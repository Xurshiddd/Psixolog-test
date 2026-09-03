<script setup lang="ts">
import BlockForm from '@/components/BlockForm.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps<{
    block: {
        id: number;
        name: string;
        description: string | null;
        is_active: boolean;
        modules: Array<{ id: number; name: string }>;
    };
    availableModules: Array<{ id: number; name: string; is_active: boolean }>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Bloklar', href: '/blocks' },
    { title: props.block.name, href: `/blocks/${props.block.id}/edit` },
];

const form = useForm({
    name: props.block.name,
    description: props.block.description ?? '',
    is_active: props.block.is_active,
    modules: props.block.modules.map((module) => module.id),
});

const submit = () => form.put(`/blocks/${props.block.id}`);
</script>

<template>
    <Head :title="`${block.name} — blokni tahrirlash`" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-screen bg-slate-50 dark:bg-slate-900">
            <BlockForm
                :available-modules="availableModules"
                :model-value="form"
                :errors="form.errors as Record<string, string>"
                :processing="form.processing"
                submit-label="O'zgarishlarni saqlash"
                @submit="submit"
            />
        </div>
    </AppLayout>
</template>
