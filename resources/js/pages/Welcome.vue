<script setup lang="ts">
import ErrorNotification from '@/components/ErrorNotification.vue';
import SelectLanguageDropdown from '@/components/SelectLanguageDropdown.vue';
import { login } from '@/routes';
import { Head, Link, router } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';

const showVideoModal = ref(false);
const videoRef = ref<HTMLVideoElement | null>(null);

function openVideoModal() {
    showVideoModal.value = true;
    nextTick(() => {
        videoRef.value?.play().catch(() => {});
    });
}

function closeVideoModal() {
    if (videoRef.value) {
        try {
            videoRef.value.pause();
            videoRef.value.currentTime = 0;
        } catch (e) {
            // ignore
        }
    }
    showVideoModal.value = false;
}

// HEMIS orqali kirish popup (talaba yoki hodim tanlash)
const showHemisModal = ref(false);

// HEMIS'da bor, lekin HEMIS orqali kira olmaydigan hodimlar uchun kirish
const showEmployeeModal = ref(false);
const employeeStep = ref<'search' | 'credential'>('search');
const employeeQuery = ref('');
const employeeResults = ref<any[]>([]);
const employeeSearching = ref(false);
const employeeSearched = ref(false);
const selectedEmp = ref<any>(null);
const credential = ref(''); // keyingi kirishlar uchun parol
const birthDate = ref(''); // birinchi kirish: tug'ilgan kun (kun/oy/yil)
const newPassword = ref(''); // birinchi kirish: yangi parol
const newPasswordConfirm = ref(''); // birinchi kirish: parolni tasdiqlash
const empProcessing = ref(false);
const empError = ref<string | null>(null);
let searchDebounce: ReturnType<typeof setTimeout> | null = null;
let searchSeq = 0;

function clearCredentialFields() {
    credential.value = '';
    birthDate.value = '';
    newPassword.value = '';
    newPasswordConfirm.value = '';
}

function clearSearchDebounce() {
    if (searchDebounce !== null) {
        clearTimeout(searchDebounce);
        searchDebounce = null;
    }
}

function resetEmployeeModal() {
    clearSearchDebounce();
    employeeStep.value = 'search';
    employeeQuery.value = '';
    employeeResults.value = [];
    employeeSearched.value = false;
    selectedEmp.value = null;
    clearCredentialFields();
    empError.value = null;
}

// F.I.SH bo'yicha 3 harfdan keyin, yozish to'xtagach 2 soniyada avtomatik qidiruv.
watch(employeeQuery, (value) => {
    clearSearchDebounce();
    empError.value = null;

    if (value.trim().length < 3) {
        employeeResults.value = [];
        employeeSearched.value = false;
        return;
    }

    searchDebounce = setTimeout(() => {
        searchEmployees();
    }, 2000);
});

function openEmployeeModal() {
    resetEmployeeModal();
    showEmployeeModal.value = true;
}

function closeEmployeeModal() {
    showEmployeeModal.value = false;
}

async function searchEmployees() {
    clearSearchDebounce();
    const q = employeeQuery.value.trim();
    empError.value = null;

    if (q.length < 3) {
        empError.value = 'Kamida 3 ta harf kiriting.';
        return;
    }

    employeeSearching.value = true;
    employeeSearched.value = false;
    const seq = ++searchSeq;

    try {
        const res = await fetch(
            `/employee-login/search?query=${encodeURIComponent(q)}`,
            { headers: { Accept: 'application/json' } },
        );
        const data = await res.json();
        // Eskirgan javobni e'tiborsiz qoldiramiz (tez yozilganda).
        if (seq !== searchSeq) return;
        employeeResults.value = Array.isArray(data.employees) ? data.employees : [];
    } catch (e) {
        if (seq !== searchSeq) return;
        empError.value = 'Qidiruvda xatolik yuz berdi.';
        employeeResults.value = [];
    } finally {
        if (seq === searchSeq) {
            employeeSearching.value = false;
            employeeSearched.value = true;
        }
    }
}

function selectEmployee(emp: any) {
    selectedEmp.value = emp;
    clearCredentialFields();
    empError.value = null;
    employeeStep.value = 'credential';
}

function backToSearch() {
    employeeStep.value = 'search';
    clearCredentialFields();
    empError.value = null;
}

