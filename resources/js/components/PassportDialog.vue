<script setup lang="ts">
import { ref, watch } from 'vue';
import Button from '@/components/ui/button/Button.vue';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

/**
 * Ijtimoiy-psixologik passport modali — talaba, xodim va ishga qabul
 * qilinmagan nomzod sahifalarida bir xil ishlatiladi. `endpoint` orqali
 * qaysi profil uchun PDF tayyorlanishi aniqlanadi.
 */
const props = defineProps<{
    open: boolean;
    personName: string;
    /** Masalan: `/admin/employees/12/passport/pdf` */
    endpoint: string;
    /** Bazada saqlangan passport (bo'lsa forma shu bilan to'ldiriladi). */
    passport?: any | null;
    /** Xulosa maydoni sarlavhasi uchun: "Talaba", "Xodim", "Nomzod". */
    subjectLabel: string;
    /**
     * Nomzodlar uchun `true`: passportda faqat xulosa bo'ladi, temperament
     * tipi so'ralmaydi.
     */
    conclusionOnly?: boolean;
    /**
     * Talaba qiziqishlari — passportda qobiliyatlar ketma-ketligi o'rniga
     * shular chiqadi. Talaba o'z panelidan kiritadi, bu yerda faqat ko'rinadi.
     */
    hobbies?: string[];
    /** Xavf darajasi bayrog'i (biriktirilgan bo'lsa). */
    riskFlag?: { value: string; label: string; color: string } | null;
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
    saved: [passport: { temperament_type?: string; conclusion: string }];
}>();

const isDownloading = ref(false);
const requestError = ref('');
const errors = ref<Record<string, string>>({});

const buildForm = (passport: any = null) => ({
    temperamentType: passport?.temperament_type || '',
    conclusion: passport?.conclusion || '',
});

const savedPassport = ref(props.passport || null);
const form = ref(buildForm(savedPassport.value));

const resetForm = () => {
    form.value = buildForm(savedPassport.value);
    requestError.value = '';
    errors.value = {};
};

watch(
    () => props.open,
    (open) => {
        if (open) {
            resetForm();
        }
    },
);

const close = () => {
    emit('update:open', false);
    resetForm();
};

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

const validate = () => {
    const found: Record<string, string> = {};

    if (!props.conclusionOnly && !form.value.temperamentType.trim()) {
        found.temperamentType = 'Temperament tipini kiriting.';
    }

    if (!form.value.conclusion.trim()) {
        found.conclusion = 'Xulosani kiriting.';
    }

    errors.value = found;

    return Object.keys(found).length === 0;
};

const downloadPdf = async () => {
    requestError.value = '';

    if (!validate()) {
        return;
    }

    isDownloading.value = true;

    try {
        const formData = new FormData();

        if (!props.conclusionOnly) {
            formData.append('temperament_type', form.value.temperamentType.trim());
        }

        formData.append('conclusion', form.value.conclusion.trim());

        const response = await fetch(props.endpoint, {
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
                        if (key === 'temperament_type') {
                            validationErrors.temperamentType = value[0];
                        } else if (key === 'conclusion') {
                            validationErrors.conclusion = value[0];
                        }
                    }
                });

                errors.value = validationErrors;
            } else {
                requestError.value =
                    errorPayload?.message || 'PDF tayyorlashda xatolik yuz berdi. Qayta urinib ko‘ring.';
            }

            return;
        }

        // Sessiya tugagan bo'lsa server login sahifasiga yo'naltiradi va fetch
        // 200 bilan HTML qaytaradi. Uni .pdf qilib saqlasak, fayl ochilmaydi.
        if (!(response.headers.get('content-type') || '').includes('application/pdf')) {
            requestError.value =
                'Server PDF o‘rniga boshqa javob qaytardi. Sahifani yangilab (sessiya tugagan bo‘lishi mumkin), qaytadan urinib ko‘ring.';

            return;
        }

        const pdfBlob = await response.blob();
        const downloadUrl = URL.createObjectURL(pdfBlob);
        const downloadLink = document.createElement('a');
        const fileName =
            extractFilename(response.headers.get('content-disposition')) ||
            `ijtimoiy-psixologik-passport-${slugify(props.personName || 'foydalanuvchi')}.pdf`;

        downloadLink.href = downloadUrl;
        downloadLink.download = fileName;
        document.body.appendChild(downloadLink);
        downloadLink.click();
        downloadLink.remove();

        // Blob URL darhol bekor qilinsa, brauzer 1 MB'dan katta faylni yozib
        // ulgurmasdan yuklab olishni uzib qo'yishi mumkin — natijada fayl
        // yuklanadi-yu, ochilmaydi. Shuning uchun tozalashni kechiktiramiz.
        window.setTimeout(() => URL.revokeObjectURL(downloadUrl), 60_000);

        const stored = props.conclusionOnly
            ? { conclusion: form.value.conclusion.trim() }
            : {
                  temperament_type: form.value.temperamentType.trim(),
                  conclusion: form.value.conclusion.trim(),
              };

        savedPassport.value = { ...(savedPassport.value || {}), ...stored };
        emit('saved', stored);

        close();
    } catch {
        requestError.value = 'Server bilan bog‘lanishda xatolik yuz berdi.';
    } finally {
        isDownloading.value = false;
    }
};
</script>

