<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import axios from 'axios';
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
import PassportDialog from '@/components/PassportDialog.vue';

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

const isPassportModalOpen = ref(false);
const savedPassport = ref(props.guest.passport || null);

const syncCategories = () => {
    router.post(
        `/admin/guests/${props.guest.id}/sync-categories`,
        { category_ids: selectedCategoryIds.value },
        { onSuccess: () => { isSyncModalOpen.value = false; } },
    );
};

const statusUpdating = ref(false);

const rejectGuest = () => {
    if (!confirm('Nomzod arizasini rad etasizmi?')) return;

    statusUpdating.value = true;
    router.post(
        `/admin/guests/${props.guest.id}/status`,
        { status: 'rejected' },
        { onFinish: () => { statusUpdating.value = false; } },
    );
};

// --- Ishga qabul qilish: HEMIS ID qidiruv modali ---
const isAcceptModalOpen = ref(false);
const hemisId = ref('');
const searchLoading = ref(false);
const searchError = ref('');
const localMatch = ref<any>(null);
const hemisMatch = ref<any>(null);

const openAcceptModal = () => {
    hemisId.value = '';
    searchError.value = '';
    localMatch.value = null;
    hemisMatch.value = null;
    isAcceptModalOpen.value = true;
};

const searchEmployee = async () => {
    const id = hemisId.value.trim();
    if (!id) {
        searchError.value = 'HEMIS ID kiriting.';
        return;
    }

    searchLoading.value = true;
    searchError.value = '';
    localMatch.value = null;
    hemisMatch.value = null;

    try {
        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
        const { data } = await axios.post(
            `/admin/guests/${props.guest.id}/employee-search`,
            { hemis_id: id },
            { headers: { 'X-CSRF-TOKEN': csrf } },
        );

        if (data.source === 'local') {
            localMatch.value = data.local;
        } else if (data.source === 'hemis') {
            hemisMatch.value = data.hemis;
        } else {
            searchError.value = data.message || 'Ma\'lumot topilmadi.';
        }
    } catch (e: any) {
        searchError.value = e?.response?.data?.message || 'Qidiruvda xatolik yuz berdi.';
    } finally {
        searchLoading.value = false;
    }
};

const mergeIntoEmployee = () => {
    if (!localMatch.value) return;
    statusUpdating.value = true;
    router.post(
        `/admin/guests/${props.guest.id}/merge`,
        { target_user_id: localMatch.value.user_id },
        {
            onSuccess: () => { isAcceptModalOpen.value = false; },
            onFinish: () => { statusUpdating.value = false; },
        },
    );
};

