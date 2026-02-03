<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppStudentLayout from '@/layouts/AppStudentLayout.vue';
import { dashboard, student_test_index, student_test_take } from '@/routes';
import { type BreadcrumbItem } from '@/types';
const props = defineProps<{ modules: any }>();


const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Student Dashboard',
        href: dashboard().url,
    },
    {
        title: 'Tests',
        href: student_test_index().url,
    },
];
</script>

<template>
    <Head title="Student Dashboard" />
    <AppStudentLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div v-for="module in props.modules" :key="module.id" class="rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 shadow-sm p-6">
                    <div class="flex items-center gap-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ module.name }}</p>
                            <h4 class="text-2xl font-bold text-slate-900 dark:text-slate-100">Test savollar soni <span class="text-blue-600 dark:text-blue-400">{{ module.tests.length }}</span> ta</h4>
                            <!-- Testni yechish tugmasi -->
                            <Link :href="student_test_take(module.id).url" class="mt-4 inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                Testni yechish
                            </Link>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AppStudentLayout>
</template>
