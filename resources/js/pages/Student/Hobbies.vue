<script setup lang="ts">
import AppStudentLayout from '@/layouts/AppStudentLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps<{ hobbies: Array<{ id: number; name: string }> }>();

const page = usePage();
const flashSuccess = computed(() => (page.props.flash as any)?.success as string | undefined);

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Talaba paneli', href: dashboard().url },
    { title: 'Qiziqishlar', href: '/student/hobbies' },
];

// Kamida bitta bo'sh maydon turadi, shunda talaba darhol yoza boshlaydi.
const initial = props.hobbies.length > 0 ? props.hobbies.map((hobby) => hobby.name) : [''];

const form = useForm({ hobbies: initial });
const justSaved = ref(false);

const addHobby = () => {
    if (form.hobbies.length < 30) {
        form.hobbies.push('');
    }
};

const removeHobby = (index: number) => {
    form.hobbies.splice(index, 1);

    if (form.hobbies.length === 0) {
        form.hobbies.push('');
    }
};

const filledCount = computed(() => form.hobbies.filter((hobby) => hobby.trim() !== '').length);

const submit = () => {
    form.post('/student/hobbies', {
        preserveScroll: true,
        onSuccess: () => {
            justSaved.value = true;
            window.setTimeout(() => (justSaved.value = false), 3000);
        },
    });
};
</script>

<template>
    <Head title="Qiziqishlar" />
    <AppStudentLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div class="mx-auto w-full max-w-2xl">
                <div class="rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
                    <header class="border-b border-slate-100 px-6 py-5 dark:border-slate-700">
                        <h1 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Qiziqishlarim</h1>
                        <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">
                            Bo'sh vaqtingizda nima bilan shug'ullanasiz? Bir nechta qiziqish qo'shishingiz mumkin —
                            ular ijtimoiy-psixologik passportingizda ko'rsatiladi.
                        </p>
                    </header>

                    <form class="px-6 py-6" @submit.prevent="submit">
                        <div
                            v-if="justSaved && flashSuccess"
                            class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-800 dark:bg-emerald-900/20 dark:text-emerald-300"
                        >
                            {{ flashSuccess }}
                        </div>

                        <div class="space-y-3">
                            <div v-for="(_, index) in form.hobbies" :key="index" class="flex items-start gap-2">
                                <span
                                    class="mt-2.5 w-5 shrink-0 text-right text-sm font-medium text-slate-400 dark:text-slate-500"
                                >
                                    {{ index + 1 }}
                                </span>
                                <div class="flex-1">
                                    <input
                                        v-model="form.hobbies[index]"
                                        type="text"
                                        placeholder="Masalan: Shaxmat, kitob o'qish, sport..."
                                        maxlength="120"
                                        class="block w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                                    />
                                    <p v-if="form.errors[`hobbies.${index}` as keyof typeof form.errors]" class="mt-1 text-sm text-rose-600">
                                        {{ form.errors[`hobbies.${index}` as keyof typeof form.errors] }}
                                    </p>
                                </div>
                                <button
                                    type="button"
                                    class="mt-1 rounded-lg p-2 text-rose-600 transition hover:bg-rose-50 dark:hover:bg-rose-900/20"
                                    title="O'chirish"
                                    @click="removeHobby(index)"
                                >
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <p v-if="form.errors.hobbies" class="mt-2 text-sm text-rose-600">{{ form.errors.hobbies }}</p>

                        <button
                            type="button"
                            :disabled="form.hobbies.length >= 30"
                            class="mt-4 inline-flex items-center gap-1.5 text-sm font-medium text-blue-600 transition hover:text-blue-700 disabled:opacity-40"
                            @click="addHobby"
                        >
                            <span class="text-lg leading-none">+</span> Yana qiziqish qo'shish
                        </button>

                        <div class="mt-8 flex items-center justify-between border-t border-slate-100 pt-6 dark:border-slate-700">
                            <p class="text-sm text-slate-500 dark:text-slate-400">
                                To'ldirilgan: <span class="font-medium">{{ filledCount }}</span> ta
                            </p>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="inline-flex items-center justify-center rounded-xl bg-emerald-600 px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700 focus:ring-4 focus:ring-emerald-600/20 focus:outline-none disabled:opacity-60"
                            >
                                {{ form.processing ? 'Saqlanmoqda...' : 'Saqlash' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppStudentLayout>
</template>
