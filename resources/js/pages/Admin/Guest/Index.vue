<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
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
import { Button } from '@/components/ui/button';

interface PaginatedData<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: Array<{ url: string | null; label: string; active: boolean }>;
}

const props = defineProps<{
    guests: PaginatedData<any>;
    positions: string[];
    statuses: string[];
    categories: any[];
    filters: {
        search?: string | null;
        desired_position?: string | null;
        application_status?: string | null;
        test_status?: string | null;
        category_id?: string | null;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Ishga qabul qilinmaganlar', href: '/admin/guests' }];

const selectedGuest = ref<any>(null);
const searchQuery = ref<string>(props.filters.search || '');
const positionFilter = ref<string | null>(props.filters.desired_position || null);
const statusFilter = ref<string | null>(props.filters.application_status || null);
const testStatusFilter = ref<string | null>(props.filters.test_status || null);
const categoryFilter = ref<string | null>(props.filters.category_id || null);

const formatDate = (dateString: string) => new Date(dateString).toLocaleDateString();

const getFilterParams = () => ({
    search: searchQuery.value,
    desired_position: positionFilter.value,
    application_status: statusFilter.value,
    test_status: testStatusFilter.value,
    category_id: categoryFilter.value,
});

const applyFilters = () => router.get('/admin/guests', getFilterParams());

const resetFilters = () => {
    searchQuery.value = '';
    positionFilter.value = null;
    statusFilter.value = null;
    testStatusFilter.value = null;
    categoryFilter.value = null;
    router.get('/admin/guests');
};

const hasPassport = (guest: any) => Boolean(guest.passport);

const downloadPassport = (guestId: number) => {
    window.location.href = `/admin/guests/${guestId}/passport/pdf`;
};

const getPaginationLink = (url: string | null) => {
    if (!url) return null;
    const urlObj = new URL(url);
    Object.entries(getFilterParams()).forEach(([key, value]) => {
        if (value) urlObj.searchParams.set(key, String(value));
    });
    return urlObj.toString();
};

const downloadExcel = () => {
    const params = new URLSearchParams();
    Object.entries(getFilterParams()).forEach(([key, value]) => {
        if (value) params.append(key, String(value));
    });
    const queryString = params.toString();
    window.location.href = `/admin/guests/export/excel${queryString ? '?' + queryString : ''}`;
};

const getGuestLink = (guestId: number) => {
    const params = new URLSearchParams();
    Object.entries(getFilterParams()).forEach(([key, value]) => {
        if (value) params.append(key, String(value));
    });
    if (props.guests.current_page > 1) params.append('page', String(props.guests.current_page));
    const queryString = params.toString();
    return `/admin/guests/${guestId}${queryString ? '?' + queryString : ''}`;
};
</script>

<template>
    <Head title="Ishga qabul qilinmaganlar" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-2 sm:p-4">
            <div class="flex items-center justify-between">
                <h1 class="text-xl sm:text-2xl font-bold tracking-tight">Ishga qabul qilinmaganlar</h1>
            </div>

            <!-- Filter Section -->
            <div class="rounded-md border bg-card text-card-foreground shadow-sm p-4">
                <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-2">Qidirish (Ism yoki Telefon)</label>
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Ism yoki telefon..."
                            class="w-full px-3 py-2 border border-input rounded-md bg-background text-sm"
                        />
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-2">Lavozim</label>
                        <select v-model="positionFilter" class="w-full px-3 py-2 border border-input rounded-md bg-background text-sm">
                            <option value="" selected>Barchasi</option>
                            <option v-for="p in positions" :key="p" :value="p">{{ p }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-2">Ariza holati</label>
                        <select v-model="statusFilter" class="w-full px-3 py-2 border border-input rounded-md bg-background text-sm">
                            <option value="" selected>Barchasi</option>
                            <option v-for="s in statuses" :key="s" :value="s">{{ s }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-2">Test Statusi</label>
                        <select v-model="testStatusFilter" class="w-full px-3 py-2 border border-input rounded-md bg-background text-sm">
                            <option value="" selected>Barchasi</option>
                            <option value="submitted">Topshirgan</option>
                            <option value="not_submitted">Topshirmagan</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-2">Kategoriya</label>
                        <select v-model="categoryFilter" class="w-full px-3 py-2 border border-input rounded-md bg-background text-sm">
                            <option value="" selected>Barchasi</option>
                            <option v-for="category in categories" :key="category.id" :value="category.id">{{ category.name }}</option>
                        </select>
                    </div>

                    <div class="flex items-end gap-2 lg:col-span-2">
                        <Button @click="applyFilters" class="flex-1">Filterlash</Button>
                        <Button @click="resetFilters" variant="outline" class="flex-1">Tozalash</Button>
                    </div>
                </div>

                <div class="flex flex-col md:flex-row gap-2 mt-4 pt-4 border-t">
                    <Button @click="downloadExcel" variant="outline" class="flex-1">📊 Excel'ga yuklash</Button>
                </div>
            </div>

            <!-- Desktop Table View -->
            <div class="hidden md:block rounded-md border bg-card text-card-foreground shadow-sm">
                <div class="relative w-full overflow-auto">
                    <table class="w-full caption-bottom text-sm">
                        <thead class="[&_tr]:border-b">
                            <tr class="border-b">
                                <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Rasm</th>
                                <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Ism Familiya</th>
                                <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Telefon</th>
                                <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Lavozim</th>
                                <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Ariza holati</th>
                                <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Sana</th>
                                <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Xulosalar</th>
                                <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Amallar</th>
                            </tr>
                        </thead>
                        <tbody class="[&_tr:last-child]:border-0">
                            <tr v-for="guest in guests.data" :key="guest.id" class="border-b transition-colors hover:bg-muted/50">
                                <td class="p-4 align-middle">
                                    <div v-if="guest.picture" class="w-10 h-10 rounded-full overflow-hidden bg-gray-100">
                                        <img :src="`/storage/${guest.picture}`" :alt="guest.name" class="w-full h-full object-cover" />
                                    </div>
                                    <div v-else class="w-10 h-10 rounded-full bg-indigo-100 flex items-center justify-center text-sm font-semibold text-indigo-600">
                                        {{ guest.name.charAt(0).toUpperCase() }}
                                    </div>
                                </td>
                                <td class="p-4 align-middle font-medium">{{ guest.name }}</td>
                                <td class="p-4 align-middle">{{ guest.phone || '-' }}</td>
                                <td class="p-4 align-middle">{{ guest.guest?.desired_position || '-' }}</td>
                                <td class="p-4 align-middle">{{ guest.guest?.application_status || '-' }}</td>
                                <td class="p-4 align-middle">{{ formatDate(guest.created_at) }}</td>
                                <td class="p-4 align-middle">
                                    <Dialog v-if="guest.users_tests_results && guest.users_tests_results.length > 0">
                                        <DialogTrigger as-child>
                                            <Button variant="outline" size="sm" @click="selectedGuest = guest">Xulosa</Button>
                                        </DialogTrigger>
                                        <DialogContent class="sm:max-w-[425px]">
                                            <DialogHeader>
                                                <DialogTitle>Diagnostika Xulosalari - {{ guest.name }}</DialogTitle>
                                                <DialogDescription>Nomzod uchun yozilgan barcha diagnostika xulosalari.</DialogDescription>
                                            </DialogHeader>
                                            <div class="grid gap-4 py-4 max-h-[60vh] overflow-y-auto">
                                                <div v-for="result in guest.users_tests_results" :key="result.id" class="bg-background/50 border border-input rounded-lg p-4 shadow-sm">
                                                    <div class="flex items-center gap-3">
                                                        <span class="inline-flex items-center justify-center rounded-full bg-primary/10 text-primary px-3 py-1 text-sm font-medium">Modul -></span>
                                                        <div class="text-sm text-muted-foreground"><span class="font-medium text-foreground ml-1">{{ result.name }}</span></div>
                                                    </div>
                                                    <div class="mt-3 grid gap-3 sm:grid-cols-2">
                                                        <div>
                                                            <h5 class="text-sm font-medium mb-1">Psixolog xulosasi</h5>
                                                            <p class="text-sm text-muted-foreground whitespace-pre-wrap break-words">{{ result.pivot.diagnosis || '-' }}</p>
                                                        </div>
                                                        <div>
                                                            <h5 class="text-sm font-medium mb-1">Avtomatik xulosa</h5>
                                                            <p class="text-sm text-muted-foreground whitespace-pre-wrap break-words">{{ result.pivot.result_real || '-' }}</p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </DialogContent>
                                    </Dialog>
                                    <span v-else class="text-muted-foreground text-sm">-</span>
                                </td>
                                <td class="p-4 align-middle">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <Link :href="getGuestLink(guest.id)" class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium transition-colors bg-primary text-primary-foreground hover:bg-primary/90 h-9 px-4 py-2">
                                            Ko'rish
                                        </Link>
                                        <Button
                                            v-if="hasPassport(guest)"
                                            variant="outline"
                                            size="sm"
                                            @click="downloadPassport(guest.id)"
                                        >
                                            Passport
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="guests.data.length === 0">
                                <td colspan="8" class="p-4 align-middle text-center">Nomzodlar topilmadi.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Mobile Card View -->
            <div class="md:hidden space-y-4">
                <div v-for="guest in guests.data" :key="guest.id" class="rounded-lg border bg-card text-card-foreground shadow-sm p-4">
                    <div class="flex items-start gap-3">
                        <div v-if="guest.picture" class="w-12 h-12 rounded-full overflow-hidden bg-gray-100 flex-shrink-0">
                            <img :src="`/storage/${guest.picture}`" :alt="guest.name" class="w-full h-full object-cover" />
                        </div>
                        <div v-else class="w-12 h-12 rounded-full bg-indigo-100 flex items-center justify-center text-base font-semibold text-indigo-600 flex-shrink-0">
                            {{ guest.name.charAt(0).toUpperCase() }}
                        </div>
                        <div>
                            <h3 class="font-semibold text-base">{{ guest.name }}</h3>
                            <p class="text-sm text-muted-foreground">{{ guest.phone || '-' }}</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-2 text-sm mt-3">
                        <div><span class="text-muted-foreground">Lavozim:</span><p class="font-medium">{{ guest.guest?.desired_position || '-' }}</p></div>
                        <div><span class="text-muted-foreground">Ariza holati:</span><p class="font-medium">{{ guest.guest?.application_status || '-' }}</p></div>
                        <div><span class="text-muted-foreground">Sana:</span><p class="font-medium">{{ formatDate(guest.created_at) }}</p></div>
                        <div><span class="text-muted-foreground">Passport:</span><p class="font-medium">{{ hasPassport(guest) ? 'Mavjud' : 'Mavjud emas' }}</p></div>
                    </div>
                    <div class="flex flex-wrap gap-2 pt-2 mt-2 border-t">
                        <Link :href="getGuestLink(guest.id)" class="inline-flex flex-1 items-center justify-center rounded-md text-sm font-medium bg-primary text-primary-foreground hover:bg-primary/90 h-9 px-3 py-2">
                            Ko'rish
                        </Link>
                        <Button
                            v-if="hasPassport(guest)"
                            variant="outline"
                            class="flex-1"
                            @click="downloadPassport(guest.id)"
                        >
                            Passport
                        </Button>
                    </div>
                </div>
                <div v-if="guests.data.length === 0" class="rounded-lg border bg-card p-8 text-center text-muted-foreground">Nomzodlar topilmadi.</div>
            </div>

            <!-- Pagination -->
            <div v-if="guests.last_page > 1" class="flex flex-col sm:flex-row items-center justify-between gap-4 mt-4">
                <div class="text-sm text-muted-foreground">Jami: {{ guests.total }} ta nomzod</div>
                <div class="flex flex-wrap items-center justify-center gap-1">
                    <Link
                        v-for="(link, index) in guests.links"
                        :key="index"
                        :href="getPaginationLink(link.url) || '#'"
                        :class="[
                            'inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium transition-colors h-9 px-3 py-2',
                            link.active ? 'bg-primary text-primary-foreground hover:bg-primary/90' : 'border border-input bg-background hover:bg-accent hover:text-accent-foreground',
                            !link.url ? 'opacity-50 cursor-not-allowed' : '',
                        ]"
                        v-html="link.label"
                    />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
