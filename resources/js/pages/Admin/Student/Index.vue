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

const props = defineProps<{
    students: Array<any>;
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
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold tracking-tight">Talabalar</h1>
            </div>

            <div class="rounded-md border bg-card text-card-foreground shadow-sm">
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
                            <tr v-for="student in students" :key="student.id" class="border-b transition-colors hover:bg-muted/50 data-[state=selected]:bg-muted">
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
                                                    <p class="text-sm text-muted-foreground whitespace-pre-wrap">{{ result.pivot.diagnosis }}</p>
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
                            <tr v-if="students.length === 0">
                                <td colspan="8" class="p-4 align-middle text-center">
                                    Talabalar topilmadi.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
