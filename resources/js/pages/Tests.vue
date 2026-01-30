<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { module_change_status, module_delete, test_index, test_create, test_edit } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { ref } from 'vue';

const props = defineProps<{ testsCount: number; modules: any; modulesCount: number }>();

const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString();
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Tests Module',
        href: test_index().url,
    },
];

// Use props directly
const loading = ref<{ moduleId: number; field: string } | null>(null);

const toggleStatus = (moduleId: number, field: 'is_active' | 'shuffle') => {
    loading.value = { moduleId, field };
    
    router.patch(module_change_status().url, {
        module_id: moduleId,
        field: field,
    }, {
        preserveScroll: true,
        onFinish: () => {
            loading.value = null;
        },
        onError: (errors: any) => {
            console.error('Error toggling status:', errors);
        }
    });
};

const deleteModule = (moduleId: number) => {
    if (confirm('Are you sure you want to delete this module?')) {
        router.delete(module_delete(moduleId).url, {
            preserveScroll: true,
            onFinish: () => {
                loading.value = null;
            },
            onError: (errors: any) => {
                console.error('Error deleting module:', errors);
            }
        });
    }
};

const goToPage = (page: number) => {
    router.get(test_index().url, { page }, {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Dashboard" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 shadow-sm p-6">
                    <div class="flex items-center gap-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Jami Testlar</p>
                            <h3 class="text-2xl font-bold text-slate-900 dark:text-slate-100">{{ props.testsCount }}</h3>
                        </div>
                    </div>
                </div>
                <div class="rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 shadow-sm p-6">
                    <div class="flex items-center gap-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 00-.491 6.347A48.62 48.62 0 015.824 20.75m12.07-5.272a48.882 48.882 0 013.318.612l.45-2.25m-5.657.134a48.879 48.879 0 013.282-.643m0 .419a24.442 24.442 0 015.572 0m0 0a24.482 24.482 0 013.917.811M6.134 6.75a48.868 48.868 0 015.064 0l1.969-3.94a24.467 24.467 0 00-5.064 0l-1.969 3.94z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Jami Modullar</p>
                            <h3 class="text-2xl font-bold text-slate-900 dark:text-slate-100">{{ props.modulesCount }}</h3>
                        </div>
                    </div>
                </div>
            </div>
            <div class="h-6 flex items-center justify-end">
                <Link :href="test_create().url" class="inline-flex items-center justify-end px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:bg-blue-600 dark:hover:bg-blue-700">Test yaratish</Link>
            </div>
            <!-- Table -->
            <div class="rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100 dark:divide-slate-700">
                        <thead>
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-slate-500 dark:text-slate-400">ID</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-slate-500 dark:text-slate-400">Name</th>
                                <th class="px-4 py-2 text-center text-xs font-medium text-slate-500 dark:text-slate-400">Status</th>
                                <th class="px-4 py-2 text-center text-xs font-medium text-slate-500 dark:text-slate-400">Aralashtirish</th>
                                <th class="px-4 py-2 text-center text-xs font-medium text-slate-500 dark:text-slate-400">Harakatlar</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-slate-500 dark:text-slate-400">Created At</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-slate-500 dark:text-slate-400">Updated At</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                            <tr v-for="module in props.modules.data" :key="module.id" class="hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                                <td class="px-4 py-2">{{ module.id }}</td>
                                <td class="px-4 py-2">{{ module.name }}</td>
                                <td class="px-4 py-2 text-center">
                                    <label class="inline-flex items-center cursor-pointer" :style="{ opacity: loading?.moduleId === module.id ? 0.6 : 1 }">
                                        <input
                                            :checked="module.is_active"
                                            :disabled="loading?.moduleId === module.id"
                                            @change="toggleStatus(module.id, 'is_active')"
                                            type="checkbox"
                                            class="sr-only peer"
                                        />
                                        <div 
                                            class="relative w-11 h-6 peer-focus:outline-none peer-focus:ring-4 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600"
                                            :class="[
                                                module.is_active 
                                                    ? 'bg-green-600 dark:bg-green-600 peer-focus:ring-green-300 dark:peer-focus:ring-green-800' 
                                                    : 'bg-gray-200 dark:bg-gray-700 peer-focus:ring-gray-300 dark:peer-focus:ring-gray-800'
                                            ]"
                                        ></div>
                                    </label>
                                </td>
                                <td class="px-4 py-2 text-center">
                                    <label class="inline-flex items-center cursor-pointer" :style="{ opacity: loading?.moduleId === module.id ? 0.6 : 1 }">
                                        <input
                                            :checked="module.shuffle"
                                            :disabled="loading?.moduleId === module.id"
                                            @change="toggleStatus(module.id, 'shuffle')"
                                            type="checkbox"
                                            class="sr-only peer"
                                        />
                                        <div 
                                            class="relative w-11 h-6 peer-focus:outline-none peer-focus:ring-4 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600"
                                            :class="[
                                                module.shuffle 
                                                    ? 'bg-blue-600 dark:bg-blue-600 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800' 
                                                    : 'bg-gray-200 dark:bg-gray-700 peer-focus:ring-gray-300 dark:peer-focus:ring-gray-800'
                                            ]"
                                        ></div>
                                    </label>
                                </td>
                                <td class="px-4 py-2 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <Link 
                                            :href="test_edit(module.id).url"
                                            class="inline-flex items-center justify-center p-2 text-blue-500 hover:bg-blue-50 dark:hover:bg-blue-900/20 dark:text-blue-400 rounded-lg transition-colors"
                                            title="Tahrirlash"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                                            </svg>
                                        </Link>
                                        <button 
                                            class="inline-flex items-center justify-center p-2 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 dark:text-red-400 rounded-lg transition-colors"
                                            :disabled="loading?.moduleId === module.id"
                                            @click="deleteModule(module.id)"
                                            title="O'chirish"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                                <td class="px-4 py-2 text-sm text-slate-600 dark:text-slate-400">{{ formatDate(module.created_at) }}</td>
                                <td class="px-4 py-2 text-sm text-slate-600 dark:text-slate-400">{{ formatDate(module.updated_at) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="flex items-center justify-between border-t border-slate-100 dark:border-slate-700 px-4 py-4 bg-slate-50 dark:bg-slate-900/50">
                    <div class="text-sm text-slate-600 dark:text-slate-400">
                        Showing <span class="font-medium">{{ props.modules.from }}</span> to <span class="font-medium">{{ props.modules.to }}</span> of <span class="font-medium">{{ props.modules.total }}</span> modules
                    </div>
                    <div class="flex items-center gap-2">
                        <!-- Previous Button -->
                        <button
                            v-if="props.modules.current_page > 1"
                            @click="goToPage(props.modules.current_page - 1)"
                            class="inline-flex items-center justify-center px-3 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 border border-slate-300 dark:border-slate-600 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                            </svg>
                            Oldingi
                        </button>

                        <!-- Page Numbers -->
                        <div class="flex items-center gap-1">
                            <template v-for="(link, index) in props.modules.links.slice(1, -1)" :key="index">
                                <button
                                    @click="link.url && goToPage(parseInt(link.label))"
                                    :disabled="!link.url"
                                    :class="[
                                        'inline-flex items-center justify-center px-3 py-2 text-sm font-medium rounded-lg transition-colors',
                                        link.active
                                            ? 'bg-blue-600 text-white dark:bg-blue-600'
                                            : link.url
                                            ? 'text-slate-700 dark:text-slate-300 border border-slate-300 dark:border-slate-600 hover:bg-slate-100 dark:hover:bg-slate-800'
                                            : 'text-slate-400 dark:text-slate-600 cursor-not-allowed'
                                    ]"
                                    v-html="link.label"
                                >
                                </button>
                            </template>
                        </div>

                        <!-- Next Button -->
                        <button
                            v-if="props.modules.current_page < props.modules.last_page"
                            @click="goToPage(props.modules.current_page + 1)"
                            class="inline-flex items-center justify-center px-3 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 border border-slate-300 dark:border-slate-600 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                        >
                            Keyingi
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5L15.75 12l-7.5 7.5" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
