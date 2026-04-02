<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
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

interface DashboardModule {
    id: number;
    name: string;
}

interface ModuleScoreReportStudent {
    id: number;
    login: string;
    name: string;
    faculity_name: string;
    group_name: string;
    level: string;
    score: number;
}

interface PaginatedData<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: Array<{
        url: string | null;
        label: string;
        active: boolean;
    }>;
}

const props = defineProps<{ 
    tests: any; 
    testsCount: number; 
    modules: DashboardModule[];
    modulesCount: number;
    moduleStats?: ModuleStatItem[];
    resultCategoryStats?: ResultCategoryStatItem[];
    categoryStudentStats?: CategoryStudentStatItem[];
    faculityStudentStats?: FaculityStudentStatItem[];
    studentPopulationStats: StudentPopulationStats;
    reportFilters: {
        module_id?: number | null;
        min_score?: number | null;
        max_score?: number | null;
        is_ready?: boolean;
    };
    moduleScoreReport?: PaginatedData<ModuleScoreReportStudent> | null;
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

const selectedModuleId = ref(props.reportFilters.module_id ? String(props.reportFilters.module_id) : '');
const minScore = ref(
    props.reportFilters.min_score !== null && typeof props.reportFilters.min_score !== 'undefined'
        ? String(props.reportFilters.min_score)
        : '',
);
const maxScore = ref(
    props.reportFilters.max_score !== null && typeof props.reportFilters.max_score !== 'undefined'
        ? String(props.reportFilters.max_score)
        : '',
);

const showScoreInputs = computed(() => selectedModuleId.value !== '');
const normalizedMinScore = computed(() => (minScore.value === '' ? null : Number(minScore.value)));
const normalizedMaxScore = computed(() => (maxScore.value === '' ? null : Number(maxScore.value)));

const canSubmitReport = computed(() => {
    if (!selectedModuleId.value || normalizedMinScore.value === null || normalizedMaxScore.value === null) {
        return false;
    }

    return normalizedMinScore.value >= 0 && normalizedMaxScore.value >= 0 && normalizedMinScore.value <= normalizedMaxScore.value;
});

const selectedModuleName = computed(
    () => props.modules.find((module) => String(module.id) === selectedModuleId.value)?.name ?? '',
);

const buildReportParams = () => ({
    report_module_id: selectedModuleId.value,
    min_score: minScore.value,
    max_score: maxScore.value,
});

const submitReport = () => {
    if (!canSubmitReport.value) {
        return;
    }

    router.get(dashboard().url, buildReportParams(), {
        preserveScroll: true,
        replace: true,
    });
};

const resetReport = () => {
    selectedModuleId.value = '';
    minScore.value = '';
    maxScore.value = '';

    router.get(dashboard().url, {}, {
        preserveScroll: true,
        replace: true,
    });
};

const downloadReportExcel = () => {
    if (!canSubmitReport.value) {
        return;
    }

    const params = new URLSearchParams(buildReportParams());
    window.location.href = `/dashboard/module-score-report/export?${params.toString()}`;
};
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

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-800">
                <div class="flex flex-col gap-5">
                    <div class="flex flex-col gap-2 lg:flex-row lg:items-start lg:justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Modul bo‘yicha ball oralig‘i natijalari</h3>
                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                Ball `Student/Result` sahifasidagi `Jami` formulasi bilan bir xil hisoblanadi.
                            </p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <button
                                type="button"
                                class="inline-flex items-center justify-center rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-700/50"
                                :disabled="!canSubmitReport"
                                @click="downloadReportExcel"
                            >
                                Excel yuklab olish
                            </button>
                            <button
                                type="button"
                                class="inline-flex items-center justify-center rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-700/50"
                                @click="resetReport"
                            >
                                Tozalash
                            </button>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 lg:grid-cols-4">
                        <div>
                            <label for="module-score-report-module" class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-200">
                                Modul
                            </label>
                            <select
                                id="module-score-report-module"
                                v-model="selectedModuleId"
                                class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-blue-500 focus:outline-none dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100"
                            >
                                <option value="">Modulni tanlang</option>
                                <option v-for="module in props.modules" :key="module.id" :value="String(module.id)">
                                    {{ module.name }}
                                </option>
                            </select>
                        </div>

                        <div v-if="showScoreInputs">
                            <label for="module-score-report-min" class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-200">
                                Necha balldan yuqori
                            </label>
                            <input
                                id="module-score-report-min"
                                v-model="minScore"
                                type="number"
                                min="0"
                                class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-blue-500 focus:outline-none dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100"
                                placeholder="Masalan, 10"
                            />
                        </div>

                        <div v-if="showScoreInputs">
                            <label for="module-score-report-max" class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-200">
                                Nechi ballgacha
                            </label>
                            <input
                                id="module-score-report-max"
                                v-model="maxScore"
                                type="number"
                                min="0"
                                class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-blue-500 focus:outline-none dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100"
                                placeholder="Masalan, 20"
                            />
                        </div>

                        <div v-if="showScoreInputs" class="flex items-end">
                            <button
                                type="button"
                                class="inline-flex w-full items-center justify-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:bg-slate-400"
                                :disabled="!canSubmitReport"
                                @click="submitReport"
                            >
                                Natija olish
                            </button>
                        </div>
                    </div>

                    <p v-if="showScoreInputs && !canSubmitReport" class="text-sm text-amber-600 dark:text-amber-400">
                        Ball oralig‘ini to‘liq kiriting. Minimal ball maksimal balldan katta bo‘lmasligi kerak.
                    </p>

                    <div v-if="props.reportFilters.is_ready" class="rounded-xl border border-slate-200 dark:border-slate-700">
                        <div class="flex flex-col gap-2 border-b border-slate-200 px-4 py-3 text-sm text-slate-600 dark:border-slate-700 dark:text-slate-300 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <span class="font-medium text-slate-900 dark:text-slate-100">{{ selectedModuleName }}</span>
                                uchun
                                <span class="font-medium">{{ props.reportFilters.min_score }}</span>
                                dan
                                <span class="font-medium">{{ props.reportFilters.max_score }}</span>
                                gacha bo‘lgan natijalar
                            </div>
                            <div>
                                Jami: <span class="font-semibold text-slate-900 dark:text-slate-100">{{ props.moduleScoreReport?.total ?? 0 }}</span> ta talaba
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-700">
                                <thead class="bg-slate-50 dark:bg-slate-900/60">
                                    <tr>
                                        <th class="px-4 py-3 text-left font-semibold text-slate-700 dark:text-slate-200">Login</th>
                                        <th class="px-4 py-3 text-left font-semibold text-slate-700 dark:text-slate-200">Ism Familiya</th>
                                        <th class="px-4 py-3 text-left font-semibold text-slate-700 dark:text-slate-200">Fakultet</th>
                                        <th class="px-4 py-3 text-left font-semibold text-slate-700 dark:text-slate-200">Guruh</th>
                                        <th class="px-4 py-3 text-left font-semibold text-slate-700 dark:text-slate-200">Kurs</th>
                                        <th class="px-4 py-3 text-left font-semibold text-slate-700 dark:text-slate-200">Ball</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200 bg-white dark:divide-slate-700 dark:bg-slate-800">
                                    <tr v-if="!props.moduleScoreReport || props.moduleScoreReport.data.length === 0">
                                        <td colspan="6" class="px-4 py-6 text-center text-slate-500 dark:text-slate-400">
                                            Berilgan parametr bo‘yicha talaba topilmadi.
                                        </td>
                                    </tr>
                                    <tr v-for="student in props.moduleScoreReport?.data ?? []" :key="student.id">
                                        <td class="px-4 py-3 text-slate-700 dark:text-slate-200">{{ student.login }}</td>
                                        <td class="px-4 py-3 text-slate-700 dark:text-slate-200">{{ student.name }}</td>
                                        <td class="px-4 py-3 text-slate-700 dark:text-slate-200">{{ student.faculity_name }}</td>
                                        <td class="px-4 py-3 text-slate-700 dark:text-slate-200">{{ student.group_name }}</td>
                                        <td class="px-4 py-3 text-slate-700 dark:text-slate-200">{{ student.level }}</td>
                                        <td class="px-4 py-3 font-semibold text-slate-900 dark:text-slate-100">{{ student.score }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div
                            v-if="props.moduleScoreReport && props.moduleScoreReport.last_page > 1"
                            class="flex flex-wrap items-center justify-end gap-2 border-t border-slate-200 px-4 py-3 dark:border-slate-700"
                        >
                            <template v-for="link in props.moduleScoreReport.links" :key="link.label + (link.url ?? 'null')">
                                <span
                                    v-if="!link.url"
                                    class="rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-400 dark:border-slate-700"
                                    v-html="link.label"
                                />
                                <Link
                                    v-else
                                    :href="link.url"
                                    class="rounded-lg border px-3 py-2 text-sm transition"
                                    :class="link.active
                                        ? 'border-blue-600 bg-blue-600 text-white'
                                        : 'border-slate-200 text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-700/50'"
                                    v-html="link.label"
                                />
                            </template>
                        </div>
                    </div>

                    <div v-else class="rounded-xl border border-dashed border-slate-300 px-4 py-8 text-center text-sm text-slate-500 dark:border-slate-600 dark:text-slate-400">
                        Modulni tanlang, ball oralig‘ini kiriting va `Natija olish` tugmasini bosing.
                    </div>
                </div>
            </div>

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
