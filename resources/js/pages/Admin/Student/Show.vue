<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
import Button from '@/components/ui/button/Button.vue';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import PassportDialog from '@/components/PassportDialog.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';

const page = usePage();
const user = page.props.auth.user;

const props = defineProps<{
    student: any;
    results: Array<any>;
    allCategories: Array<any>;
    hobbies?: string[];
    riskFlag?: { value: string; label: string; color: string } | null;
    filters?: {
        search?: string | null;
        faculity_id?: string | null;
        group_id?: string | null;
        speciality_id?: string | null;
        level?: string | null;
        test_status?: string | null;
        category_id?: string | null;
        passport_status?: string | null;
    };
    page?: number;
}>();

const isSyncModalOpen = ref(false);
const selectedCategoryIds = ref<number[]>(props.student.users_category?.map((c: any) => c.id) || []);
const isPassportModalOpen = ref(false);
const savedPassport = ref(props.student.passport || null);

const syncCategories = () => {
    router.post(`/admin/students/${props.student.id}/sync-categories`, {
        category_ids: selectedCategoryIds.value,
    }, {
        onSuccess: () => {
            isSyncModalOpen.value = false;
        },
    });
};

const getBackLink = () => {
    const params = new URLSearchParams();

    const filters = {
        search: props.filters?.search,
        faculity_id: props.filters?.faculity_id,
        group_id: props.filters?.group_id,
        speciality_id: props.filters?.speciality_id,
        level: props.filters?.level,
        test_status: props.filters?.test_status,
        category_id: props.filters?.category_id,
        passport_status: props.filters?.passport_status,
    };

    Object.entries(filters).forEach(([key, value]) => {
        if (value && value !== 'null') {
            params.append(key, String(value));
        }
    });

    if (props.page && props.page > 1) {
        params.append('page', String(props.page));
    }
    const queryString = params.toString();
    return `/admin/students${queryString ? '?' + queryString : ''}`;
};

const deleteResult = (resultId: number, studentId: number) => {
    if (confirm('Haqiqatdan ham ushbu natijani o\'chirmoqchimisiz?')) {
        removeResult(resultId, studentId);
    }
};
function removeResult(resultId: number, studentId: number) {
    if (confirm('Haqiqatdan ham ushbu natijani o\'chirmoqchimisiz?')) {
        router.delete(`/admin/students/${studentId}/results/${resultId}`, {
            onSuccess: () => {
                router.reload();
            },
            onError: () => {
                alert('Natijani o\'chirishda xatolik yuz berdi. Iltimos, qayta urinib ko\'ring.');
            },
        });
    }
}

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
                        <div class="flex justify-between items-center py-2">
                            <span class="text-muted-foreground">Kategoriyalar:</span>
                            <div class="flex flex-wrap gap-1 justify-end">
                                <span v-for="cat in student.users_category" :key="cat.id" class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-800">
                                    {{ cat.name }}
                                </span>
                                <span v-if="!student.users_category?.length" class="text-muted-foreground">-</span>
                            </div>
                        </div>
                        <div class="space-y-2 pt-4">
                            <Button v-if="results.length > 0" @click="isSyncModalOpen = true" class="w-full">
                                Kategoriya Biriktirish
                            </Button>
                            <Button variant="outline" @click="isPassportModalOpen = true" class="w-full">
                                Ijtimoiy-psixologik passport
                            </Button>
                        </div>
                    </div>
                </div>

                <Dialog v-model:open="isSyncModalOpen">
                    <DialogContent class="sm:max-w-[425px]">
                        <DialogHeader>
                            <DialogTitle>Kategoriyalarni biriktirish</DialogTitle>
                        </DialogHeader>
                        <div class="grid gap-4 py-4">
                            <div v-for="category in allCategories" :key="category.id" class="flex items-center space-x-2">
                                <input
                                    type="checkbox"
                                    :id="'cat-' + category.id"
                                    :value="category.id"
                                    v-model="selectedCategoryIds"
                                    class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                />
                                <label :for="'cat-' + category.id" class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">
                                    {{ category.name }}
                                </label>
                            </div>
                        </div>
                        <DialogFooter>
                            <Button @click="syncCategories">Saqlash</Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>

                <PassportDialog
                    v-model:open="isPassportModalOpen"
                    :person-name="student.name"
                    :endpoint="`/admin/students/${student.id}/passport/pdf`"
                    :passport="savedPassport"
                    subject-label="Talaba"
                    :hobbies="props.hobbies ?? []"
                    :risk-flag="props.riskFlag ?? null"
                    @saved="(passport) => (savedPassport = passport)"
                />

                <div class="rounded-lg border bg-card p-6 shadow-sm">
                    <h2 class="text-lg font-semibold mb-4">Topshirilgan Testlar</h2>
                    <div class="relative w-full overflow-auto">
                        <table class="w-full caption-bottom text-sm">
                            <thead class="[&_tr]:border-b">
                                <tr class="border-b">
                                    <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Modul</th>
                                    <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Natija (Diagnostika)</th>
                                    <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Amallar</th>
                                </tr>
                            </thead>
                            <tbody class="[&_tr:last-child]:border-0">
                                <tr v-for="result in results" :key="result.id" class="border-b">
                                    <td class="p-4 align-middle">{{ result.name }}</td>
                                    <td class="p-4 align-middle">{{ result.pivot.diagnosis || result.pivot.result_real ? 'Ha' : 'Yo\'q' }}</td>
                                    <td class="p-4 align-middle">
                                        <Link
                                            :href="`/admin/students/${student.id}/results/${result.id}`"
                                            class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-secondary text-secondary-foreground hover:bg-secondary/80 h-9 px-4 py-2"
                                        >
                                            Natijani ko'rish
                                        </Link>
                                        <Button
                                            @click="deleteResult(result.id, student.id)"
                                            class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-secondary text-secondary-foreground hover:bg-secondary/80 h-9 px-4 py-2 ml-2"
                                        >
                                            O'chirish
                                        </Button>
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