const confirmAccept = () => {
    statusUpdating.value = true;
    router.post(
        `/admin/guests/${props.guest.id}/status`,
        { status: 'accepted', hemis_id: hemisId.value.trim() },
        {
            onSuccess: () => { isAcceptModalOpen.value = false; },
            onFinish: () => { statusUpdating.value = false; },
        },
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
                    <div class="flex flex-wrap gap-2">
                        <Button variant="outline" size="sm" @click="isSyncModalOpen = true">Kategoriya biriktirish</Button>
                        <Button variant="outline" size="sm" @click="isPassportModalOpen = true">
                            Ijtimoiy-psixologik passport
                        </Button>
                    </div>
                </div>
            </div>

            <PassportDialog
                v-model:open="isPassportModalOpen"
                :person-name="guest.name"
                :endpoint="`/admin/guests/${guest.id}/passport/pdf`"
                :passport="savedPassport"
                subject-label="Nomzod"
                conclusion-only
                @saved="(passport) => (savedPassport = passport)"
            />

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

            <!-- Ishga qabul qilish: HEMIS ID qidiruv modali -->
            <Dialog v-model:open="isAcceptModalOpen">
                <DialogContent class="sm:max-w-[480px]">
                    <DialogHeader>
                        <DialogTitle>Ishga qabul qilish — HEMIS ID</DialogTitle>
                    </DialogHeader>
                    <div class="grid gap-4 py-2">
                        <div class="flex items-end gap-2">
                            <div class="flex-1">
                                <label class="text-sm font-medium text-muted-foreground">HEMIS ID</label>
                                <input
                                    v-model="hemisId"
                                    type="text"
                                    inputmode="numeric"
                                    placeholder="masalan: 3312411057"
                                    class="mt-1 w-full rounded-md border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                    @keyup.enter="searchEmployee"
                                />
                            </div>
                            <Button :disabled="searchLoading" @click="searchEmployee">
                                {{ searchLoading ? 'Qidirilmoqda...' : 'Qidirish' }}
                            </Button>
                        </div>

                        <p v-if="searchError" class="text-sm text-red-600">{{ searchError }}</p>

                        <!-- O'z bazamizda topildi -->
                        <div v-if="localMatch" class="rounded-md border bg-amber-50 p-4">
                            <p class="text-sm text-amber-800 mb-2">
                                Bu HEMIS ID bo'yicha hodim allaqachon bazada mavjud. Birlashtirilsa, bu nomzod
                                ro'yxatdan o'chiriladi va test natijalari o'sha hodim profilida ko'rinadi.
                            </p>
                            <div class="flex items-center gap-3 mb-3">
                                <div v-if="localMatch.picture" class="h-12 w-12 rounded-full overflow-hidden bg-gray-100">
                                    <img :src="`/storage/${localMatch.picture}`" class="h-full w-full object-cover" />
                                </div>
                                <p class="font-medium">{{ localMatch.name }}</p>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <Button
                                    class="bg-indigo-600 text-white hover:bg-indigo-700"
                                    size="sm"
                                    :disabled="statusUpdating"
                                    @click="mergeIntoEmployee"
                                >
                                    {{ statusUpdating ? 'Birlashtirilmoqda...' : 'Mavjud profilga birlashtirish' }}
                                </Button>
                                <a :href="localMatch.profile_url">
                                    <Button variant="outline" size="sm">Profilni ko'rish</Button>
                                </a>
                            </div>
                        </div>

                        <!-- HEMIS'dan topildi -->
                        <div v-if="hemisMatch" class="rounded-md border bg-green-50 p-4">
                            <div class="flex items-start gap-3 mb-3">
                                <div v-if="hemisMatch.image" class="h-16 w-16 rounded-full overflow-hidden bg-gray-100 flex-shrink-0">
                                    <img :src="hemisMatch.image" class="h-full w-full object-cover" />
                                </div>
                                <div class="text-sm space-y-0.5">
                                    <p class="font-semibold">{{ hemisMatch.name }}</p>
                                    <p class="text-muted-foreground">HEMIS ID: {{ hemisMatch.employee_id_number }}</p>
                                    <p v-if="hemisMatch.staff_position_name">Lavozim: {{ hemisMatch.staff_position_name }}</p>
                                    <p v-if="hemisMatch.department_name">Bo'lim: {{ hemisMatch.department_name }}</p>
                                    <p v-if="hemisMatch.employee_type_name">Turi: {{ hemisMatch.employee_type_name }}</p>
                                    <p v-if="hemisMatch.employee_status_name">Holati: {{ hemisMatch.employee_status_name }}</p>
                                </div>
                            </div>
                            <Button
                                class="bg-green-600 text-white hover:bg-green-700"
                                size="sm"
                                :disabled="statusUpdating"
                                @click="confirmAccept"
                            >
                                {{ statusUpdating ? 'Saqlanmoqda...' : 'Tasdiqlash va hodimga o\'tkazish' }}
                            </Button>
                        </div>
                    </div>
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
                            @click="openAcceptModal"
                        >
                            Ishga qabul qilish
                        </Button>
                        <Button
                            variant="outline"
                            size="sm"
                            class="border-red-300 text-red-700 hover:bg-red-50"
                            :disabled="statusUpdating || guest.guest?.application_status === 'rejected'"
                            @click="rejectGuest"
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
