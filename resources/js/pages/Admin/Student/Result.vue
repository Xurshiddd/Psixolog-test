<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';

const props = withDefaults(
    defineProps<{
        student: any;
        module: any;
        answers: Record<number, Array<any>>;
        diagnosis: string | null;
        generatedDiagnosis: string | null;
        basePath?: string;
        backTitle?: string;
    }>(),
    {
        basePath: '/admin/students',
        backTitle: 'Talabalar',
    },
);

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: props.backTitle,
        href: props.basePath,
    },
    {
        title: props.student.name,
        href: `${props.basePath}/${props.student.id}`,
    },
    {
        title: props.module.name,
        href: '#',
    },
];

const form = useForm({
    diagnosis: props.diagnosis || '',
});

const currentDiagnosis = ref<string | null>(props.diagnosis);

const submit = (options: { clearAi?: boolean } = {}) => {
    form.post(`${props.basePath}/${props.student.id}/results/${props.module.id}/diagnosis`, {
        preserveScroll: true,
        onSuccess: () => {
            currentDiagnosis.value = form.diagnosis || null;

            if (options.clearAi !== false) {
                aiDiagnosis.value = null;
            }
        },
    });
};

const aiLoading = ref(false);
const aiDiagnosis = ref<string | null>(null);
const aiError = ref<string | null>(null);
const aiCancelled = ref(false);
const aiAbortController = ref<AbortController | null>(null);

const handleAiStreamChunk = (payload: string) => {
    if (payload === '[DONE]') {
        return;
    }

    const parsed = JSON.parse(payload);

    if (parsed.type === 'error') {
        aiError.value = parsed.message || 'AI xulosa olishda xatolik yuz berdi.';
        return;
    }

    if (parsed.type === 'text_delta') {
        aiDiagnosis.value = (aiDiagnosis.value ?? '') + (parsed.delta || '');
    }
};

const getAiDiagnosis = async () => {
    aiLoading.value = true;
    aiDiagnosis.value = '';
    aiError.value = null;
    aiCancelled.value = false;
    aiAbortController.value = new AbortController();

    try {
        const response = await fetch(
            `${props.basePath}/${props.student.id}/results/${props.module.id}/ai-diagnosis-stream`,
            {
                method: 'POST',
                signal: aiAbortController.value.signal,
                headers: {
                    Accept: 'text/event-stream',
                    'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '',
                },
            }
        );

        if (!response.ok) {
            const data = await response.json().catch(() => null);
            aiError.value = data?.error || 'AI xulosa olishda xatolik yuz berdi.';
            return;
        }

        if (!response.body) {
            aiError.value = 'Browser stream javobini o‘qiy olmadi.';
            return;
        }

        const reader = response.body.getReader();
        const decoder = new TextDecoder('utf-8');
        let buffer = '';

        while (true) {
            const { done, value } = await reader.read();

            if (done) {
                break;
            }

            buffer += decoder.decode(value, { stream: true });

            const events = buffer.split('\n\n');
            buffer = events.pop() || '';

            for (const eventChunk of events) {
                const dataLines = eventChunk
                    .split('\n')
                    .filter((line) => line.startsWith('data: '))
                    .map((line) => line.slice(6));

                if (dataLines.length === 0) {
                    continue;
                }

                for (const line of dataLines) {
                    handleAiStreamChunk(line);
                }
            }
        }
    } catch (e) {
        if (e instanceof DOMException && e.name === 'AbortError') {
            aiCancelled.value = true;
        } else {
            aiError.value = 'Serverga ulanishda xatolik yuz berdi.';
        }
    } finally {
        aiAbortController.value = null;
        aiLoading.value = false;
    }
};

const cancelAiDiagnosis = () => {
    aiAbortController.value?.abort();
};

const clearAiDiagnosis = () => {
    aiDiagnosis.value = null;
    aiError.value = null;
    aiCancelled.value = false;
};