<template>
    <Dialog :open="open" @update:open="(value: boolean) => emit('update:open', value)">
        <DialogContent class="sm:max-w-2xl">
            <DialogHeader>
                <DialogTitle>Ijtimoiy-psixologik passport</DialogTitle>
            </DialogHeader>
            <div class="grid gap-5 py-4 max-h-[70vh] overflow-y-auto">
                <div class="rounded-lg border bg-muted/20 p-4">
                    <p class="text-sm font-medium">{{ personName }}</p>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Saqlangan ma'lumot bo‘lsa avtomatik chiqadi. PDF yuklab olish bosilganda avval ma'lumot
                        saqlanadi yoki yangilanadi, keyin fayl yuklanadi.
                    </p>
                </div>

                <div v-if="!conclusionOnly" class="grid gap-2">
                    <label class="text-sm font-medium">Talaba qiziqishlari</label>
                    <div v-if="hobbies && hobbies.length > 0" class="flex flex-wrap gap-2">
                        <span
                            v-for="(hobby, index) in hobbies"
                            :key="index"
                            class="inline-flex items-center gap-1.5 rounded-lg bg-muted px-3 py-1.5 text-sm"
                        >
                            <span class="text-xs font-semibold text-muted-foreground">{{ index + 1 }}.</span>
                            {{ hobby }}
                        </span>
                    </div>
                    <p v-else class="rounded-lg border border-dashed px-3 py-4 text-center text-sm text-muted-foreground">
                        Talaba hali qiziqishlarini kiritmagan. Ular talaba panelidagi "Qiziqishlar" bo'limidan
                        kiritiladi va passportga avtomatik tushadi.
                    </p>
                </div>

                <div v-if="!conclusionOnly" class="space-y-1">
                    <label class="text-sm font-medium" for="passport-temperament-type">Temperament tipi</label>
                    <input
                        id="passport-temperament-type"
                        v-model="form.temperamentType"
                        type="text"
                        placeholder="Masalan: Flegmatik"
                        class="file:text-foreground placeholder:text-muted-foreground selection:bg-primary selection:text-primary-foreground dark:bg-input/30 border-input h-9 w-full min-w-0 rounded-md border bg-transparent px-3 py-1 text-base shadow-xs transition-[color,box-shadow] outline-none md:text-sm focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]"
                    />
                    <p v-if="errors.temperamentType" class="text-sm text-red-600">
                        {{ errors.temperamentType }}
                    </p>
                </div>

                <div class="space-y-1">
                    <label class="text-sm font-medium" for="passport-conclusion">{{ subjectLabel }} xulosasi</label>
                    <textarea
                        id="passport-conclusion"
                        v-model="form.conclusion"
                        rows="5"
                        :placeholder="`${subjectLabel} uchun xulosani kiriting`"
                        class="placeholder:text-muted-foreground dark:bg-input/30 border-input min-h-28 w-full rounded-md border bg-transparent px-3 py-2 text-sm shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]"
                    />
                    <p v-if="errors.conclusion" class="text-sm text-red-600">
                        {{ errors.conclusion }}
                    </p>
                </div>

                <div v-if="!conclusionOnly" class="grid gap-2">
                    <label class="text-sm font-medium">Xavf darajasi</label>
                    <div v-if="riskFlag" class="flex items-center gap-2 rounded-lg border px-3 py-2.5">
                        <span
                            class="inline-block h-3.5 w-3.5 rounded-full"
                            :style="{ backgroundColor: riskFlag.color }"
                        />
                        <span class="text-sm font-semibold" :style="{ color: riskFlag.color }">
                            {{ riskFlag.label }}
                        </span>
                    </div>
                    <p v-else class="rounded-lg border border-dashed px-3 py-4 text-center text-sm text-muted-foreground">
                        Bayroq biriktirilmagan — passportda xavf darajasi ko'rsatilmaydi.
                    </p>
                </div>

                <p v-if="requestError" class="text-sm text-red-600">
                    {{ requestError }}
                </p>
            </div>
            <DialogFooter class="gap-2 sm:justify-end">
                <Button variant="outline" @click="close">Bekor qilish</Button>
                <Button :disabled="isDownloading" @click="downloadPdf">
                    {{ isDownloading ? 'Yuklanmoqda...' : 'PDF yuklab olish' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
