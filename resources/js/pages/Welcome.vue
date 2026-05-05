<script setup lang="ts">
import ErrorNotification from '@/components/ErrorNotification.vue';
import SelectLanguageDropdown from '@/components/SelectLanguageDropdown.vue';
import { login } from '@/routes';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { nextTick, onBeforeUnmount, onMounted, ref } from 'vue';

const showVideoModal = ref(false);
const videoRef = ref<HTMLVideoElement | null>(null);
const hemisLoginForm = useForm({
    login: '',
    password: '',
});

function submitHemisLogin() {
    hemisLoginForm.post('/hemis/login', {
        preserveScroll: true,
    });
}

function openVideoModal() {
    showVideoModal.value = true;
    // play after DOM updates
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

onMounted(() => {
    const escHandler = (e: KeyboardEvent) => {
        if (e.key === 'Escape' && showVideoModal.value) {
            closeVideoModal();
        }
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
                    <div
                        class="relative isolate mb-8 overflow-hidden rounded-[2rem] bg-slate-950 px-6 py-6 text-white shadow-2xl sm:mb-10 sm:px-8 sm:py-7"
                    >
                        <div
                            class="absolute top-1/2 -left-10 h-32 w-32 -translate-y-1/2 rounded-full bg-cyan-400/25 blur-3xl"
                        ></div>
                        <div
                            class="absolute top-0 right-0 h-28 w-28 rounded-full bg-fuchsia-400/20 blur-3xl"
                        ></div>
                        <div
                            class="absolute inset-x-0 bottom-0 h-px bg-gradient-to-r from-transparent via-white/50 to-transparent"
                        ></div>

                        <div
                            class="relative grid items-center gap-6 md:grid-cols-[1.4fr_auto]"
                        >
                            <div>
                                <p
                                    class="mb-2 text-sm font-semibold tracking-[0.3em] text-cyan-300 uppercase"
                                >
                                    Android ilova
                                </p>
                                <h3
                                    class="text-2xl leading-tight font-bold sm:text-3xl"
                                >
                                    Student mobil ilovasini tez yuklab oling
                                </h3>
                                <p
                                    class="mt-3 max-w-2xl text-sm leading-6 text-slate-300 sm:text-base"
                                >
                                    Telefon orqali foydalanish uchun tayyor
                                    Android ilova havolasi qo'shildi. Yuklab
                                    olib, qurilmangizga o'rnatishingiz mumkin.
                                </p>
                            </div>

                            <a
                                href="/app-release.apk"
                                download="app-release.apk"
                                class="apk-float apk-shimmer group inline-flex items-center justify-center gap-3 rounded-full border border-white/20 bg-white px-6 py-4 font-bold text-slate-900 shadow-[0_20px_60px_rgba(34,211,238,0.28)] transition duration-300 hover:-translate-y-1 hover:shadow-[0_25px_80px_rgba(34,211,238,0.38)]"
                            >
                                <span
                                    class="flex h-12 w-12 items-center justify-center rounded-full bg-slate-900 text-cyan-300 transition duration-300 group-hover:scale-110"
                                >
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        class="h-6 w-6"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="1.8"
                                            d="M12 3v11m0 0 4-4m-4 4-4-4m-5 8h18"
                                        />
                                    </svg>
                                </span>
                                <span class="text-left">
                                    <span class="block text-base"
                                        >APK yuklab olish</span
                                    >
                                    <span
                                        class="block text-xs font-medium text-slate-500"
                                    >
                                        <span class="font-mono"
                                            >/app-release.apk</span
                                        >
                                    </span>
                                </span>
                            </a>
                        </div>

                        <div
                            class="relative mt-6 grid gap-3 rounded-2xl border border-cyan-300/30 bg-white/10 p-4 shadow-lg backdrop-blur-sm sm:grid-cols-2 sm:p-5"
                        >
                            <div
                                class="rounded-2xl border border-white/10 bg-slate-900/70 p-4"
                            >
                                <p
                                    class="text-xs font-semibold tracking-[0.25em] text-cyan-300 uppercase"
                                >
                                    Login
                                </p>
                                <p class="mt-2 text-lg font-bold text-white">
                                    HEMIS logini
                                </p>
                            </div>
                            <div
                                class="rounded-2xl border border-amber-300/20 bg-amber-400/10 p-4"
                            >
                                <p
                                    class="text-xs font-semibold tracking-[0.25em] text-amber-200 uppercase"
                                >
                                    Parol
                                </p>
                                <p class="mt-2 text-lg font-bold text-white">
                                    Passport seria ID
                                </p>
                                <p class="mt-2 text-sm text-amber-100">
                                    Masalan:
                                    <span
                                        class="rounded-full bg-white/10 px-3 py-1 font-mono text-white"
                                    >
                                        AB4567898
                                    </span>
                                </p>
                            </div>
                        </div>
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
                    <div class="mb-8 flex justify-center sm:mb-10">
                        <a
                            href="/hemis/redirect"
                            class="inline-flex transform items-center gap-3 rounded-full bg-gradient-to-r from-purple-600 to-indigo-600 px-6 py-3 text-base font-bold text-white shadow-xl transition-all duration-300 hover:scale-110 hover:shadow-2xl active:scale-95 sm:px-8 sm:py-4 sm:text-lg md:px-10"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="h-5 w-5 sm:h-6 sm:w-6"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"
                                />
                            </svg>
                            {{ trans('hemis_login') }}
                        </a>
                    </div>
                    <form
                        class="mx-auto mb-8 grid max-w-2xl gap-4 rounded-2xl border border-indigo-100 bg-indigo-50/70 p-4 shadow-sm sm:mb-10 sm:grid-cols-[1fr_1fr_auto] sm:items-start sm:p-5"
                        @submit.prevent="submitHemisLogin"
                    >
                        <div>
                            <label
                                for="hemis-login"
                                class="mb-1 block text-sm font-semibold text-gray-800"
                            >
                                HEMIS login
                            </label>
                            <input
                                id="hemis-login"
                                v-model="hemisLoginForm.login"
                                type="text"
                                inputmode="numeric"
                                autocomplete="username"
                                class="h-11 w-full rounded-lg border border-indigo-200 bg-white px-3 text-gray-900 shadow-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200"
                                placeholder="999211100073"
                            />
                            <p
                                v-if="hemisLoginForm.errors.login"
                                class="mt-1 text-sm text-red-600"
                            >
                                {{ hemisLoginForm.errors.login }}
                            </p>
                        </div>
                        <div>
                            <label
                                for="hemis-password"
                                class="mb-1 block text-sm font-semibold text-gray-800"
                            >
                                Parol
                            </label>
                            <input
                                id="hemis-password"
                                v-model="hemisLoginForm.password"
                                type="password"
                                autocomplete="current-password"
                                class="h-11 w-full rounded-lg border border-indigo-200 bg-white px-3 text-gray-900 shadow-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200"
                                placeholder="DD7777777"
                            />
                            <p
                                v-if="hemisLoginForm.errors.password"
                                class="mt-1 text-sm text-red-600"
                            >
                                {{ hemisLoginForm.errors.password }}
                            </p>
                        </div>
                        <button
                            type="submit"
                            :disabled="hemisLoginForm.processing"
                            class="mt-1 inline-flex h-11 items-center justify-center rounded-lg bg-indigo-600 px-5 text-sm font-bold text-white shadow-md transition hover:bg-indigo-700 disabled:cursor-not-allowed disabled:bg-indigo-400 sm:mt-6"
                        >
                            {{
                                hemisLoginForm.processing
                                    ? 'Tekshirilmoqda...'
                                    : 'Kirish'
                            }}
                        </button>
                    </form>
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
