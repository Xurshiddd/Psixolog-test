<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { ref } from 'vue';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog';
import {
  Button
} from '@/components/ui/button';

interface PaginatedData<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: Array<{
        url: string | null;
        label: string;
        active: boolean;
    }>;
}

const props = defineProps<{
    students: PaginatedData<any>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Talabalar',
        href: '/admin/students',
    },
];

const selectedStudent = ref<any>(null);

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString();
};

const openDiagnosisModal = (student: any) => {
    selectedStudent.value = student;
};
</script>

<template>
    <Head title="Talabalar" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-2 sm:p-4">
            <div class="flex items-center justify-between">
                <h1 class="text-xl sm:text-2xl font-bold tracking-tight">Talabalar</h1>
            </div>

            <!-- Desktop Table View -->
            <div class="hidden md:block rounded-md border bg-card text-card-foreground shadow-sm">
                <div class="relative w-full overflow-auto">
                    <table class="w-full caption-bottom text-sm">
                        <thead class="[&_tr]:border-b">
                            <tr class="border-b transition-colors hover:bg-muted/50 data-[state=selected]:bg-muted">
                                <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground [&:has([role=checkbox])]:pr-0">
                                    Ism Familiya
                                </th>
                                <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground [&:has([role=checkbox])]:pr-0">
                                    Login
                                </th>
                                <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground [&:has([role=checkbox])]:pr-0">
                                    Telefon
                                </th>
                                <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground [&:has([role=checkbox])]:pr-0">
                                    Guruh
                                </th>
                                <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground [&:has([role=checkbox])]:pr-0">
                                    Yo'nalish
                                </th>
                                <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground [&:has([role=checkbox])]:pr-0">
                                    Ro'yxatdan o'tgan sana
                                </th>
                                <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground [&:has([role=checkbox])]:pr-0">
                                    Xulosalar
                                </th>
                                <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground [&:has([role=checkbox])]:pr-0">
                                    Amallar
                                </th>
                            </tr>
                        </thead>
                        <tbody class="[&_tr:last-child]:border-0">
                            <tr v-for="student in students.data" :key="student.id" class="border-b transition-colors hover:bg-muted/50 data-[state=selected]:bg-muted">
                                <td class="p-4 align-middle [&:has([role=checkbox])]:pr-0 font-medium">
                                    {{ student.name }}
                                </td>
                                <td class="p-4 align-middle [&:has([role=checkbox])]:pr-0">
                                    {{ student.login }}
                                </td>
                                <td class="p-4 align-middle [&:has([role=checkbox])]:pr-0">
                                    {{ student.phone }}
                                </td>
                                <td class="p-4 align-middle [&:has([role=checkbox])]:pr-0">
                                    <span v-if="student.group">{{ student.group.name }}</span>
                                    <span v-else class="text-muted-foreground">-</span>
                                </td>
                                <td class="p-4 align-middle [&:has([role=checkbox])]:pr-0">
                                    <span v-if="student.speciality">{{ student.speciality.name }}</span>
                                    <span v-else class="text-muted-foreground">-</span>
                                </td>
                                <td class="p-4 align-middle [&:has([role=checkbox])]:pr-0">
                                    {{ formatDate(student.created_at) }}
                                </td>
                                <td class="p-4 align-middle [&:has([role=checkbox])]:pr-0">
                                    <Dialog v-if="student.users_tests_results && student.users_tests_results.length > 0">
                                        <DialogTrigger as-child>
                                            <Button variant="outline" size="sm" @click="openDiagnosisModal(student)">
                                                Xulosa
                                            </Button>
                                        </DialogTrigger>
                                        <DialogContent class="sm:max-w-[425px]">
                                            <DialogHeader>
                                                <DialogTitle>Diagnostika Xulosalari - {{ student.name }}</DialogTitle>
                                                <DialogDescription>
                                                    Talaba uchun yozilgan barcha diagnostika xulosalari.
                                                </DialogDescription>
                                            </DialogHeader>
                                            <div class="grid gap-4 py-4 max-h-[60vh] overflow-y-auto">
                                                <div v-for="result in student.users_tests_results" :key="result.id" class="border-b pb-4 last:border-0 last:pb-0">
                                                    <h4 class="font-semibold mb-2">Modul ID: {{ result.pivot.module_id }}</h4>
                                                    <p class="text-sm text-muted-foreground whitespace-pre-wrap">{{ result.pivot.result_real }}</p>
                                                </div>
                                            </div>
                                        </DialogContent>
                                    </Dialog>
                                    <span v-else class="text-muted-foreground text-sm">-</span>
                                </td>
                                <td class="p-4 align-middle [&:has([role=checkbox])]:pr-0">
                                    <Link :href="`/admin/students/${student.id}`" class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-primary text-primary-foreground hover:bg-primary/90 h-9 px-4 py-2">
                                        Ko'rish
                                    </Link>
                                </td>
                            </tr>
                            <tr v-if="students.data.length === 0">
                                <td colspan="8" class="p-4 align-middle text-center">
                                    Talabalar topilmadi.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Mobile Card View -->
            <div class="md:hidden space-y-4">
                <div v-for="student in students.data" :key="student.id" class="rounded-lg border bg-card text-card-foreground shadow-sm p-4">
                    <div class="space-y-3">
                        <div class="flex items-start justify-between">
                            <div>
                                <h3 class="font-semibold text-base">{{ student.name }}</h3>
                                <p class="text-sm text-muted-foreground">{{ student.login }}</p>
                            </div>
                            <Link :href="`/admin/students/${student.id}`" class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-primary text-primary-foreground hover:bg-primary/90 h-9 px-3 py-2">
                                Ko'rish
                            </Link>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-2 text-sm">
                            <div>
                                <span class="text-muted-foreground">Telefon:</span>
                                <p class="font-medium">{{ student.phone }}</p>
                            </div>
                            <div>
                                <span class="text-muted-foreground">Guruh:</span>
                                <p class="font-medium">
                                    <span v-if="student.group">{{ student.group.name }}</span>
                                    <span v-else>-</span>
                                </p>
                            </div>
                            <div>
                                <span class="text-muted-foreground">Yo'nalish:</span>
                                <p class="font-medium">
                                    <span v-if="student.speciality">{{ student.speciality.name }}</span>
                                    <span v-else>-</span>
                                </p>
                            </div>
                            <div>
                                <span class="text-muted-foreground">Sana:</span>
                                <p class="font-medium">{{ formatDate(student.created_at) }}</p>
                            </div>
                        </div>

                        <div v-if="student.users_tests_results && student.users_tests_results.length > 0" class="pt-2 border-t">
                            <Dialog>
                                <DialogTrigger as-child>
                                    <Button variant="outline" size="sm" class="w-full" @click="openDiagnosisModal(student)">
                                        Xulosalarni ko'rish
                                    </Button>
                                </DialogTrigger>
                                <DialogContent class="sm:max-w-[425px]">
                                    <DialogHeader>
                                        <DialogTitle>Diagnostika Xulosalari - {{ student.name }}</DialogTitle>
                                        <DialogDescription>
                                            Talaba uchun yozilgan barcha diagnostika xulosalari.
                                        </DialogDescription>
                                    </DialogHeader>
                                    <div class="grid gap-4 py-4 max-h-[60vh] overflow-y-auto">
                                        <div v-for="result in student.users_tests_results" :key="result.id" class="border-b pb-4 last:border-0 last:pb-0">
                                            <h4 class="font-semibold mb-2">Modul ID: {{ result.pivot.module_id }}</h4>
                                            <p class="text-sm text-muted-foreground whitespace-pre-wrap">{{ result.pivot.result_real }}</p>
                                        </div>
                                    </div>
                                </DialogContent>
                            </Dialog>
                        </div>
                    </div>
                </div>

                <div v-if="students.data.length === 0" class="rounded-lg border bg-card p-8 text-center text-muted-foreground">
                    Talabalar topilmadi.
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="students.last_page > 1" class="flex flex-col sm:flex-row items-center justify-between gap-4 mt-4">
                <div class="text-sm text-muted-foreground">
                    Jami: {{ students.total }} ta talaba
                </div>
                <div class="flex flex-wrap items-center justify-center gap-1">
                    <Link
                        v-for="(link, index) in students.links"
                        :key="index"
                        :href="link.url || '#'"
                        :class="[
                            'inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50',
                            'h-9 px-3 py-2',
                            link.active 
                                ? 'bg-primary text-primary-foreground hover:bg-primary/90' 
                                : 'border border-input bg-background hover:bg-accent hover:text-accent-foreground',
                            !link.url ? 'opacity-50 cursor-not-allowed' : ''
                        ]"
                        :disabled="!link.url"
                        v-html="link.label"
                    />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
