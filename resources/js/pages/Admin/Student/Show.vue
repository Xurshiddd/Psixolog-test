<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';

const props = defineProps<{
    student: any;
    results: Array<any>;
    filters?: {
        group_id?: string | null;
        speciality_id?: string | null;
        test_status?: string | null;
    };
    page?: number;
}>();

const getBackLink = () => {
    const params = new URLSearchParams();
    if (props.filters?.group_id && props.filters.group_id !== 'null') {
        params.append('group_id', props.filters.group_id);
    }
    if (props.filters?.speciality_id && props.filters.speciality_id !== 'null') {
        params.append('speciality_id', props.filters.speciality_id);
    }
    if (props.filters?.test_status && props.filters.test_status !== 'null') {
        params.append('test_status', props.filters.test_status);
    }
    if (props.page && props.page > 1) {
        params.append('page', String(props.page));
    }
    const queryString = params.toString();
    return `/admin/students${queryString ? '?' + queryString : ''}`;
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Talabalar',
        href: getBackLink(),
    },
    {
        title: props.student.name,
        href: `/admin/students/${props.student.id}`,
    },
];

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString();
};
</script>

<template>
    <Head :title="student.name" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold tracking-tight">{{ student.name }}</h1>
                <Link :href="getBackLink()" class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-9 px-4 py-2">
                    ← Orqaga
                </Link>
            </div>
            
            <div class="grid gap-6 md:grid-cols-2">
                <div class="rounded-lg border bg-card p-6 shadow-sm">
                    <h2 class="text-lg font-semibold mb-4">Ma'lumotlar</h2>
                    <div class="space-y-2">
                        <div class="flex justify-between border-b pb-2">
                            <span class="text-muted-foreground">Login:</span>
                            <span>{{ student.login }}</span>
                        </div>
                        <div class="flex justify-between border-b pb-2">
                            <span class="text-muted-foreground">Telefon:</span>
                            <span>{{ student.phone }}</span>
                        </div>
                        <div class="flex justify-between border-b pb-2">
                            <span class="text-muted-foreground">Guruh:</span>
                            <span>{{ student.group?.name || '-' }}</span>
                        </div>
                        <div class="flex justify-between border-b pb-2">
                            <span class="text-muted-foreground">Yo'nalish:</span>
                            <span>{{ student.speciality?.name || '-' }}</span>
                        </div>
                    </div>
                </div>

                <div class="rounded-lg border bg-card p-6 shadow-sm">
                    <h2 class="text-lg font-semibold mb-4">Topshirilgan Testlar</h2>
                    <div class="relative w-full overflow-auto">
                        <table class="w-full caption-bottom text-sm">
                            <thead class="[&_tr]:border-b">
                                <tr class="border-b">
                                    <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Modul</th>
                                    <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Natija (Real)</th>
                                    <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Amallar</th>
                                </tr>
                            </thead>
                            <tbody class="[&_tr:last-child]:border-0">
                                <tr v-for="result in results" :key="result.id" class="border-b">
                                    <td class="p-4 align-middle">{{ result.name }}</td>
                                    <td class="p-4 align-middle">{{ result.pivot.result_real ? 'Ha' : 'Yo\'q' }}</td>
                                    <td class="p-4 align-middle">
                                        <Link 
                                            :href="`/admin/students/${student.id}/results/${result.id}`" 
                                            class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-secondary text-secondary-foreground hover:bg-secondary/80 h-9 px-4 py-2"
                                        >
                                            Natijani ko'rish
                                        </Link>
                                    </td>
                                </tr>
                                <tr v-if="results.length === 0">
                                    <td colspan="3" class="p-4 text-center text-muted-foreground">Hali testlar yechilmagan.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
