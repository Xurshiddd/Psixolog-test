<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';

const props = defineProps<{
    student: any;
    module: any;
    answers: Record<number, Array<any>>;
    diagnosis: string | null;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Talabalar',
        href: '/admin/students',
    },
    {
        title: props.student.name,
        href: `/admin/students/${props.student.id}`,
    },
    {
        title: props.module.name,
        href: '#',
    },
];

const form = useForm({
    diagnosis: props.diagnosis || '',
});

const submit = () => {
    router.post(`/admin/students/${props.student.id}/results/${props.module.id}/diagnosis`, {
        diagnosis: form.diagnosis
    });
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
</script>

<template>
    <Head :title="`${module.name} - Natijalar`" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <h1 class="text-2xl font-bold tracking-tight mb-4">{{ module.name }} - Natijalar</h1>

            <div class="space-y-6">
                <!-- Questions and Answers -->
                <div v-for="(test, index) in module.tests" :key="test.id" class="rounded-lg border bg-card p-6 shadow-sm">
                    <div class="mb-4">
                        <span class="font-bold text-lg mr-2">{{ Number(index) + 1 }}.</span>
                        <span class="text-lg font-medium">{{ test.question }}</span>
                    </div>
                    <div v-if="test.image" class="mb-4">
                        <img :src="'/storage/' + test.image" alt="Question Image" class="max-w-full h-auto rounded-md max-h-96 object-contain" />
                    </div>

                    <div v-if="test.type === 'text'" class="p-4 rounded-md border bg-slate-50 dark:bg-slate-900">
                        <p class="font-medium text-sm text-muted-foreground mb-1">Talaba javobi:</p>
                        <p class="text-base">{{ answers[test.id]?.[0]?.answer || 'Javob berilmagan' }}</p>
                    </div>

                    <div v-else class="grid gap-3">
                        <div 
                            v-for="option in test.options" 
                            :key="option.id"
                            class="p-3 rounded-md border flex justify-between items-center"
                            :class="{
                                'bg-blue-50 border-blue-200 dark:bg-blue-900/20 dark:border-blue-800': isOptionSelected(test.id, option.id),
                                'bg-white dark:bg-slate-900': !isOptionSelected(test.id, option.id)
                            }"
                        >
                            <span>{{ option.option_text }}</span>
                            <span v-if="isOptionSelected(test.id, option.id)" class="text-sm font-semibold text-blue-600 dark:text-blue-400">
                                (Tanlangan)
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Diagnosis Section -->
                <div class="rounded-lg border bg-card p-6 shadow-sm">
                    <h2 class="text-xl font-bold mb-4">Diagnostika Xulosasi</h2>
                    <form @submit.prevent="submit">
                        <div class="mb-4">
                            <label for="diagnosis" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Xulosa matni
                            </label>
                            <textarea 
                                id="diagnosis" 
                                v-model="form.diagnosis" 
                                rows="6" 
                                class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                placeholder="Diagnostika xulosasini kiriting..."
                            ></textarea>
                            <div v-if="form.errors.diagnosis" class="text-sm text-red-500 mt-1">
                                {{ form.errors.diagnosis }}
                            </div>
                        </div>
                        <div class="flex justify-end">
                            <button 
                                type="submit" 
                                class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-primary text-primary-foreground hover:bg-primary/90 h-10 px-4 py-2"
                                :disabled="form.processing"
                            >
                                <span v-if="form.processing">Saqlanmoqda...</span>
                                <span v-else>Saqlash</span>
                            </button>
                        </div>
                    </form>
                    
                    <div v-if="diagnosis" class="mt-8 pt-6 border-t">
                        <h3 class="text-lg font-semibold mb-2 text-green-700 dark:text-green-400">Joriy Xulosa:</h3>
                        <div class="bg-green-50 dark:bg-green-900/10 p-4 rounded-md border border-green-100 dark:border-green-900/20">
                            <p class="whitespace-pre-wrap text-sm">{{ diagnosis }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
