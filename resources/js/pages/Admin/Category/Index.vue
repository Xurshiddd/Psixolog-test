<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { ref } from 'vue';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
    DialogFooter,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

interface Category {
    id: number;
    name: string;
    created_at: string;
}

const props = defineProps<{
    categories: Category[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Kategoriyalar',
        href: '/categories',
    },
];

const isCreateModalOpen = ref(false);
const isEditModalOpen = ref(false);
const editingCategory = ref<Category | null>(null);
const form = ref({
    name: '',
});

const openCreateModal = () => {
    form.value.name = '';
    isCreateModalOpen.value = true;
};

const createCategory = () => {
    router.post('/categories', form.value, {
        onSuccess: () => {
            isCreateModalOpen.value = false;
        },
    });
};

const openEditModal = (category: Category) => {
    editingCategory.value = category;
    form.value.name = category.name;
    isEditModalOpen.value = true;
};

const updateCategory = () => {
    if (!editingCategory.value) return;
    router.put(`/categories/${editingCategory.value.id}`, form.value, {
        onSuccess: () => {
            isEditModalOpen.value = false;
        },
    });
};

const deleteCategory = (id: number) => {
    if (confirm('Ishonchingiz komilmi?')) {
        router.delete(`/categories/${id}`);
    }
};
</script>

<template>
    <Head title="Kategoriyalar" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold tracking-tight">Kategoriyalar</h1>
                <Button @click="openCreateModal">
                    Yangi qo'shish
                </Button>
            </div>

            <div class="rounded-md border bg-card text-card-foreground shadow-sm">
                <div class="relative w-full overflow-auto">
                    <table class="w-full caption-bottom text-sm">
                        <thead class="[&_tr]:border-b">
                            <tr class="border-b transition-colors hover:bg-muted/50">
                                <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">ID</th>
                                <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Nomi</th>
                                <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Sana</th>
                                <th class="h-12 px-4 text-right align-middle font-medium text-muted-foreground">Amallar</th>
                            </tr>
                        </thead>
                        <tbody class="[&_tr:last-child]:border-0">
                            <tr v-for="category in categories" :key="category.id" class="border-b transition-colors hover:bg-muted/50">
                                <td class="p-4 align-middle">{{ category.id }}</td>
                                <td class="p-4 align-middle font-medium">{{ category.name }}</td>
                                <td class="p-4 align-middle">{{ new Date(category.created_at).toLocaleDateString() }}</td>
                                <td class="p-4 align-middle text-right flex justify-end gap-2">
                                    <Button variant="outline" size="sm" @click="openEditModal(category)">
                                        Tahrirlash
                                    </Button>
                                    <Button variant="destructive" size="sm" @click="deleteCategory(category.id)">
                                        O'chirish
                                    </Button>
                                </td>
                            </tr>
                            <tr v-if="categories.length === 0">
                                <td colspan="4" class="p-4 text-center text-muted-foreground">
                                    Kategoriyalar topilmadi.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Create Modal -->
        <Dialog v-model:open="isCreateModalOpen">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Yangi Kategoriya qo'shish</DialogTitle>
                </DialogHeader>
                <div class="grid gap-4 py-4">
                    <div class="grid grid-cols-4 items-center gap-4">
                        <Label for="name" class="text-right">Nomi</Label>
                        <Input id="name" v-model="form.name" class="col-span-3" />
                    </div>
                </div>
                <DialogFooter>
                    <Button type="submit" @click="createCategory">Saqlash</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Edit Modal -->
        <Dialog v-model:open="isEditModalOpen">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Kategoriyani tahrirlash</DialogTitle>
                </DialogHeader>
                <div class="grid gap-4 py-4">
                    <div class="grid grid-cols-4 items-center gap-4">
                        <Label for="edit-name" class="text-right">Nomi</Label>
                        <Input id="edit-name" v-model="form.name" class="col-span-3" />
                    </div>
                </div>
                <DialogFooter>
                    <Button type="submit" @click="updateCategory">Saqlash</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
