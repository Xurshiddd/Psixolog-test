<script setup lang="ts">
import { computed } from 'vue';
import { Bar } from 'vue-chartjs';
import { Chart as ChartJS, CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend } from 'chart.js';

ChartJS.register(CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend);

interface FaculityStudentStatItem {
    name: string;
    studentCount: number;
}

const props = defineProps<{ faculityStudentStats: FaculityStudentStatItem[] }>();

const chartData = computed(() => ({
    labels: props.faculityStudentStats.map((item) => item.name),
    datasets: [
        {
            label: 'Talabalar soni',
            backgroundColor: '#14b8a6',
            borderColor: '#0f766e',
            borderWidth: 2,
            data: props.faculityStudentStats.map((item) => item.studentCount),
            borderRadius: 8,
            borderSkipped: false,
        },
    ],
}));

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            display: true,
            position: 'top' as const,
            labels: {
                font: {
                    size: 12,
                    weight: 500 as const,
                },
                padding: 15,
                usePointStyle: true,
            },
        },
        title: {
            display: false,
        },
    },
    scales: {
        y: {
            beginAtZero: true,
            ticks: {
                stepSize: 1,
                font: {
                    size: 11,
                },
            },
            title: {
                display: true,
                text: 'Talaba soni',
                font: {
                    size: 12,
                    weight: 600 as const,
                },
            },
            grid: {
                drawBorder: true,
                color: 'rgba(0, 0, 0, 0.1)',
            },
        },
        x: {
            ticks: {
                font: {
                    size: 11,
                },
            },
            title: {
                display: true,
                text: 'Fakultetlar',
                font: {
                    size: 12,
                    weight: 600 as const,
                },
            },
            grid: {
                display: false,
            },
        },
    },
};
</script>

<template>
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-800">
        <h3 class="mb-6 text-lg font-semibold text-slate-900 dark:text-slate-100">Fakultetlar bo'yicha talabalar</h3>
        <div class="h-96 w-full">
            <Bar :data="chartData" :options="chartOptions" />
        </div>
    </div>
</template>
