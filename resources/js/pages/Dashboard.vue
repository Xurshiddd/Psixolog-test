<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import CategoryStudentChart from '@/components/CategoryStudentChart.vue';
import FaculityStudentChart from '@/components/FaculityStudentChart.vue';
import ModuleStatsChart from '@/components/ModuleStatsChart.vue';
import ResultCategoryChart from '@/components/ResultCategoryChart.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';

interface ModuleStatItem {
    name: string;
    solvedCount: number;
}

interface ResultCategoryStatItem {
    name: string;
    solvedCount: number;
}

interface CategoryStudentStatItem {
    name: string;
    studentCount: number;
}

interface FaculityStudentStatItem {
    name: string;
    studentCount: number;
}

interface StudentPopulationStats {
    totalStudents: number;
    platformStudentsCount: number;
    loggedInStudentsCount: number;
    solvedAtLeastOneCount: number;
    solvedAllModulesCount: number;
    loginPercentage: number;
    solvedAtLeastOnePercentage: number;
    solvedAllModulesPercentage: number;
}

const props = defineProps<{ 
    tests: any; 
    testsCount: number; 
    modules: any; 
    modulesCount: number;
    moduleStats?: ModuleStatItem[];
    resultCategoryStats?: ResultCategoryStatItem[];
    categoryStudentStats?: CategoryStudentStatItem[];
    faculityStudentStats?: FaculityStudentStatItem[];
    studentPopulationStats: StudentPopulationStats;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];

const completionStats = [
    {
        title: 'Platformaga kirganlar',
        count: props.studentPopulationStats.platformStudentsCount,
        percentage: props.studentPopulationStats.loginPercentage,
        tone: 'from-blue-500 to-cyan-500',
    },
    {
        title: 'Kamida 1 modul yechganlar',
        count: props.studentPopulationStats.solvedAtLeastOneCount,
        percentage: props.studentPopulationStats.solvedAtLeastOnePercentage,
        tone: 'from-emerald-500 to-teal-500',
    },
    {
        title: 'Barcha modullarni yechganlar',
        count: props.studentPopulationStats.solvedAllModulesCount,
        percentage: props.studentPopulationStats.solvedAllModulesPercentage,
        tone: 'from-amber-500 to-orange-500',
    },
];
</script>
<template>
    <Head title="Dashboard" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-3">
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-800">
                    <div class="flex items-center gap-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-blue-50 text-blue-600 dark:bg-blue-900/20 dark:text-blue-400">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Jami test savollari</p>
                            <h3 class="text-2xl font-bold text-slate-900 dark:text-slate-100">{{ props.testsCount }}</h3>
                        </div>
                    </div>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-800">
                    <div class="flex items-center gap-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-900/20 dark:text-emerald-400">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 00-.491 6.347A48.62 48.62 0 015.824 20.75m12.07-5.272a48.882 48.882 0 013.318.612l.45-2.25m-5.657.134a48.879 48.879 0 013.282-.643m0 .419a24.442 24.442 0 015.572 0m0 0a24.482 24.482 0 013.917.811M6.134 6.75a48.868 48.868 0 015.064 0l1.969-3.94a24.467 24.467 0 00-5.064 0l-1.969 3.94z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Jami modullar</p>
                            <h3 class="text-2xl font-bold text-slate-900 dark:text-slate-100">{{ props.modulesCount }}</h3>
                        </div>
                    </div>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-800">
                    <div class="flex items-center gap-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-fuchsia-50 text-fuchsia-600 dark:bg-fuchsia-900/20 dark:text-fuchsia-400">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.552-.37-6.624-1.056v-.106a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Bakalavr kunduzgi jami talaba</p>
                            <h3 class="text-2xl font-bold text-slate-900 dark:text-slate-100">{{ props.studentPopulationStats.totalStudents }}</h3>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
                <div
                    v-for="item in completionStats"
                    :key="item.title"
                    class="relative overflow-hidden rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-800"
                >
                    <div :class="`absolute inset-x-0 top-0 h-1.5 bg-gradient-to-r ${item.tone}`" />
                    <p class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ item.title }}</p>
                    <div class="mt-4 flex items-end justify-between gap-4">
                        <div>
                            <h3 class="text-3xl font-bold text-slate-900 dark:text-slate-100">{{ item.percentage }}%</h3>
                            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">{{ item.count }} ta talaba</p>
                        </div>
                        <div class="rounded-2xl bg-slate-100 px-4 py-3 text-right dark:bg-slate-900/70">
                            <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Bazasi</p>
                            <p class="text-lg font-semibold text-slate-700 dark:text-slate-200">
                                {{ props.studentPopulationStats.totalStudents }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <ModuleStatsChart v-if="props.moduleStats && props.moduleStats.length > 0" :module-stats="props.moduleStats" />
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <ResultCategoryChart v-if="props.resultCategoryStats && props.resultCategoryStats.length > 0" :result-category-stats="props.resultCategoryStats" />
                <CategoryStudentChart v-if="props.categoryStudentStats && props.categoryStudentStats.length > 0" :category-student-stats="props.categoryStudentStats" />
            </div>
            <FaculityStudentChart
                v-if="props.faculityStudentStats && props.faculityStudentStats.length > 0"
                :faculity-student-stats="props.faculityStudentStats"
            />
        </div>
    </AppLayout>
</template>
