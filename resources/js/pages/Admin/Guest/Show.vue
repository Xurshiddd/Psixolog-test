<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

const props = defineProps<{
    guest: any;
    results: any[];
    allCategories: any[];
    filters: Record<string, any>;
    page: number;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Ishga qabul qilinmaganlar', href: '/admin/guests' },
    { title: props.guest.name, href: '#' },
];

const resultDetailLink = (moduleId: number) => `/admin/guests/${props.guest.id}/results/${moduleId}`;

const isSyncModalOpen = ref(false);
const selectedCategoryIds = ref<number[]>(props.guest.users_category?.map((c: any) => c.id) || []);

const syncCategories = () => {
    router.post(
        `/admin/guests/${props.guest.id}/sync-categories`,
        { category_ids: selectedCategoryIds.value },
        { onSuccess: () => { isSyncModalOpen.value = false; } },
    );
};

const statusUpdating = ref(false);

const updateStatus = (status: 'accepted' | 'rejected') => {
    const message =
        status === 'accepted'
            ? 'Nomzodni ishga qabul qilasizmi? U hodimlar ro\'yxatiga o\'tkaziladi.'
            : 'Nomzod arizasini rad etasizmi?';

    if (!confirm(message)) return;

    statusUpdating.value = true;
    router.post(
        `/admin/guests/${props.guest.id}/status`,
        { status },
        { onFinish: () => { statusUpdating.value = false; } },
    );
};
</script>

<template>
    <Head :title="guest.name" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-2 sm:p-4">
            <!-- Profile -->
            <div class="rounded-md border bg-card text-card-foreground shadow-sm p-6">
                <div class="flex flex-col sm:flex-row items-start gap-6">
                    <div v-if="guest.picture" class="w-24 h-24 rounded-full overflow-hidden bg-gray-100 flex-shrink-0">
                        <img :src="`/storage/${guest.picture}`" :alt="guest.name" class="w-full h-full object-cover" />
                    </div>
                    <div v-else class="w-24 h-24 rounded-full bg-indigo-100 flex items-center justify-center text-3xl font-semibold text-indigo-600 flex-shrink-0">
                        {{ guest.name.charAt(0).toUpperCase() }}
                    </div>
                    <div class="flex-1 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div><span class="text-sm text-muted-foreground">Ism Familiya</span><p class="font-medium">{{ guest.name }}</p></div>
                        <div><span class="text-sm text-muted-foreground">Otasining ismi</span><p class="font-medium">{{ guest.guest?.father_name || '-' }}</p></div>
                        <div><span class="text-sm text-muted-foreground">Telefon</span><p class="font-medium">{{ guest.phone || '-' }}</p></div>
                        <div><span class="text-sm text-muted-foreground">Manzil</span><p class="font-medium">{{ guest.guest?.address || '-' }}</p></div>
                        <div><span class="text-sm text-muted-foreground">Lavozim</span><p class="font-medium">{{ guest.guest?.desired_position || '-' }}</p></div>
                        <div><span class="text-sm text-muted-foreground">Ariza holati</span><p class="font-medium">{{ guest.guest?.application_status || '-' }}</p></div>
                    </div>
                </div>

                <!-- Categories -->
                <div class="mt-6 pt-6 border-t flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="text-sm text-muted-foreground">Kategoriyalar:</span>
                        <span v-for="cat in guest.users_category" :key="cat.id" class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-800">
                            {{ cat.name }}
                        </span>
                        <span v-if="!guest.users_category?.length" class="text-muted-foreground text-sm">-</span>
                    </div>
                    <Button variant="outline" size="sm" @click="isSyncModalOpen = true">Kategoriya biriktirish</Button>
                </div>
            </div>

            <Dialog v-model:open="isSyncModalOpen">
                <DialogContent class="sm:max-w-[425px]">
                    <DialogHeader>
                        <DialogTitle>Kategoriyalarni biriktirish</DialogTitle>
                    </DialogHeader>
                    <div class="grid gap-4 py-4 max-h-[60vh] overflow-y-auto">
                        <div v-for="category in allCategories" :key="category.id" class="flex items-center space-x-2">
                            <input
                                type="checkbox"
                                :id="'cat-' + category.id"
                                :value="category.id"
                                v-model="selectedCategoryIds"
                                class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                            />
                            <label :for="'cat-' + category.id" class="text-sm font-medium leading-none">{{ category.name }}</label>
                        </div>
                        <p v-if="allCategories.length === 0" class="text-sm text-muted-foreground">Kategoriyalar mavjud emas.</p>
                    </div>
                    <DialogFooter>
                        <Button @click="syncCategories">Saqlash</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <!-- Application status -->
            <div class="rounded-md border bg-card text-card-foreground shadow-sm p-6">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-3">
                        <span class="text-sm text-muted-foreground">Ariza holati:</span>
                        <span
                            class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold"
                            :class="{
                                'bg-amber-100 text-amber-800': guest.guest?.application_status === 'pending',
                                'bg-green-100 text-green-800': guest.guest?.application_status === 'accepted',
                                'bg-red-100 text-red-800': guest.guest?.application_status === 'rejected',
                            }"
                        >
                            {{
                                guest.guest?.application_status === 'accepted'
                                    ? 'Qabul qilingan'
                                    : guest.guest?.application_status === 'rejected'
                                    ? 'Rad etilgan'
                                    : 'Kutilmoqda'
                            }}
                        </span>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <Button
                            class="bg-green-600 text-white hover:bg-green-700"
                            size="sm"
                            :disabled="statusUpdating || guest.guest?.application_status === 'accepted'"
                            @click="updateStatus('accepted')"
                        >
                            Ishga qabul qilish
                        </Button>
                        <Button
                            variant="outline"
                            size="sm"
                            class="border-red-300 text-red-700 hover:bg-red-50"
                            :disabled="statusUpdating || guest.guest?.application_status === 'rejected'"
                            @click="updateStatus('rejected')"
                        >
                            Rad etish
                        </Button>
                    </div>
                </div>
            </div>

            <!-- Results -->
            <div class="rounded-md border bg-card text-card-foreground shadow-sm">
                <div class="px-6 py-4 border-b">
                    <h2 class="text-lg font-semibold">Test natijalari</h2>
                </div>
                <div class="relative w-full overflow-auto">
                    <table class="w-full caption-bottom text-sm">
                        <thead class="[&_tr]:border-b">
                            <tr class="border-b">
                                <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Modul</th>
                                <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Psixolog xulosasi</th>
                                <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Avtomatik xulosa</th>
                                <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Amallar</th>
                            </tr>
                        </thead>
                        <tbody class="[&_tr:last-child]:border-0">
                            <tr v-for="result in results" :key="result.id" class="border-b">
                                <td class="p-4 align-middle font-medium">{{ result.name }}</td>
                                <td class="p-4 align-middle whitespace-pre-wrap break-words max-w-xs">{{ result.pivot.diagnosis || '-' }}</td>
                                <td class="p-4 align-middle whitespace-pre-wrap break-words max-w-xs">{{ result.pivot.result_real || '-' }}</td>
                                <td class="p-4 align-middle">
                                    <Link :href="resultDetailLink(result.id)">
                                        <Button variant="outline" size="sm">Batafsil / Xulosa</Button>
                                    </Link>
                                </td>
                            </tr>
                            <tr v-if="results.length === 0">
                                <td colspan="4" class="p-4 align-middle text-center text-muted-foreground">Test natijalari yo'q.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
