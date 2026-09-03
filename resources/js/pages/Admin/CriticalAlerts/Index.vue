<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

type Alert = {
    id: number;
    module_name: string;
    question: string;
    answer: string;
    created_at: string | null;
    resolved_at: string | null;
    resolved_by: string | null;
};

type AlertStudent = {
    id: number;
    name: string;
    login: string | number | null;
    phone: string | null;
    group_name: string;
    faculity_name: string;
    pending_alerts_count: number;
    resolved_alerts_count: number;
    last_alert_at: string | null;
    alerts: Alert[];
};

const props = defineProps<{
    students: { data: AlertStudent[]; links: any[]; from: number | null; to: number | null; total: number; last_page: number };
    filters: { status: string };
    pendingStudentsCount: number;
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Zudlik bilan', href: '/critical-alerts' }];

const expanded = ref<number | null>(null);
const resolvingId = ref<number | null>(null);

const toggle = (studentId: number) => {
    expanded.value = expanded.value === studentId ? null : studentId;
};

const resolve = (student: AlertStudent) => {
    if (!confirm(`${student.name} bo'yicha barcha holatlar "Hal qilindi" deb belgilansinmi?`)) {
        return;
    }

    resolvingId.value = student.id;
    router.post(
        `/critical-alerts/${student.id}/resolve`,
        {},
        { preserveScroll: true, onFinish: () => (resolvingId.value = null) },
    );
};

const setStatus = (status: string) => {
    router.get('/critical-alerts', status === 'pending' ? {} : { status }, {
        preserveScroll: true,
        replace: true,
    });
};

const formatDate = (value: string | null) => (value ? new Date(value).toLocaleString() : '-');
</script>

<template>
    <Head title="Zudlik bilan" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-3">
                    <span
                        class="flex h-11 w-11 items-center justify-center rounded-xl"
                        :class="
                            pendingStudentsCount > 0
                                ? 'bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400'
                                : 'bg-emerald-100 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400'
                        "
                    >
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
                        </svg>
                    </span>
                    <div>
                        <h1 class="text-lg font-semibold text-slate-900 dark:text-slate-100">
                            Zudlik bilan ish olib boriladiganlar
                        </h1>
                        <p class="text-sm text-slate-500 dark:text-slate-400">
                            Xavfli deb belgilangan variantni tanlagan talabalar:
                            <span class="font-semibold" :class="pendingStudentsCount > 0 ? 'text-red-600' : 'text-emerald-600'">
                                {{ pendingStudentsCount }}
                            </span>
                            ta
                        </p>
                    </div>
                </div>

                <div class="inline-flex rounded-lg border border-slate-300 p-1 dark:border-slate-600">
                    <button
                        type="button"
                        class="rounded-md px-3 py-1.5 text-sm font-medium transition"
                        :class="filters.status === 'pending' ? 'bg-red-600 text-white' : 'text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-700'"
                        @click="setStatus('pending')"
                    >
                        Hal qilinmagan
                    </button>
                    <button
                        type="button"
                        class="rounded-md px-3 py-1.5 text-sm font-medium transition"
                        :class="filters.status === 'all' ? 'bg-slate-700 text-white' : 'text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-700'"
                        @click="setStatus('all')"
                    >
                        Barchasi
                    </button>
                </div>
            </div>

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100 text-sm dark:divide-slate-700">
                        <thead class="bg-slate-50 dark:bg-slate-900/60">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-slate-700 dark:text-slate-200">Talaba</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-700 dark:text-slate-200">Login</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-700 dark:text-slate-200">Fakultet</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-700 dark:text-slate-200">Guruh</th>
                                <th class="px-4 py-3 text-center font-semibold text-slate-700 dark:text-slate-200">Holatlar</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-700 dark:text-slate-200">Oxirgi</th>
                                <th class="px-4 py-3 text-center font-semibold text-slate-700 dark:text-slate-200">Amal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                            <tr v-if="students.data.length === 0">
                                <td colspan="7" class="px-4 py-10 text-center text-slate-500 dark:text-slate-400">
                                    Zudlik bilan ish olib boriladigan holat yo'q.
                                </td>
                            </tr>

                            <template v-for="student in students.data" :key="student.id">
                                <tr
                                    class="cursor-pointer transition-colors hover:bg-slate-50 dark:hover:bg-slate-700/50"
                                    @click="toggle(student.id)"
                                >
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-2">
                                            <span
                                                v-if="student.pending_alerts_count > 0"
                                                class="h-2.5 w-2.5 shrink-0 rounded-full bg-red-500"
                                                title="Hal qilinmagan"
                                            />
                                            <span class="font-medium text-slate-900 dark:text-slate-100">{{ student.name }}</span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-slate-700 dark:text-slate-200">{{ student.login ?? '-' }}</td>
                                    <td class="px-4 py-3 text-slate-700 dark:text-slate-200">{{ student.faculity_name }}</td>
                                    <td class="px-4 py-3 text-slate-700 dark:text-slate-200">{{ student.group_name }}</td>
                                    <td class="px-4 py-3 text-center">
                                        <span
                                            v-if="student.pending_alerts_count > 0"
                                            class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-1 text-xs font-semibold text-red-700 dark:bg-red-900/30 dark:text-red-400"
                                        >
                                            {{ student.pending_alerts_count }} ta
                                        </span>
                                        <span
                                            v-else
                                            class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400"
                                        >
                                            Hal qilindi
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ formatDate(student.last_alert_at) }}</td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center justify-center gap-2" @click.stop>
                                            <button
                                                v-if="student.pending_alerts_count > 0"
                                                type="button"
                                                :disabled="resolvingId === student.id"
                                                class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-emerald-700 disabled:opacity-60"
                                                @click="resolve(student)"
                                            >
                                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                                </svg>
                                                {{ resolvingId === student.id ? '...' : 'Hal qilindi' }}
                                            </button>
                                            <span v-else class="text-xs font-medium text-emerald-600 dark:text-emerald-400">✓ Hal qilindi</span>

                                            <Link
                                                :href="`/admin/students/${student.id}`"
                                                class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-700 transition hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-700/50"
                                            >
                                                Profil
                                            </Link>
                                        </div>
                                    </td>
                                </tr>

                                <tr v-if="expanded === student.id" class="bg-slate-50 dark:bg-slate-900/40">
                                    <td colspan="7" class="px-6 py-4">
                                        <p class="mb-3 text-xs font-semibold tracking-wide text-slate-500 uppercase dark:text-slate-400">
                                            Xavfli javoblar
                                        </p>
                                        <ul class="space-y-3">
                                            <li
                                                v-for="alert in student.alerts"
                                                :key="alert.id"
                                                class="rounded-xl border border-slate-200 bg-white p-3 dark:border-slate-700 dark:bg-slate-800"
                                            >
                                                <p class="text-xs font-medium text-blue-600 dark:text-blue-400">{{ alert.module_name }}</p>
                                                <p class="mt-1 text-sm text-slate-800 dark:text-slate-200">{{ alert.question }}</p>
                                                <p class="mt-1 text-sm">
                                                    <span class="text-slate-500 dark:text-slate-400">Javob:</span>
                                                    <span class="font-medium text-red-600 dark:text-red-400">{{ alert.answer }}</span>
                                                </p>
                                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                                    {{ formatDate(alert.created_at) }}
                                                    <template v-if="alert.resolved_at">
                                                        · hal qilindi: {{ formatDate(alert.resolved_at) }}
                                                        <template v-if="alert.resolved_by"> ({{ alert.resolved_by }})</template>
                                                    </template>
                                                </p>
                                            </li>
                                            <li v-if="student.alerts.length === 0" class="text-sm text-slate-500 dark:text-slate-400">
                                                Ma'lumot yo'q.
                                            </li>
                                        </ul>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <div
                    v-if="students.last_page > 1"
                    class="flex flex-wrap items-center justify-between gap-2 border-t border-slate-100 bg-slate-50 px-4 py-3 dark:border-slate-700 dark:bg-slate-900/50"
                >
                    <span class="text-sm text-slate-600 dark:text-slate-400">
                        {{ students.from }}–{{ students.to }} / {{ students.total }}
                    </span>
                    <div class="flex flex-wrap gap-1">
                        <template v-for="link in students.links" :key="link.label + (link.url ?? '')">
                            <span
                                v-if="!link.url"
                                class="rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-400 dark:border-slate-700"
                                v-html="link.label"
                            />
                            <Link
                                v-else
                                :href="link.url"
                                class="rounded-lg border px-3 py-2 text-sm transition"
                                :class="
                                    link.active
                                        ? 'border-blue-600 bg-blue-600 text-white'
                                        : 'border-slate-200 text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-700/50'
                                "
                                v-html="link.label"
                            />
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
