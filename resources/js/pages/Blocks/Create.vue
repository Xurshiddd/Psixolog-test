<script setup lang="ts">
import BlockForm from '@/components/BlockForm.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';

defineProps<{ availableModules: Array<{ id: number; name: string; is_active: boolean }> }>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Bloklar', href: '/blocks' },
    { title: 'Blok yaratish', href: '/blocks/create' },
];

const form = useForm({
    name: '',
    description: '',
    is_active: true,
    modules: [] as number[],
});

const submit = () => form.post('/blocks');
</script>

<template>
    <Head title="Blok yaratish" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-screen bg-slate-50 dark:bg-slate-900">
            <BlockForm
                :available-modules="availableModules"
                :model-value="form"
                :errors="form.errors as Record<string, string>"
                :processing="form.processing"
                submit-label="Blokni saqlash"
                @submit="submit"
            />
        </div>
    </AppLayout>
</template>
