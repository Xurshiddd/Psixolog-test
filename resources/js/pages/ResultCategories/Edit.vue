<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { ref } from 'vue';

interface Module {
    id: number;
    name: string;
}

interface Category {
    id: number;
    name: string;
    diagnostic: string | null;
    fake_diagnostic: string | null;
    value: number | null;
    module_id: number;
}

const props = defineProps<{
    category: Category;
    modules: Module[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Result Categories',
        href: '/result-categories',
    },
    {
        title: 'Tahrirlash',
        href: `/result-categories/${props.category.id}/edit`,
    },
];

const previousUrl = ref<string>(
    typeof document !== 'undefined' ? document.referrer || '/result-categories' : '/result-categories'
);

const form = ref({
    name: props.category.name,
    diagnostic: props.category.diagnostic || '',
    fake_diagnostic: props.category.fake_diagnostic || '',
    value: props.category.value,
    module_id: props.category.module_id,
});

const errors = ref<Record<string, string>>({});
const isSubmitting = ref(false);
const testValues = ref<number[]>([]);
const loadingValues = ref(false);
const page = usePage();

const fetchTestOptions = async (moduleId: number) => {
    if (!moduleId) {
        testValues.value = [];
        return;
    }
    
    loadingValues.value = true;
    try {
        const response = await fetch(`/api/modules/${moduleId}/test-options`);
        const data = await response.json();
        testValues.value = data;
    } catch (error) {
        console.error('Error fetching test options:', error);
        testValues.value = [];
    } finally {
        loadingValues.value = false;
    }
};

const handleModuleChange = () => {
    if (form.value.module_id) {
        fetchTestOptions(form.value.module_id);
    } else {
        form.value.value = null;
    }
};

// Load test options on component mount
const initializeTestOptions = async () => {
    if (form.value.module_id) {
        await fetchTestOptions(form.value.module_id);
    }
};

initializeTestOptions();

const submitForm = () => {
    isSubmitting.value = true;
    errors.value = {};

    router.put(`/result-categories/${props.category.id}`, form.value, {
        onError: (errorsResponse) => {
            errors.value = errorsResponse as Record<string, string>;
            isSubmitting.value = false;
        },
        onFinish: () => {
            isSubmitting.value = false;
        },
    });
};
</script>

<template>
    <Head :title="`${category.name} - Tahrirlash`" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <!-- Header -->
            <div>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-slate-100">
                    Kategoriyani Tahrirlash
                </h1>
                <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">
                    {{ category.name }} kategoriyasini tahrirlang
                </p>
            </div>

            <!-- Form Card -->
            <div class="rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 shadow-sm p-6 max-w-2xl">
                <form @submit.prevent="submitForm" class="space-y-6">
                    <!-- Name Field -->
                    <div>
                        <label for="name" class="block text-sm font-medium text-slate-900 dark:text-slate-100 mb-2">
                            Kategoriya Nomi <span class="text-red-500">*</span>
                        </label>
                        <input
                            id="name"
                            v-model="form.name"
                            type="text"
                            placeholder="Masalan: A-grumli, B-normal"
                            class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                            :class="{ 'border-red-500': errors.name }"
                        />
                        <p v-if="errors.name" class="mt-1 text-sm text-red-500">{{ errors.name }}</p>
                    </div>

                    <!-- Module Field -->
                    <div>
                        <label for="module" class="block text-sm font-medium text-slate-900 dark:text-slate-100 mb-2">
                            Modul <span class="text-red-500">*</span>
                        </label>
                        <select
                            id="module"
                            v-model.number="form.module_id"
                            @change="handleModuleChange"
                            class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-blue-500"
                            :class="{ 'border-red-500': errors.module_id }"
                            disabled
                        >
                            <option :value="null" disabled>Modulni tanlang</option>
                            <option v-for="module in modules" :key="module.id" :value="module.id">
                                {{ module.name }}
                            </option>
                        </select>
                        <p v-if="errors.module_id" class="mt-1 text-sm text-red-500">{{ errors.module_id }}</p>
                    </div>

                    <!-- Value Field -->
                    <div>
                        <label for="value" class="block text-sm font-medium text-slate-900 dark:text-slate-100 mb-2">
                            Qiymat <span v-if="form.module_id" class="text-xs font-normal text-slate-500 dark:text-slate-400">(test variantlaridan)</span>
                        </label>
                        <select
                            id="value"
                            v-model.number="form.value"
                            disabled
                            class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                            :class="{ 'border-red-500': errors.value }"
                        >
                            <option :value="null" disabled>
                                {{ loadingValues ? 'Yuklashda...' : 'Qiymatni tanlang' }}
                            </option>
                            <option v-for="val in testValues" :key="val" :value="val">
                                {{ val }}
                            </option>
                        </select>
                        <p v-if="errors.value" class="mt-1 text-sm text-red-500">{{ errors.value }}</p>
                        <p v-if="form.module_id && testValues.length === 0 && !loadingValues" class="mt-1 text-sm text-amber-500">
                            Ushbu modulda test variantlari topilmadi
                        </p>
                    </div>

                    <!-- Diagnostic Field -->
                    <div>
                        <label for="diagnostic" class="block text-sm font-medium text-slate-900 dark:text-slate-100 mb-2">
                            Tashxis
                        </label>
                        <textarea
                            id="diagnostic"
                            v-model="form.diagnostic"
                            rows="4"
                            placeholder="Bu kategoriyaning tashxisini kiriting"
                            class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                            :class="{ 'border-red-500': errors.diagnostic }"
                        ></textarea>
                        <p v-if="errors.diagnostic" class="mt-1 text-sm text-red-500">{{ errors.diagnostic }}</p>
                    </div>

                    <!-- Fake Diagnostic Field -->
                    <div>
                        <label for="fake_diagnostic" class="block text-sm font-medium text-slate-900 dark:text-slate-100 mb-2">
                            Soxta Tashxis
                        </label>
                        <textarea
                            id="fake_diagnostic"
                            v-model="form.fake_diagnostic"
                            rows="4"
                            placeholder="Soxta tashxisni kiriting"
                            class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                            :class="{ 'border-red-500': errors.fake_diagnostic }"
                        ></textarea>
                        <p v-if="errors.fake_diagnostic" class="mt-1 text-sm text-red-500">{{ errors.fake_diagnostic }}</p>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex gap-3 pt-4">
                        <button
                            type="submit"
                            :disabled="isSubmitting"
                            class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:bg-blue-600 dark:hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                        >
                            <svg v-if="isSubmitting" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4 mr-2 animate-spin">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.995-1.465" />
                            </svg>
                            {{ isSubmitting ? 'Saqlashda...' : 'Saqlash' }}
                        </button>
                        <button
                            type="button"
                            @click="router.visit(previousUrl, { preserveState: true })"
                            class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-slate-700 rounded-lg hover:bg-slate-200 dark:hover:bg-slate-600 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2 dark:focus:ring-offset-slate-900 transition-colors"
                        >
                            Bekor qilish
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
