<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';

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
    module: Module;
    created_at: string;
    updated_at: string;
}

const props = defineProps<{
    category: Category;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Result Categories',
        href: '/result-categories',
    },
    {
        title: props.category.name,
        href: `/result-categories/${props.category.id}`,
    },
];

const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('uz-UZ', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const deleteCategory = () => {
    if (confirm('Siz rostdan ham bu kategoriyani o\'chirib tashlamoqchisiz?')) {
        router.delete(`/result-categories/${props.category.id}`, {
            onSuccess: () => {
                router.visit('/result-categories');
            },
        });
    }
};
</script>

<template>
    <Head :title="category.name" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 max-w-3xl">
            <!-- Header with Actions -->
            <div class="flex items-start justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-slate-900 dark:text-slate-100">
                        {{ category.name }}
                    </h1>
                    <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">
                        Test natijalarining kategoriyasi
                    </p>
                </div>
                <div class="flex gap-2">
                    <Link :href="`/result-categories/${category.id}/edit`" class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 dark:bg-emerald-600 dark:hover:bg-emerald-700 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4 mr-2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 9.75a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z" />
                        </svg>
                        Tahrirlash
                    </Link>
                    <button @click="deleteCategory" class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 dark:bg-red-600 dark:hover:bg-red-700 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4 mr-2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 2.98a1.125 1.125 0 0 0-1.06-.813h-3.197a1.125 1.125 0 0 0-1.06.813l-.821 4.832m0 0L5.34 2.98a1.125 1.125 0 0 0-1.06-.813H1.127a1.125 1.125 0 0 0-1.06.813l-.821 4.832m15.716 0L8.959 20.84a2.25 2.25 0 0 1-2.11 1.16H5.25a2.25 2.25 0 0 1-2.11-1.16L.98 6.81m16.802 0a24.009 24.009 0 0 1-15.6 0" />
                        </svg>
                        O'chirish
                    </button>
                </div>
            </div>

            <!-- Content Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Info Card -->
                <div class="rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 shadow-sm p-6">
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100 mb-4">
                        Asosiy Ma'lumotlar
                    </h2>
                    <dl class="space-y-4">
                        <div>
                            <dt class="text-sm font-medium text-slate-600 dark:text-slate-400">ID</dt>
                            <dd class="mt-1 text-sm text-slate-900 dark:text-slate-100 font-mono bg-slate-50 dark:bg-slate-700 px-2 py-1 rounded">{{ category.id }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-slate-600 dark:text-slate-400">Nomi</dt>
                            <dd class="mt-1 text-sm text-slate-900 dark:text-slate-100">{{ category.name }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-slate-600 dark:text-slate-400">Modul</dt>
                            <dd class="mt-1 text-sm text-slate-900 dark:text-slate-100 flex items-center gap-2">
                                <span class="inline-block px-2 py-1 bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300 text-xs font-medium rounded">
                                    {{ category.module.name }}
                                </span>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-slate-600 dark:text-slate-400">Qiymat</dt>
                            <dd class="mt-1 text-sm text-slate-900 dark:text-slate-100">
                                {{ category.value || '-' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-slate-600 dark:text-slate-400">Yaratildi</dt>
                            <dd class="mt-1 text-sm text-slate-900 dark:text-slate-100">{{ formatDate(category.created_at) }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-slate-600 dark:text-slate-400">O'zgartirildi</dt>
                            <dd class="mt-1 text-sm text-slate-900 dark:text-slate-100">{{ formatDate(category.updated_at) }}</dd>
                        </div>
                    </dl>
                </div>

                <!-- Diagnostic Card -->
                <div class="rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 shadow-sm p-6">
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100 mb-4">
                        Tashxislar
                    </h2>
                    <div class="space-y-4">
                        <div>
                            <h3 class="text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Xaqiqiy Tashxis</h3>
                            <p v-if="category.diagnostic" class="text-sm text-slate-600 dark:text-slate-400 leading-relaxed whitespace-pre-wrap">
                                {{ category.diagnostic }}
                            </p>
                            <p v-else class="text-sm text-slate-400 dark:text-slate-500 italic">
                                Tashxis yo'q
                            </p>
                        </div>
                        <div class="border-t border-slate-200 dark:border-slate-700 pt-4">
                            <h3 class="text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Soxta Tashxis</h3>
                            <p v-if="category.fake_diagnostic" class="text-sm text-slate-600 dark:text-slate-400 leading-relaxed whitespace-pre-wrap">
                                {{ category.fake_diagnostic }}
                            </p>
                            <p v-else class="text-sm text-slate-400 dark:text-slate-500 italic">
                                Soxta tashxis yo'q
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Back Button -->
            <div>
                <Link href="/result-categories" class="inline-flex items-center text-sm font-medium text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4 mr-1">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                    </svg>
                    Orqaga
                </Link>
            </div>
        </div>
    </AppLayout>
</template>