const applyAiDiagnosis = () => {
    if (!aiDiagnosis.value?.trim()) {
        return;
    }

    form.diagnosis = aiDiagnosis.value;
    submit();
};

const isOptionSelected = (testId: number, optionId: number) => {
    const testAnswers = props.answers[testId];
    if (!testAnswers) return false;

    // Check if testAnswers is an array (it should be due to groupBy)
    if (Array.isArray(testAnswers)) {
        return testAnswers.some((answer: any) => answer.test_option_id === optionId);
    }

    return false;
};

const optionStats = computed(() => {
    const buckets: Record<number, { count: number; category?: any }> = {};
    const tests = props.module?.tests || [];
    const totalTests = tests.length || 0;

    // Initialize buckets from module options (use option_value field)
    for (const test of tests) {
        for (const option of test.options || []) {
            const val = typeof option.option_value !== 'undefined' ? option.option_value : (option.value ?? 0);
            if (!Object.prototype.hasOwnProperty.call(buckets, val)) {
                buckets[val] = { count: 0, category: option.result_category ?? option.result_category_id ?? undefined };
            }
        }
    }

    // For each test, count selected options' option_value
    for (const test of tests) {
        const testAnswers = props.answers?.[test.id] || [];
        if (!Array.isArray(testAnswers) || testAnswers.length === 0) continue;

        // Count each selected option (handle multiple selections defensively)
        for (const ans of testAnswers) {
            const opt = (test.options || []).find((o: any) => o.id === ans.test_option_id);
            if (!opt) continue;
            const val = typeof opt.option_value !== 'undefined' ? opt.option_value : (opt.value ?? 0);
            if (!Object.prototype.hasOwnProperty.call(buckets, val)) {
                buckets[val] = { count: 0, category: opt.result_category ?? opt.result_category_id ?? undefined };
            }
            buckets[val].count += 1;
        }
    }

    const arr = Object.entries(buckets).map(([key, v]) => {
        const valueNum = Number(key);
        const percent = totalTests > 0 ? Math.round((v.count / totalTests) * 100) : 0;
        return { value: valueNum, count: v.count, percent, category: v.category };
    });

    arr.sort((a, b) => b.count - a.count);
    return { stats: arr, totalTests };
});
</script>

