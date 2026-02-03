<script setup lang="ts">
import AppStudentSidebarLayout from '@/layouts/app/AppStudentSidebarLayout.vue';
import type { BreadcrumbItem } from '@/types';

type Props = {
    breadcrumbs?: BreadcrumbItem[];
};

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});
</script>

<template>
    <AppStudentSidebarLayout :breadcrumbs="breadcrumbs">
        <slot />
    </AppStudentSidebarLayout>
</template>