// Tug'ilgan kunni kun/oy/yil ko'rinishida avtomatik formatlaydi (20/05/1990).
function formatBirthDate(e: Event) {
    const digits = (e.target as HTMLInputElement).value
        .replace(/\D/g, '')
        .slice(0, 8);

    let out = digits;
    if (digits.length > 4) {
        out = `${digits.slice(0, 2)}/${digits.slice(2, 4)}/${digits.slice(4)}`;
    } else if (digits.length > 2) {
        out = `${digits.slice(0, 2)}/${digits.slice(2)}`;
    }

    birthDate.value = out;
}

function submitEmployeeLogin() {
    if (!selectedEmp.value) return;
    empError.value = null;

    let payload: Record<string, unknown>;

    if (selectedEmp.value.needs_activation) {
        // Birinchi kirish: tug'ilgan kun + yangi parol.
        if (!birthDate.value.trim()) {
            empError.value = "Tug'ilgan kuningizni kiriting.";
            return;
        }
        if (newPassword.value.length < 4) {
            empError.value = "Yangi parol kamida 4 ta belgidan iborat bo'lsin.";
            return;
        }
        if (newPassword.value !== newPasswordConfirm.value) {
            empError.value = 'Parollar mos kelmadi.';
            return;
        }
        payload = {
            user_id: selectedEmp.value.id,
            birth_date: birthDate.value.trim(),
            password: newPassword.value,
        };
    } else {
        // Keyingi kirishlar: faqat parol.
        if (!credential.value) {
            empError.value = 'Parolni kiriting.';
            return;
        }
        payload = {
            user_id: selectedEmp.value.id,
            password: credential.value,
        };
    }

    empProcessing.value = true;

    router.post('/employee-login/authenticate', payload, {
        onError: (errors: Record<string, string>) => {
            empError.value =
                errors.password ||
                errors.birth_date ||
                errors.credential ||
                'Kirishda xatolik yuz berdi.';
        },
        onFinish: () => {
            empProcessing.value = false;
        },
    });
}

onMounted(() => {
    const escHandler = (e: KeyboardEvent) => {
        if (e.key !== 'Escape') return;
        if (showVideoModal.value) closeVideoModal();
        if (showHemisModal.value) showHemisModal.value = false;
        if (showEmployeeModal.value) closeEmployeeModal();
    };
    window.addEventListener('keydown', escHandler);
    onBeforeUnmount(() => window.removeEventListener('keydown', escHandler));
});
</script>

