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
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';

const page = usePage();
const user = page.props.auth.user;

const props = defineProps<{
    student: any;
    results: Array<any>;
    allCategories: Array<any>;
    filters?: {
        group_id?: string | null;
        speciality_id?: string | null;
        test_status?: string | null;
    };
    page?: number;
}>();

const isSyncModalOpen = ref(false);
const selectedCategoryIds = ref<number[]>(props.student.users_category?.map((c: any) => c.id) || []);
const isPassportModalOpen = ref(false);
const isDownloadingPassport = ref(false);
const passportRequestError = ref('');
const passportErrors = ref<Record<string, string>>({});
const passportForm = ref({
    characterTraits: ['', '', '', '', ''],
    temperamentType: '',
    studentConclusion: '',
});

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
    if (props.filters?.group_id && props.filters.group_id !== 'null') {
        params.append('group_id', props.filters.group_id);
    }
    if (props.filters?.speciality_id && props.filters.speciality_id !== 'null') {
        params.append('speciality_id', props.filters.speciality_id);
    }
    if (props.filters?.test_status && props.filters.test_status !== 'null') {
        params.append('test_status', props.filters.test_status);
    }
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

const extractFilename = (disposition: string | null) => {
    if (!disposition) {
        return null;
    }

    const utf8Match = disposition.match(/filename\*=UTF-8''([^;]+)/i);
    if (utf8Match?.[1]) {
        return decodeURIComponent(utf8Match[1]);
    }

    const asciiMatch = disposition.match(/filename="?([^"]+)"?/i);
    return asciiMatch?.[1] || null;
};

const slugify = (value: string) =>
    value
        .toLowerCase()
        .trim()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '');

const validatePassportForm = () => {
    const errors: Record<string, string> = {};

    passportForm.value.characterTraits.forEach((trait, index) => {
        if (!trait.trim()) {
            errors[`characterTraits.${index}`] = `${index + 1}-qobiliyatni kiriting.`;
        }
    });

    if (!passportForm.value.temperamentType.trim()) {
        errors.temperamentType = 'Temperament tipini kiriting.';
    }

    if (!passportForm.value.studentConclusion.trim()) {
        errors.studentConclusion = 'Talaba uchun xulosani kiriting.';
    }

    passportErrors.value = errors;

    return Object.keys(errors).length === 0;
};

const closePassportModal = () => {
    isPassportModalOpen.value = false;
    passportRequestError.value = '';
    passportErrors.value = {};
};

