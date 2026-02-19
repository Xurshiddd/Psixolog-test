<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { ref } from 'vue';

interface Category {
    id: number;
    name: string;
    diagnostic: string | null;
    fake_diagnostic: string | null;
    value: number | null;
    module: {
        id: number;
        name: string;
    };
    created_at: string;
    updated_at: string;
}

interface PaginatedData {
    data: Category[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

const props = defineProps<{
    categories: PaginatedData;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Result Categories',
        href: '/result-categories',
    },
];

const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString();
};

const deleteCategory = (id: number) => {
    if (confirm('Siz rostdan ham bu kategoriyani o\'chirib tashlamoqchisiz?')) {
        router.delete(`/result-categories/${id}`, {
            preserveScroll: true,
        });
    }
};

const goToPage = (page: number) => {
    router.get('/result-categories', { page }, {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Result Categories" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <!-- Header with Create Button -->
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold text-slate-900 dark:text-slate-100">
                    Natija Kategoriyalari
                </h1>
                <Link href="/result-categories/create" class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:bg-blue-600 dark:hover:bg-blue-700">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5 mr-2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Yangi Kategoriya
                </Link>
            </div>

            <!-- Stats Card -->
            <div class="rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 shadow-sm p-6">
                <div class="flex items-center gap-4">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-purple-50 dark:bg-purple-900/20 text-purple-600 dark:text-purple-400">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Jami Kategoriyalar</p>
                        <h3 class="text-2xl font-bold text-slate-900 dark:text-slate-100">{{ categories.total }}</h3>
                    </div>
                </div>
            </div>

            <!-- Table -->
            <div class="rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100 dark:divide-slate-700">
                        <thead class="bg-slate-50 dark:bg-slate-900">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-900 dark:text-slate-100">ID</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-900 dark:text-slate-100">Nomi</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-900 dark:text-slate-100">Modul</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-900 dark:text-slate-100">Qiymat</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-900 dark:text-slate-100">Yaratildi</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-slate-900 dark:text-slate-100">Harakatlar</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                            <tr v-for="category in categories.data" :key="category.id" class="hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                                <td class="px-4 py-3 text-sm text-slate-900 dark:text-slate-100">{{ category.id }}</td>
                                <td class="px-4 py-3 text-sm font-medium text-slate-900 dark:text-slate-100">{{ category.name }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-400">{{ category.module.name }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-400">{{ category.value || '-' }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-400">{{ formatDate(category.created_at) }}</td>
                                <td class="px-4 py-3 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <Link :href="`/result-categories/${category.id}`" class="inline-flex items-center justify-center p-2 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-colors" title="Ko'rish">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.107.424.107.639a1.012 1.012 0 0 1-.107.639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178A1.01 1.01 0 0 1 2.036 12.322Z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                            </svg>
                                        </Link>
                                        <Link :href="`/result-categories/${category.id}/edit`" class="inline-flex items-center justify-center p-2 text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-900/20 rounded-lg transition-colors" title="Tahrirlash">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 9.75a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z" />
                                            </svg>
                                        </Link>
                                        <button @click="deleteCategory(category.id)" class="inline-flex items-center justify-center p-2 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors" title="O'chirish">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 2.98a1.125 1.125 0 0 0-1.06-.813h-3.197a1.125 1.125 0 0 0-1.06.813l-.821 4.832m0 0L5.34 2.98a1.125 1.125 0 0 0-1.06-.813H1.127a1.125 1.125 0 0 0-1.06.813l-.821 4.832m15.716 0L8.959 20.84a2.25 2.25 0 0 1-2.11 1.16H5.25a2.25 2.25 0 0 1-2.11-1.16L.98 6.81m16.802 0a24.009 24.009 0 0 1-15.6 0" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="categories.data.length === 0">
                                <td colspan="6" class="px-4 py-8 text-center text-slate-600 dark:text-slate-400">
                                    Hech qanday kategoriya topilmadi
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="categories.last_page > 1" class="px-4 py-4 border-t border-slate-100 dark:border-slate-700 flex items-center justify-between">
                    <div class="text-sm text-slate-600 dark:text-slate-400">
                        {{ categories.per_page * (categories.current_page - 1) + 1 }}-{{ Math.min(categories.per_page * categories.current_page, categories.total) }} 
                        ta {{ categories.total }} dan
                    </div>
                    <div class="flex gap-2">
                        <button 
                            v-if="categories.current_page > 1"
                            @click="goToPage(categories.current_page - 1)"
                            class="px-3 py-1 text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg transition-colors"
                        >
                            Oldingi
                        </button>
                        <div class="flex gap-1">
                            <button 
                                v-for="page in Math.min(5, categories.last_page)" 
                                :key="page"
                                @click="goToPage(page)"
                                :class="[
                                    'px-3 py-1 text-sm font-medium rounded-lg transition-colors',
                                    page === categories.current_page
                                        ? 'bg-blue-600 text-white'
                                        : 'text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700'
                                ]"
                            >
                                {{ page }}
                            </button>
                        </div>
                        <button 
                            v-if="categories.current_page < categories.last_page"
                            @click="goToPage(categories.current_page + 1)"
                            class="px-3 py-1 text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg transition-colors"
                        >
                            Keyingi
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
