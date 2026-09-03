<script setup lang="ts">
import { ArcElement, Chart as ChartJS, Legend, Title, Tooltip } from 'chart.js';
import { computed } from 'vue';
import { Doughnut } from 'vue-chartjs';

ChartJS.register(ArcElement, Tooltip, Legend, Title);

/**
 * Bayroqlar bo'yicha talabalar statistikasi — kategoriyalar statistikasi
 * yonida turadi. Rang bayroq rangi bilan bir xil.
 */
interface FlagStudentStatItem {
    value: string;
    name: string;
    color: string;
    studentCount: number;
}

const props = defineProps<{ flagStudentStats: FlagStudentStatItem[] }>();

const total = computed(() => props.flagStudentStats.reduce((sum, item) => sum + item.studentCount, 0));

const chartData = computed(() => ({
    labels: props.flagStudentStats.map((item) => item.name),
    datasets: [
        {
            label: 'Talabalar soni',
            backgroundColor: props.flagStudentStats.map((item) => item.color),
            data: props.flagStudentStats.map((item) => item.studentCount),
            borderWidth: 1,
        },
    ],
}));

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            display: true,
            position: 'bottom' as const,
            labels: {
                font: { size: 12, weight: 500 as const },
                padding: 15,
                usePointStyle: true,
            },
        },
        title: {
            display: true,
            text: 'Bayroqlar bo‘yicha talabalar',
            font: { size: 16, weight: 'bold' as any },
        },
    },
};
</script>

<template>
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-800">
        <div v-if="total > 0" class="h-96 w-full">
            <Doughnut :data="chartData" :options="chartOptions" />
        </div>

        <div v-else class="flex h-96 flex-col items-center justify-center gap-2 text-center">
            <h3 class="text-base font-semibold text-slate-900 dark:text-slate-100">Bayroqlar bo‘yicha talabalar</h3>
            <p class="max-w-xs text-sm text-slate-500 dark:text-slate-400">
                Hali bayroq biriktirilmagan. "Modul bo‘yicha ball oralig‘i natijalari" bo‘limidan bayroq tanlang.
            </p>
        </div>

        <div class="mt-4 grid grid-cols-3 gap-3 border-t border-slate-100 pt-4 dark:border-slate-700">
            <div v-for="item in flagStudentStats" :key="item.value" class="text-center">
                <span
                    class="mx-auto mb-1.5 block h-3 w-3 rounded-full"
                    :style="{ backgroundColor: item.color }"
                    :aria-label="item.name"
                />
                <p class="text-lg font-bold text-slate-900 dark:text-slate-100">{{ item.studentCount }}</p>
                <p class="text-xs text-slate-500 dark:text-slate-400">{{ item.name }}</p>
            </div>
        </div>
    </div>
</template>
