<script setup lang="ts">
import ActivityLogsTable from '@/components/admin/ActivityLogsTable.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';

type ActivityLogItem = {
    id: number;
    log_name: string | null;
    description: string;
    event: string | null;
    subject_type: string | null;
    subject_id: number | null;
    causer_name: string;
    causer_id: number | null;
    created_at: string | null;
    properties: Record<string, unknown> | null;
};

type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

defineProps<{
    logs: {
        data: ActivityLogItem[];
        links: PaginationLink[];
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Activity Logs',
        href: '/admin/activity-logs',
    },
];
</script>

<template>
    <Head title="Activity Logs" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-2 sm:p-4">
            <div class="flex items-center justify-between">
                <h1 class="text-xl sm:text-2xl font-bold tracking-tight">Activity Logs</h1>
            </div>

            <ActivityLogsTable :items="logs.data" :links="logs.links" />
        </div>
    </AppLayout>
</template>
