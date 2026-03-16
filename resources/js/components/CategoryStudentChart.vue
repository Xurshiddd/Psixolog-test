<script setup lang="ts">
import { computed } from 'vue';
import { Doughnut } from 'vue-chartjs';
import { Chart as ChartJS, ArcElement, Tooltip, Legend, Title } from 'chart.js';

ChartJS.register(ArcElement, Tooltip, Legend, Title);

interface CategoryStudentStatItem {
    name: string;
    studentCount: number;
}

const props = defineProps<{ categoryStudentStats: CategoryStudentStatItem[] }>();

const chartData = computed(() => ({
    labels: props.categoryStudentStats.map(m => m.name),
    datasets: [
        {
            label: 'Talabalar soni',
            backgroundColor: [
                '#3b82f6', // blue
                '#10b981', // emerald
                '#f59e0b', // amber
                '#ef4444', // red
                '#8b5cf6', // violet
                '#ec4899', // pink
                '#06b6d4', // cyan
                '#f97316', // orange
            ],
            data: props.categoryStudentStats.map(m => m.studentCount),
            borderWidth: 1,
        }
    ]
}));

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            display: true,
            position: 'bottom' as const,
            labels: {
                font: {
                    size: 12,
                    weight: 500 as const
                },
                padding: 15,
                usePointStyle: true,
            }
        },
        title: {
            display: true,
            text: 'Kategoriyalar bo\'yicha talabalar',
            font: {
                size: 16,
                weight: 'bold' as any
            }
        }
    }
};
</script>

<template>
    <div class="rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 shadow-sm p-6">
        <div class="h-96 w-full">
            <Doughnut :data="chartData" :options="chartOptions" />
        </div>
    </div>
</template>