<template>

    <Head :title="`${module.name} - Natijalar`" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <h1 class="text-2xl font-bold tracking-tight mb-4">{{ module.name }} - Natijalar</h1>

            <div class="space-y-6">
                <!-- Module Option Statistics -->
                <div class="rounded-lg border bg-card p-6 shadow-sm">
                    <h2 class="text-xl font-bold mb-3">Statistika</h2>
                    <p class="text-sm text-muted-foreground mb-4">Har bir option uchun tanlanganlar soni va foizi (modul
                        testlari bo'yicha).</p>

                    <div v-if="optionStats.stats.length > 0" class="space-y-3">
                        <div v-for="stat in optionStats.stats" :key="stat.value" class="flex flex-col gap-2">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <span class="text-sm font-medium">Variant {{ stat.value }}</span>
                                    <span v-if="stat.category"
                                        class="text-xs text-muted-foreground px-2 py-0.5 rounded bg-muted/20">{{
                                        stat.category.name ?? stat.category }}</span>
                                </div>
                                <div class="text-sm font-semibold">{{ stat.count }} / {{ optionStats.totalTests }} ({{
                                    stat.percent }}%)</div>
                            </div>

                            <div class="w-full bg-muted h-2 rounded overflow-hidden">
                                <div class="h-2 bg-primary" :style="{ width: stat.percent + '%' }"></div>
                            </div>
                        </div>
                        <div class="flex items-center justify-between">
                            <h5 class="text-lg font-bold">
                                Jami: {{optionStats.stats.reduce((sum, item) => sum + (item.count * item.value), 0)}}
                            </h5>
                        </div>
                    </div>

                    <div v-else class="text-sm text-muted-foreground">Statistika uchun yetarli ma'lumot yo'q.</div>
                </div>
                <!-- Questions and Answers -->
                <div v-for="(test, index) in module.tests" :key="test.id"
                    class="rounded-lg border bg-card p-6 shadow-sm">
                    <div class="mb-4">
                        <span class="font-bold text-lg mr-2">{{ Number(index) + 1 }}.</span>
                        <span class="text-lg font-medium">{{ test.question }}</span>
                    </div>
                    <div v-if="test.image" class="mb-4">
                        <img :src="'/storage/' + test.image" alt="Question Image"
                            class="max-w-full h-auto rounded-md max-h-96 object-contain" />
                    </div>

                    <div v-if="test.type === 'text'" class="p-4 rounded-md border bg-slate-50 dark:bg-slate-900">
                        <p class="font-medium text-sm text-muted-foreground mb-1">Foydalanuvchi javobi:</p>
                        <p class="text-base">{{ answers[test.id]?.[0]?.answer || 'Javob berilmagan' }}</p>
                    </div>

                    <div v-else class="grid gap-3">
                        <div v-for="option in test.options" :key="option.id"
                            class="p-3 rounded-md border flex justify-between items-center" :class="{
                                'bg-blue-50 border-blue-200 dark:bg-blue-900/20 dark:border-blue-800': isOptionSelected(test.id, option.id),
                                'bg-white dark:bg-slate-900': !isOptionSelected(test.id, option.id)
                            }">
                            <span>{{ option.option_text }}</span>
                            <span v-if="isOptionSelected(test.id, option.id)"
                                class="text-sm font-semibold text-blue-600 dark:text-blue-400">
                                (Tanlangan)
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Diagnosis Section -->
                <div class="rounded-lg border bg-card p-6 shadow-sm">
                    <h2 class="text-xl font-bold mb-4">Diagnostika Xulosasi</h2>

                    <form @submit.prevent="submit">
                        <div v-if="generatedDiagnosis" class="mb-4 rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-900/60 dark:bg-amber-950/30 dark:text-amber-200">
                            <p class="font-medium mb-1">Avtomatik xulosasi</p>
                            <p class="whitespace-pre-wrap leading-relaxed">{{ generatedDiagnosis }}</p>
                        </div>

                        <div class="mb-4">
                            <label for="diagnosis"
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Psixolog xulosasi
                            </label>
                            <textarea id="diagnosis" v-model="form.diagnosis" rows="6"
                                class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                placeholder="Psixolog tomonidan yakuniy xulosa yoziladi..."></textarea>
                            <div v-if="form.errors.diagnosis" class="text-sm text-red-500 mt-1">
                                {{ form.errors.diagnosis }}
                            </div>
                        </div>

                        <div class="mb-6 rounded-lg border border-violet-200 dark:border-violet-800 bg-violet-50 dark:bg-violet-950/30 p-4">
                            <div class="flex items-center justify-between mb-3 gap-3">
                                <div class="flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-violet-600 dark:text-violet-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                                    </svg>
                                    <div>
                                        <h3 class="text-sm font-semibold text-violet-700 dark:text-violet-300">AI xulosasi</h3>
                                <p class="text-xs text-violet-500 dark:text-violet-400">AI natijasi pastda chiqadi, chapda bekor qilish, o'ngda tasdiqlab saqlash mumkin.</p>
                                    </div>
                                </div>
                                <button
                                    id="ai-diagnosis-btn"
                                    type="button"
                                    @click="getAiDiagnosis"
                                    :disabled="aiLoading || form.processing"
                                    class="inline-flex items-center gap-2 rounded-md bg-violet-600 hover:bg-violet-700 disabled:opacity-60 disabled:cursor-not-allowed text-white text-sm font-medium px-4 py-2 transition-colors"
                                >
                                    <svg v-if="aiLoading" class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                    </svg>
                                    <span>{{ aiLoading ? 'AI tahlil qilmoqda...' : 'AI xulosa olish' }}</span>
                                </button>
                                <button
                                    v-if="aiLoading"
                                    id="cancel-ai-diagnosis-btn"
                                    type="button"
                                    @click="cancelAiDiagnosis"
                                    class="inline-flex items-center gap-2 rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition-colors hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800"
                                >
                                    Bekor qilish
                                </button>
                            </div>

                            <div v-if="aiLoading && !aiDiagnosis" class="space-y-2 animate-pulse">
                                <div class="h-3 bg-violet-200 dark:bg-violet-800 rounded w-full"></div>
                                <div class="h-3 bg-violet-200 dark:bg-violet-800 rounded w-5/6"></div>
                                <div class="h-3 bg-violet-200 dark:bg-violet-800 rounded w-4/6"></div>
                                <div class="h-3 bg-violet-200 dark:bg-violet-800 rounded w-full"></div>
                                <div class="h-3 bg-violet-200 dark:bg-violet-800 rounded w-3/4"></div>
                            </div>

                            <div v-if="aiError" class="mt-2 rounded-md border border-red-200 bg-red-50 p-3 dark:border-red-800 dark:bg-red-900/20">
                                <p class="text-sm text-red-600 dark:text-red-400">{{ aiError }}</p>
                            </div>

                            <div v-if="aiCancelled" class="mt-2 rounded-md border border-amber-200 bg-amber-50 p-3 dark:border-amber-800 dark:bg-amber-900/20">
                                <p class="text-sm text-amber-700 dark:text-amber-300">AI javobi foydalanuvchi tomonidan to‘xtatildi.</p>
                            </div>

                            <div v-if="aiDiagnosis !== null" id="ai-result-block" class="mt-2 space-y-3">
                                <div class="rounded-md border border-violet-100 bg-white p-4 dark:border-violet-900 dark:bg-slate-900">
                                    <p class="whitespace-pre-wrap text-sm leading-relaxed">{{ aiDiagnosis }}</p>
                                </div>
                                <div class="flex items-center justify-between gap-3">
                                    <button
                                        id="clear-ai-btn"
                                        type="button"
                                        @click="clearAiDiagnosis"
                                        :disabled="form.processing || aiLoading || !aiDiagnosis?.trim()"
                                        class="inline-flex items-center gap-2 rounded-md bg-red-600 hover:bg-red-700 disabled:opacity-60 disabled:cursor-not-allowed text-white text-sm font-medium px-5 py-2 transition-colors"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                        AI xulosani o'chirish
                                    </button>
                                    <button
                                        id="confirm-ai-btn"
                                        type="button"
                                        @click="applyAiDiagnosis"
                                        :disabled="form.processing || aiLoading || !aiDiagnosis?.trim()"
                                        class="inline-flex items-center gap-2 rounded-md bg-green-600 hover:bg-green-700 disabled:opacity-60 disabled:cursor-not-allowed text-white text-sm font-medium px-5 py-2 transition-colors"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                        Tasdiqlash va saqlash
                                    </button>
                                </div>
                            </div>

                            <p v-if="!aiLoading && aiDiagnosis === null && !aiError" class="text-xs text-violet-500 dark:text-violet-400">
                                Foydalanuvchi javoblari asosida AI qoralama xulosa tayyorlaydi.
                            </p>
                        </div>

                        <div class="flex justify-end">
                            <button type="submit"
                                id="save-diagnosis-btn"
                                class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-primary text-primary-foreground hover:bg-primary/90 h-10 px-4 py-2"
                                :disabled="form.processing">
                                <span v-if="form.processing">Saqlanmoqda...</span>
                                <span v-else>Saqlash</span>
                            </button>
                        </div>
                    </form>

                    <div v-if="currentDiagnosis" class="mt-8 pt-6 border-t">
                        <h3 class="text-lg font-semibold mb-2 text-green-700 dark:text-green-400">Joriy psixolog xulosasi:</h3>
                        <div
                            class="bg-green-50 dark:bg-green-900/10 p-4 rounded-md border border-green-100 dark:border-green-900/20">
                            <p class="whitespace-pre-wrap text-sm">{{ currentDiagnosis }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