<template>
    <Head title="Toshkent To'qimachilik va yengil sanoat instituti" />
    <ErrorNotification />
    <div
        class="flex min-h-screen w-full items-center justify-center bg-gradient-to-br from-indigo-500 via-purple-500 to-pink-500 px-4 py-8 sm:px-6 lg:px-8"
    >
        <div class="w-full max-w-5xl">
            <nav class="mb-8 flex items-center justify-between sm:mb-12">
                <div class="flex-1">
                    <SelectLanguageDropdown />
                </div>
                <div class="flex flex-1 justify-end">
                    <Link
                        :href="login()"
                        class="inline-flex transform items-center gap-2 rounded-full bg-white px-4 py-2 text-sm font-semibold text-indigo-600 shadow-md transition-all duration-200 hover:scale-105 hover:bg-gray-100 hover:shadow-lg sm:px-6"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-5 w-5"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M11 16l-4-4m0 0l4-4m-4 4h12.5"
                            />
                        </svg>
                        <span class="hidden sm:inline">{{
                            trans('admin_kirish')
                        }}</span>
                    </Link>
                </div>
            </nav>
            <div
                class="overflow-hidden rounded-3xl bg-white/95 shadow-2xl backdrop-blur-md"
            >
                <div class="px-6 py-10 sm:px-10 sm:py-10 md:px-14 md:py-12">
                    <div class="mb-6 flex justify-center sm:mb-8">
                        <div
                            class="h-20 w-20 rounded-full bg-gradient-to-br from-indigo-100 to-purple-100 p-1 shadow-lg sm:h-40 sm:w-40"
                        >
                            <img
                                src="/logo.jpg"
                                alt="Institution Logo"
                                class="h-full w-full rounded-full border-4 border-white object-cover shadow-md"
                            />
                        </div>
                    </div>
                    <div class="mb-8 text-center sm:mb-10">
                        <h1
                            class="mb-2 text-2xl leading-tight font-bold text-gray-900 sm:text-3xl md:text-4xl lg:text-5xl"
                        >
                            Toshkent To'qimachilik va yengil sanoat instituti
                        </h1>
                        <p
                            class="text-lg font-semibold text-indigo-600 sm:text-xl md:text-2xl"
                        >
                            Talabalar uchun onlayn diagnostika platformasi
                        </p>
                    </div>
                    <div class="mb-10 text-center sm:mb-12">
                        <h2
                            class="mb-3 text-xl font-bold text-gray-800 sm:text-2xl md:text-3xl"
                        >
                            O'zingizni kashf eting va rivojlaning 🌟
                        </h2>
                        <button
                            @click.prevent="openVideoModal"
                            type="button"
                            class="mt-2 rounded-lg bg-indigo-600 px-4 py-2 text-white transition-colors duration-200 hover:bg-indigo-700"
                        >
                            Video qo'llanma
                        </button>
                    </div>
                    <!-- Video modal -->
                    <teleport to="body">
                        <div
                            v-if="showVideoModal"
                            class="fixed inset-0 z-50 flex items-center justify-center"
                        >
                            <div
                                class="fixed inset-0 bg-black/60"
                                @click.self="closeVideoModal"
                            ></div>

                            <div class="relative mx-4 w-full max-w-4xl">
                                <div
                                    class="overflow-hidden rounded-lg bg-white shadow-xl dark:bg-slate-800"
                                >
                                    <button
                                        @click="closeVideoModal"
                                        aria-label="close"
                                        class="absolute top-3 right-3 z-20 rounded-full bg-black/40 p-2 text-white hover:bg-black/60"
                                    >
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            class="h-5 w-5"
                                            viewBox="0 0 20 20"
                                            fill="currentColor"
                                        >
                                            <path
                                                fill-rule="evenodd"
                                                d="M10 8.586l4.95-4.95a1 1 0 111.414 1.414L11.414 10l4.95 4.95a1 1 0 01-1.414 1.414L10 11.414l-4.95 4.95A1 1 0 013.636 14.95L8.586 10 3.636 5.05A1 1 0 015.05 3.636L10 8.586z"
                                                clip-rule="evenodd"
                                            />
                                        </svg>
                                    </button>

                                    <div class="w-full bg-black">
                                        <video
                                            ref="videoRef"
                                            class="h-[60vh] w-full bg-black"
                                            controls
                                            playsinline
                                        >
                                            <source
                                                src="/videos/qollanma.mp4"
                                                type="video/mp4"
                                            />
                                            Sizning brauzeringiz video tegni
                                            qo'llab-quvvatlamaydi.
                                        </video>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </teleport>

                    <!-- HEMIS orqali kirish popup: talaba yoki hodim tanlash -->
                    <teleport to="body">
                        <div
                            v-if="showHemisModal"
                            class="fixed inset-0 z-50 flex items-center justify-center p-4"
                        >
                            <div
                                class="fixed inset-0 bg-black/60"
                                @click.self="showHemisModal = false"
                            ></div>
                            <div
                                class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl"
                            >
                                <button
                                    @click="showHemisModal = false"
                                    aria-label="close"
                                    class="absolute top-3 right-3 rounded-full p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-600"
                                >
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        class="h-5 w-5"
                                        viewBox="0 0 20 20"
                                        fill="currentColor"
                                    >
                                        <path
                                            fill-rule="evenodd"
                                            d="M10 8.586l4.95-4.95a1 1 0 111.414 1.414L11.414 10l4.95 4.95a1 1 0 01-1.414 1.414L10 11.414l-4.95 4.95A1 1 0 013.636 14.95L8.586 10 3.636 5.05A1 1 0 015.05 3.636L10 8.586z"
                                            clip-rule="evenodd"
                                        />
                                    </svg>
                                </button>
                                <h3
                                    class="mb-1 text-center text-lg font-bold text-gray-900"
                                >
                                    HEMIS orqali kirish
                                </h3>
                                <p class="mb-6 text-center text-sm text-gray-500">
                                    Qaysi turdagi foydalanuvchi sifatida
                                    kirmoqchisiz?
                                </p>
                                <div class="grid gap-3">
                                    <a
                                        href="/hemis/redirect"
                                        class="flex items-center justify-center gap-2 rounded-xl bg-gradient-to-br from-purple-600 to-indigo-600 px-4 py-3 text-center font-semibold text-white shadow transition-all duration-200 hover:scale-[1.02] active:scale-95"
                                    >
                                        Talaba sifatida
                                    </a>
                                    <a
                                        href="/hemis/employee/redirect"
                                        class="flex items-center justify-center gap-2 rounded-xl border border-indigo-200 bg-white px-4 py-3 text-center font-semibold text-gray-800 shadow-sm transition-all duration-200 hover:scale-[1.02] hover:border-indigo-400 active:scale-95"
                                    >
                                        Hodim sifatida
                                    </a>
                                </div>
                            </div>
                        </div>
                    </teleport>

                    <!-- HEMIS orqali kira olmaydigan hodimlar uchun kirish -->
                    <teleport to="body">
                        <div
                            v-if="showEmployeeModal"
                            class="fixed inset-0 z-50 flex items-center justify-center p-4"
                        >
                            <div
                                class="fixed inset-0 bg-black/60"
                                @click.self="closeEmployeeModal"
                            ></div>
                            <div
                                class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl"
                            >
                                <button
                                    @click="closeEmployeeModal"
                                    aria-label="close"
                                    class="absolute top-3 right-3 rounded-full p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-600"
                                >
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        class="h-5 w-5"
                                        viewBox="0 0 20 20"
                                        fill="currentColor"
                                    >
                                        <path
                                            fill-rule="evenodd"
                                            d="M10 8.586l4.95-4.95a1 1 0 111.414 1.414L11.414 10l4.95 4.95a1 1 0 01-1.414 1.414L10 11.414l-4.95 4.95A1 1 0 013.636 14.95L8.586 10 3.636 5.05A1 1 0 015.05 3.636L10 8.586z"
                                            clip-rule="evenodd"
                                        />
                                    </svg>
                                </button>

                                <!-- 1-qadam: F.I.SH bo'yicha qidirish -->
                                <template v-if="employeeStep === 'search'">
                                    <h3
                                        class="mb-1 text-lg font-bold text-gray-900"
                                    >
                                        Hodim sifatida kirish
                                    </h3>
                                    <p class="mb-4 text-sm text-gray-500">
                                        Ismingiz (F.I.SH) bo'yicha qidiring va
                                        ro'yxatdan o'zingizni tanlang.
                                    </p>
                                    <form
                                        @submit.prevent="searchEmployees"
                                        class="flex gap-2"
                                    >
                                        <input
                                            v-model="employeeQuery"
                                            type="text"
                                            placeholder="Familiya Ism..."
                                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-900 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none"
                                        />
                                        <button
                                            type="submit"
                                            :disabled="employeeSearching"
                                            class="shrink-0 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-60"
                                        >
                                            {{ employeeSearching ? '...' : 'Qidirish' }}
                                        </button>
                                    </form>
                                    <p
                                        v-if="empError"
                                        class="mt-2 text-sm text-red-600"
                                    >
                                        {{ empError }}
                                    </p>
                                    <div
                                        class="mt-4 max-h-64 space-y-1 overflow-y-auto"
                                    >
                                        <button
                                            v-for="emp in employeeResults"
                                            :key="emp.id"
                                            type="button"
                                            @click="selectEmployee(emp)"
                                            class="flex w-full items-center gap-3 rounded-lg border border-transparent px-2 py-2 text-left transition-colors hover:border-indigo-200 hover:bg-indigo-50"
                                        >
                                            <div
                                                v-if="emp.picture"
                                                class="h-10 w-10 shrink-0 overflow-hidden rounded-full bg-gray-100"
                                            >
                                                <img
                                                    :src="emp.picture"
                                                    :alt="emp.name"
                                                    class="h-full w-full object-cover"
                                                />
                                            </div>
                                            <div
                                                v-else
                                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-sm font-semibold text-indigo-600"
                                            >
                                                {{ emp.name.charAt(0).toUpperCase() }}
                                            </div>
                                            <div class="min-w-0">
                                                <p
                                                    class="truncate text-sm font-medium text-gray-900"
                                                >
                                                    {{ emp.name }}
                                                </p>
                                                <p
                                                    v-if="emp.department"
                                                    class="truncate text-xs text-gray-500"
                                                >
                                                    {{ emp.department }}
                                                </p>
                                            </div>
                                        </button>
                                        <p
                                            v-if="
                                                employeeSearched &&
                                                !employeeSearching &&
                                                employeeResults.length === 0
                                            "
                                            class="py-4 text-center text-sm text-gray-500"
                                        >
                                            Hech qanday hodim topilmadi.
                                        </p>
                                    </div>
                                </template>

                                <!-- 2-qadam: tug'ilgan kun / parol -->
                                <template v-else>
                                    <button
                                        type="button"
                                        @click="backToSearch"
                                        class="mb-3 text-sm font-medium text-indigo-600 hover:underline"
                                    >
                                        &larr; Orqaga
                                    </button>
                                    <h3
                                        class="mb-1 text-lg font-bold text-gray-900"
                                    >
                                        {{ selectedEmp?.name }}
                                    </h3>
                                    <p
                                        v-if="selectedEmp?.position"
                                        class="mb-4 text-sm text-gray-500"
                                    >
                                        {{ selectedEmp.position }}
                                    </p>
                                    <form
                                        @submit.prevent="submitEmployeeLogin"
                                        class="space-y-3"
                                    >
                                        <template
                                            v-if="selectedEmp?.needs_activation"
                                        >
                                            <p
                                                class="rounded-lg bg-indigo-50 px-3 py-2 text-xs text-indigo-700"
                                            >
                                                Birinchi kirish. Tug'ilgan
                                                kuningizni tasdiqlang va o'zingizga
                                                yangi parol o'ylab toping — keyingi
                                                kirishlarda shu parol so'raladi.
                                            </p>
                                            <div>
                                                <label
                                                    class="mb-1 block text-sm font-medium text-gray-700"
                                                >
                                                    Tug'ilgan kuningiz (kun/oy/yil)
                                                </label>
                                                <input
                                                    v-model="birthDate"
                                                    type="text"
                                                    inputmode="numeric"
                                                    placeholder="kun/oy/yil (masalan: 05/06/1990)"
                                                    @input="formatBirthDate"
                                                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-900 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none"
                                                />
                                            </div>
                                            <div>
                                                <label
                                                    class="mb-1 block text-sm font-medium text-gray-700"
                                                >
                                                    Yangi parol
                                                </label>
                                                <input
                                                    v-model="newPassword"
                                                    type="password"
                                                    autocomplete="new-password"
                                                    placeholder="Yangi parol"
                                                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-900 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none"
                                                />
                                            </div>
                                            <div>
                                                <label
                                                    class="mb-1 block text-sm font-medium text-gray-700"
                                                >
                                                    Parolni tasdiqlang
                                                </label>
                                                <input
                                                    v-model="newPasswordConfirm"
                                                    type="password"
                                                    autocomplete="new-password"
                                                    placeholder="Parolni qayta kiriting"
                                                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-900 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none"
                                                />
                                            </div>
                                        </template>
                                        <div v-else>
                                            <label
                                                class="mb-1 block text-sm font-medium text-gray-700"
                                            >
                                                Parol
                                            </label>
                                            <input
                                                v-model="credential"
                                                type="password"
                                                autocomplete="current-password"
                                                placeholder="Parolingiz"
                                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-900 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none"
                                            />
                                        </div>
                                        <p
                                            v-if="empError"
                                            class="text-sm text-red-600"
                                        >
                                            {{ empError }}
                                        </p>
                                        <button
                                            type="submit"
                                            :disabled="empProcessing"
                                            class="w-full rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-60"
                                        >
                                            {{ empProcessing ? 'Kirish...' : 'Kirish' }}
                                        </button>
                                    </form>
                                </template>
                            </div>
                        </div>
                    </teleport>

                    <!--
                        Kirish turlari:
                        1) HEMIS orqali kirish — bosilganda talaba yoki hodim
                           tanlash popupi chiqadi (OAuth).
                        2) Hodim sifatida kirish — HEMIS'da bor, lekin HEMIS
                           orqali kira olmaydiganlar F.I.SH bo'yicha qidirib kiradi.
                        3) Ishga qabul qilinmaganlar uchun ro'yxatdan o'tish.
                    -->
                    <div
                        class="mx-auto mb-8 grid max-w-3xl gap-4 sm:mb-10 sm:grid-cols-3"
                    >
                        <button
                            type="button"
                            @click="showHemisModal = true"
                            class="group flex flex-col items-center justify-center gap-3 rounded-2xl bg-gradient-to-br from-purple-600 to-indigo-600 px-6 py-6 text-center font-bold text-white shadow-xl transition-all duration-300 hover:scale-105 hover:shadow-2xl active:scale-95"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="h-8 w-8"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="1.8"
                                    d="M11 16l-4-4m0 0l4-4m-4 4h12.5M15 4h2a2 2 0 012 2v12a2 2 0 01-2 2h-2"
                                />
                            </svg>
                            <span class="text-base sm:text-lg">HEMIS orqali kirish</span>
                        </button>

                        <button
                            type="button"
                            @click="openEmployeeModal"
                            class="group flex flex-col items-center justify-center gap-3 rounded-2xl border border-indigo-200 bg-white px-6 py-6 text-center font-bold text-gray-800 shadow-sm transition-all duration-300 hover:scale-105 hover:border-indigo-400 hover:shadow-lg active:scale-95"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="h-8 w-8 text-indigo-600"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="1.8"
                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                                />
                            </svg>
                            <span class="text-base sm:text-lg">Hodim sifatida kirish</span>
                            <span class="text-xs font-normal text-gray-500">HEMIS orqali kira olmasangiz</span>
                        </button>

                        <Link
                            :href="'/guest/register'"
                            class="group flex flex-col items-center justify-center gap-3 rounded-2xl border border-indigo-200 bg-white px-6 py-6 text-center font-bold text-gray-800 shadow-sm transition-all duration-300 hover:scale-105 hover:border-indigo-400 hover:shadow-lg active:scale-95"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="h-8 w-8 text-indigo-600"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="1.8"
                                    d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"
                                />
                            </svg>
                            <span class="text-base sm:text-lg">Ishga qabul qilinmaganlar uchun</span>
                        </Link>
                    </div>
                    <div
                        class="rounded-xl border border-indigo-100 bg-white p-5 shadow-sm sm:p-6"
                    >
                        <h3
                            class="text-center text-base font-bold text-gray-900 sm:text-lg"
                        >
                            Agar platformada muammo bo'lsa
                        </h3>
                        <div class="mt-4 grid gap-4 sm:grid-cols-2">
                            <div
                                class="rounded-lg border border-blue-100 bg-blue-50 p-4"
                            >
                                <p
                                    class="text-sm leading-relaxed text-gray-700"
                                >
                                    <span
                                        class="mr-2 inline-flex h-6 w-6 items-center justify-center rounded-full bg-blue-600 text-xs font-bold text-white"
                                        >1</span
                                    >
                                    Telegram profil:
                                    <a
                                        href="https://t.me/Muhammad_alayhissalom_ummati"
                                        class="font-semibold text-blue-700 underline-offset-4 hover:underline"
                                    >
                                        @Muhammad_alayhissalom_ummati
                                    </a>
                                </p>
                                <p>
                                    ga murojaat qiling va muammoingizni batafsil tushuntiring. Yordam berish uchun qo'limizdan kelganicha harakat qilamiz.
                                </p>
                            </div>
                            <div
                                class="rounded-lg border border-amber-100 bg-amber-50 p-4"
                            >
                                <p
                                    class="text-sm leading-relaxed text-gray-700"
                                >
                                    <span
                                        class="mr-2 inline-flex h-6 w-6 items-center justify-center rounded-full bg-amber-500 text-xs font-bold text-white"
                                        >2</span
                                    >
                                    Agar saytga kira olmasangiz
                                    <a
                                        href="https://student.ttyesi.uz"
                                        class="font-semibold text-amber-700 underline-offset-4 hover:underline"
                                    >
                                        student.ttyesi.uz
                                    </a>
                                    ga kirib ko'ring. Login yoki parol xato deb
                                    chiqsa, parolni tiklab qayta urinib ko'ring.
                                    Agar boshqa xatolik chiqsa, registrator
                                    ofisidan profilni ochib berishini so'rang.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <div
                    class="h-1 bg-gradient-to-r from-purple-600 via-indigo-600 to-blue-600"
                ></div>
            </div>
            <div class="mt-8 text-center sm:mt-10">
                <p class="text-xs text-white/80 sm:text-sm">
                    Xush kelibsiz! 👋
                </p>
            </div>
        </div>
    </div>
</template>