const downloadPassportPdf = async () => {
    passportRequestError.value = '';

    if (!validatePassportForm()) {
        return;
    }

    isDownloadingPassport.value = true;

    try {
        const formData = new FormData();

        passportForm.value.characterTraits.forEach((trait, index) => {
            formData.append(`character_traits[${index}]`, trait.trim());
        });

        formData.append('temperament_type', passportForm.value.temperamentType.trim());
        formData.append('student_conclusion', passportForm.value.studentConclusion.trim());

        const response = await fetch(`/admin/students/${props.student.id}/passport/pdf`, {
            method: 'POST',
            headers: {
                Accept: 'application/pdf, application/json',
                'X-CSRF-TOKEN':
                    (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '',
            },
            body: formData,
        });

        if (!response.ok) {
            const errorPayload = await response.json().catch(() => null);

            if (response.status === 422 && errorPayload?.errors) {
                const validationErrors: Record<string, string> = {};

                Object.entries(errorPayload.errors).forEach(([key, value]) => {
                    if (Array.isArray(value) && value[0]) {
                        if (key.startsWith('character_traits.')) {
                            const index = key.split('.').pop();
                            validationErrors[`characterTraits.${index}`] = value[0];
                        } else if (key === 'temperament_type') {
                            validationErrors.temperamentType = value[0];
                        } else if (key === 'student_conclusion') {
                            validationErrors.studentConclusion = value[0];
                        }
                    }
                });

                passportErrors.value = validationErrors;
            } else {
                passportRequestError.value =
                    errorPayload?.message || 'PDF tayyorlashda xatolik yuz berdi. Qayta urinib ko‘ring.';
            }

            return;
        }

        const pdfBlob = await response.blob();
        const downloadUrl = URL.createObjectURL(pdfBlob);
        const downloadLink = document.createElement('a');
        const fileName =
            extractFilename(response.headers.get('content-disposition')) ||
            `ijtimoiy-psixologik-passport-${slugify(props.student.name || 'talaba')}.pdf`;

        downloadLink.href = downloadUrl;
        downloadLink.download = fileName;
        document.body.appendChild(downloadLink);
        downloadLink.click();
        downloadLink.remove();
        URL.revokeObjectURL(downloadUrl);

        closePassportModal();
    } catch {
        passportRequestError.value = 'Server bilan bog‘lanishda xatolik yuz berdi.';
    } finally {
        isDownloadingPassport.value = false;
    }
};

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

                <Dialog v-model:open="isPassportModalOpen">
                    <DialogContent class="sm:max-w-2xl">
                        <DialogHeader>
                            <DialogTitle>Ijtimoiy-psixologik passport</DialogTitle>
                        </DialogHeader>
                        <div class="grid gap-5 py-4">
                            <div class="rounded-lg border bg-muted/20 p-4">
                                <p class="text-sm font-medium">{{ student.name }}</p>
                                <p class="mt-1 text-sm text-muted-foreground">
                                    Ma'lumotlar chap tomonda avtomatik joylanadi. Quyidagi maydonlarni to‘ldirib PDF yuklab olishingiz mumkin.
                                </p>
                            </div>

                            <div class="grid gap-3">
                                <label class="text-sm font-medium">Xarakterdagi qobiliyatlar ketma-ketligi</label>
                                <div class="grid gap-3 sm:grid-cols-2">
                                    <div v-for="(_, index) in passportForm.characterTraits" :key="index" class="space-y-1">
                                        <input
                                            v-model="passportForm.characterTraits[index]"
                                            type="text"
                                            :placeholder="`${index + 1}-qobiliyat`"
                                            class="file:text-foreground placeholder:text-muted-foreground selection:bg-primary selection:text-primary-foreground dark:bg-input/30 border-input h-9 w-full min-w-0 rounded-md border bg-transparent px-3 py-1 text-base shadow-xs transition-[color,box-shadow] outline-none md:text-sm focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]"
                                        />
                                        <p v-if="passportErrors[`characterTraits.${index}`]" class="text-sm text-red-600">
                                            {{ passportErrors[`characterTraits.${index}`] }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="space-y-1">
                                <label class="text-sm font-medium" for="temperament-type">Temperament tipi</label>
                                <input
                                    id="temperament-type"
                                    v-model="passportForm.temperamentType"
                                    type="text"
                                    placeholder="Masalan: Flegmatik"
                                    class="file:text-foreground placeholder:text-muted-foreground selection:bg-primary selection:text-primary-foreground dark:bg-input/30 border-input h-9 w-full min-w-0 rounded-md border bg-transparent px-3 py-1 text-base shadow-xs transition-[color,box-shadow] outline-none md:text-sm focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]"
                                />
                                <p v-if="passportErrors.temperamentType" class="text-sm text-red-600">
                                    {{ passportErrors.temperamentType }}
                                </p>
                            </div>

                            <div class="space-y-1">
                                <label class="text-sm font-medium" for="student-conclusion">Talaba xulosasi</label>
                                <textarea
                                    id="student-conclusion"
                                    v-model="passportForm.studentConclusion"
                                    rows="5"
                                    placeholder="Talaba uchun xulosani kiriting"
                                    class="placeholder:text-muted-foreground dark:bg-input/30 border-input min-h-28 w-full rounded-md border bg-transparent px-3 py-2 text-sm shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]"
                                />
                                <p v-if="passportErrors.studentConclusion" class="text-sm text-red-600">
                                    {{ passportErrors.studentConclusion }}
                                </p>
                            </div>

                            <p v-if="passportRequestError" class="text-sm text-red-600">
                                {{ passportRequestError }}
                            </p>
                        </div>
                        <DialogFooter class="gap-2 sm:justify-end">
                            <Button variant="outline" @click="closePassportModal">Bekor qilish</Button>
                            <Button @click="downloadPassportPdf" :disabled="isDownloadingPassport">
                                {{ isDownloadingPassport ? 'Yuklanmoqda...' : 'PDF yuklab olish' }}
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>

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
                                    <td class="p-4 align-middle">{{ result.pivot.diagnosis ? 'Ha' : 'Yo\'q' }}</td>
                                    <td class="p-4 align-middle">
                                        <Link 
                                            :href="`/admin/students/${student.id}/results/${result.id}`" 
                                            class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-secondary text-secondary-foreground hover:bg-secondary/80 h-9 px-4 py-2"
                                        >
                                            Natijani ko'rish
                                        </Link>
                                        <Button
                                            @click="deleteResult(result.id, student.id)"
                                            v-if="user.role === 'admin'"
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
