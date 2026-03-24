<script setup lang="ts">
import AppStudentLayout from '@/layouts/AppStudentLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
const props = defineProps<{ solvedTestsCount: number; modulesCount: number }>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Student Dashboard',
        href: dashboard().url,
    },
];
</script>

<template>
    <Head title="Student Dashboard" />
    <AppStudentLayout :breadcrumbs="breadcrumbs">
        <div
            class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
        >
            <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                <div
                    class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-800"
                >
                    <div class="flex items-center gap-4">
                        <div
                            class="flex h-12 w-12 items-center justify-center rounded-xl bg-blue-50 text-blue-600 dark:bg-blue-900/20 dark:text-blue-400"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="1.5"
                                stroke="currentColor"
                                class="size-6"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z"
                                />
                            </svg>
                        </div>
                        <div>
                            <p
                                class="text-sm font-medium text-slate-500 dark:text-slate-400"
                            >
                                Jami yechilgan modullar soni
                            </p>
                            <h3
                                class="text-2xl font-bold text-slate-900 dark:text-slate-100"
                            >
                                {{ props.solvedTestsCount }}
                            </h3>
                        </div>
                    </div>
                </div>
                <div
                    class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-800"
                >
                    <div class="flex items-center gap-4">
                        <div
                            class="flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-900/20 dark:text-emerald-400"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="1.5"
                                stroke="currentColor"
                                class="size-6"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M4.26 10.147a60.438 60.438 0 00-.491 6.347A48.62 48.62 0 015.824 20.75m12.07-5.272a48.882 48.882 0 013.318.612l.45-2.25m-5.657.134a48.879 48.879 0 013.282-.643m0 .419a24.442 24.442 0 015.572 0m0 0a24.482 24.482 0 013.917.811M6.134 6.75a48.868 48.868 0 015.064 0l1.969-3.94a24.467 24.467 0 00-5.064 0l-1.969 3.94z"
                                />
                            </svg>
                        </div>
                        <div>
                            <p
                                class="text-sm font-medium text-slate-500 dark:text-slate-400"
                            >
                                Jami Modullar
                            </p>
                            <h3
                                class="text-2xl font-bold text-slate-900 dark:text-slate-100"
                            >
                                {{ props.modulesCount }}
                            </h3>
                        </div>
                    </div>
                </div>
            </div>

            <div
                class="relative overflow-hidden rounded-3xl border border-cyan-200 bg-gradient-to-r from-slate-950 via-sky-950 to-cyan-900 p-6 text-white shadow-xl"
            >
                <div
                    class="absolute top-1/2 -left-10 h-32 w-32 -translate-y-1/2 rounded-full bg-cyan-300/20 blur-3xl"
                ></div>
                <div
                    class="absolute top-0 right-0 h-28 w-28 rounded-full bg-white/10 blur-3xl"
                ></div>

                <div
                    class="relative flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between"
                >
                    <div class="max-w-2xl">
                        <p
                            class="mb-2 text-sm font-semibold tracking-[0.28em] text-cyan-200 uppercase"
                        >
                            Mobil ilova
                        </p>
                        <h2 class="text-2xl font-bold">
                            Student APK faylini shu yerdan yuklab oling
                        </h2>
                        <p class="mt-2 text-sm leading-6 text-slate-200">
                            Telefon orqali foydalanish uchun tayyor Android
                            ilova havolasi qo'shildi. Yuklab olib, qurilmangizga
                            o'rnatishingiz mumkin.
                        </p>
                    </div>

                    <a
                        href="/app-release.apk"
                        download="app-release.apk"
                        class="apk-float apk-shimmer inline-flex items-center justify-center gap-3 self-start rounded-full bg-white px-5 py-3 font-semibold text-slate-900 shadow-[0_16px_45px_rgba(255,255,255,0.22)] transition duration-300 hover:-translate-y-1 hover:shadow-[0_22px_60px_rgba(255,255,255,0.3)]"
                    >
                        <span
                            class="flex h-11 w-11 items-center justify-center rounded-full bg-slate-900 text-cyan-300"
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
                                    stroke-width="1.8"
                                    d="M12 3v11m0 0 4-4m-4 4-4-4m-5 8h18"
                                />
                            </svg>
                        </span>
                        <span>APK yuklab olish</span>
                    </a>
                </div>
            </div>
        </div>
    </AppStudentLayout>
    <StudentChatLauncher />
</template>
