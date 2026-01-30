<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import { login, hemis_redirect } from '@/routes'
import { trans } from 'laravel-vue-i18n';
import SelectLanguageDropdown from '@/components/SelectLanguageDropdown.vue'
import ErrorNotification from '@/components/ErrorNotification.vue'
</script>

<template>
    <Head title="S-XA Diagnosyikasi" />
    
    <ErrorNotification />

    <div class="min-h-screen flex items-center justify-center bg-linear-to-br from-indigo-400 via-purple-400 to-pink-400 p-6">
        <div class="w-full max-w-4xl rounded-2xl bg-white/90 p-8 shadow-2xl backdrop-blur-md">
            <header class="relative mb-6">
                <!-- Mobile top row: language + login -->
                <div class="flex items-center justify-between md:hidden">
                    <div>
                        <SelectLanguageDropdown />
                    </div>
                    <div>
                        <Link :href="login()" class="text-sm rounded-full bg-indigo-600 text-white px-3 py-1">
                            {{ trans('admin_kirish') }}
                        </Link>
                    </div>
                </div>

                <!-- Desktop / tablet absolute navs -->
                <nav class="hidden md:block absolute left-8 top-8">
                    <SelectLanguageDropdown />
                </nav>
                <nav class="hidden md:block absolute right-8 top-8">
                    <Link :href="login()" class="text-sm rounded-full bg-indigo-600 text-white hover:underline p-2">
                        {{ trans('admin_kirish') }}
                    </Link>
                </nav>

                <!-- Centered heading -->
                <div class="mt-6 md:mt-0 text-center px-4">
                    <div class="inline-block px-4 py-1 rounded-full bg-indigo-600 text-white text-xs font-semibold tracking-wide">
                        <h3 class="m-0">S-XA DIAGNOSTIKASI</h3>
                    </div>
                    <h1 class="mt-5 p-5 text-2xl md:text-3xl font-bold text-gray-900">O'zingizni kashf eting va rivojlang</h1>
                    <p class="mt-2 text-sm text-gray-600">Qisqa va intuitiv onlayn diagnostika. Umumiy 41 ta savol — taxminan 30 daqiqa.</p>
                </div>
            </header>

            <main class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-white rounded-xl p-6 shadow-sm">
                    <h3 class="text-lg font-semibold mb-2 dark:text-gray-700">{{ trans('bosqich1') }}</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-70">Sizning kelajagingiz haqida 20 ta fikrlar beriladi. Marhamat qilib, diqqat bilan o'qing va hozirgi vaqtdagi his-tuyg'ularingizni aks ettiring.</p>
                </div>

                <div class="bg-white rounded-xl p-6 shadow-sm">
                    <h3 class="text-lg font-semibold mb-2 dark:text-gray-700">{{ trans('bosqich2') }}</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-70">Sizga 21 ta guruhdan iborat fikrlar to'plami taqdim etiladi. Har bir guruh uchun tanlov qiling va yakuniy bahoni oling.</p>
                </div>

                <div class="md:col-span-2 flex items-center justify-center mt-2">
                    <a
                        href="/hemis/redirect"
                        class="inline-flex items-center gap-3 rounded-full bg-linear-to-r from-purple-600 to-indigo-600 px-8 py-3 text-white text-sm font-semibold shadow-lg hover:opacity-95"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 8a6 6 0 11-8 0" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14v7" />
                        </svg>
                        {{ trans('hemis_login') }}
                    </a>
                </div>
                <p class="md:col-span-2 text-center text-xs text-gray-700/70 mt-4">Ro'yxatdan o'tish shart emas — tizim Hemis orqali yangi foydalanuvchilarni aniqlaydi. Test taxminan 30 daqiqa davom etadi.</p>
            </main>
        </div>
    </div>
</template>
