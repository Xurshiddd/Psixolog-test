<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

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
    items: ActivityLogItem[];
    links: PaginationLink[];
}>();

const formatDate = (value: string | null): string => {
    if (!value) return '-';
    return new Date(value).toLocaleString();
};

const toPrettyJson = (value: Record<string, unknown> | null): string => {
    if (!value || Object.keys(value).length === 0) return '-';
    return JSON.stringify(value, null, 2);
};
</script>

<template>
    <div class="overflow-x-auto rounded-lg border">
        <table class="min-w-full text-sm">
            <thead class="bg-muted/50">
                <tr>
                    <th class="px-3 py-2 text-left font-semibold">ID</th>
                    <th class="px-3 py-2 text-left font-semibold">Event</th>
                    <th class="px-3 py-2 text-left font-semibold">Model</th>
                    <th class="px-3 py-2 text-left font-semibold">Causer</th>
                    <th class="px-3 py-2 text-left font-semibold">Description</th>
                    <th class="px-3 py-2 text-left font-semibold">Date</th>
                    <th class="px-3 py-2 text-left font-semibold">Properties</th>
                </tr>
            </thead>
            <tbody>
                <tr
                    v-for="log in items"
                    :key="log.id"
                    class="border-t align-top"
                >
                    <td class="px-3 py-2">{{ log.id }}</td>
                    <td class="px-3 py-2">
                        {{ log.event || log.log_name || '-' }}
                    </td>
                    <td class="px-3 py-2">
                        {{ log.subject_type || '-' }} #{{ log.subject_id || '-' }}
                    </td>
                    <td class="px-3 py-2">
                        {{ log.causer_name }} #{{ log.causer_id || '-' }}
                    </td>
                    <td class="px-3 py-2">{{ log.description }}</td>
                    <td class="px-3 py-2 whitespace-nowrap">{{ formatDate(log.created_at) }}</td>
                    <td class="px-3 py-2">
                        <pre class="max-w-lg overflow-x-auto rounded bg-muted p-2 text-xs">{{ toPrettyJson(log.properties) }}</pre>
                    </td>
                </tr>
                <tr v-if="items.length === 0">
                    <td colspan="7" class="px-3 py-6 text-center text-muted-foreground">
                        No activity logs found.
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="mt-4 flex flex-wrap gap-2">
        <Link
            v-for="(link, idx) in links"
            :key="`${idx}-${link.label}`"
            :href="link.url || ''"
            :class="[
                'rounded border px-3 py-1 text-sm',
                link.active ? 'bg-primary text-primary-foreground border-primary' : 'bg-background',
                !link.url ? 'pointer-events-none opacity-50' : '',
            ]"
            v-html="link.label"
        />
    </div>
</template>
