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
    <div class="min-h-screen w-full flex items-center justify-center bg-gradient-to-br from-indigo-500 via-purple-500 to-pink-500 px-4 py-8 sm:px-6 lg:px-8">
        <div class="w-full max-w-5xl">
            <nav class="flex items-center justify-between mb-8 sm:mb-12">
                <div class="flex-1">
                    <SelectLanguageDropdown />
                </div>
                <div class="flex-1 flex justify-end">
                    <Link 
                        :href="login()" 
                        class="inline-flex items-center gap-2 px-4 sm:px-6 py-2 rounded-full bg-white text-indigo-600 font-semibold text-sm hover:bg-gray-100 transition-all duration-200 shadow-md hover:shadow-lg transform hover:scale-105"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h12.5" />
                        </svg>
                        <span class="hidden sm:inline">{{ trans('admin_kirish') }}</span>
                    </Link>
                </div>
            </nav>
            <div class="rounded-3xl bg-white/95 backdrop-blur-md shadow-2xl overflow-hidden">
                <div class="px-6 sm:px-10 md:px-14 py-10 sm:py-10 md:py-12">
                    <div class="flex justify-center mb-6 sm:mb-8">
                        <div class="w-20 h-20 sm:w-40 sm:h-40 rounded-full bg-gradient-to-br from-indigo-100 to-purple-100 p-1 shadow-lg">
                            <img 
                                src="/logo.jpg" 
                                alt="Institution Logo" 
                                class="w-full h-full rounded-full object-cover border-4 border-white shadow-md"
                            />
                        </div>
                    </div>
                    <div class="text-center mb-8 sm:mb-10">
                        <h1 class="text-2xl sm:text-3xl md:text-4xl lg:text-5xl font-bold text-gray-900 mb-2 leading-tight">
                            Toshkent To'qimachilik va yengil sanoat instituti
                        </h1>
                        <p class="text-lg sm:text-xl md:text-2xl text-indigo-600 font-semibold">
                            Talabalar uchun onlayn diagnostika platformasi
                        </p>
                    </div>
                    <div class="text-center mb-10 sm:mb-12">
                        <h2 class="text-xl sm:text-2xl md:text-3xl font-bold text-gray-800 mb-3">
                            O'zingizni kashf eting va rivojlaning 🌟
                        </h2>
                        
                    </div>
                    <div class="flex justify-center mb-8 sm:mb-10">
                        <a
                            href="/hemis/redirect"
                            class="inline-flex items-center gap-3 px-6 sm:px-8 md:px-10 py-3 sm:py-4 rounded-full bg-gradient-to-r from-purple-600 to-indigo-600 text-white font-bold text-base sm:text-lg shadow-xl hover:shadow-2xl transition-all duration-300 transform hover:scale-110 active:scale-95"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 sm:h-6 sm:w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                            </svg>
                            {{ trans('hemis_login') }}
                        </a>
                    </div>
                    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl p-5 sm:p-6 border border-indigo-100">
                        <p class="text-center text-xs sm:text-sm text-gray-700 leading-relaxed">
                            <span class="font-semibold text-indigo-600">Muhim:</span> Ro'yxatdan o'tish shart emas — tizim Hemis orqali yangi foydalanuvchilarni avtomatik ravishda aniqlaydi.
                        </p>
                    </div>
                </div>
                <div class="h-1 bg-gradient-to-r from-purple-600 via-indigo-600 to-blue-600"></div>
            </div>
            <div class="mt-8 sm:mt-10 text-center">
                <p class="text-white/80 text-xs sm:text-sm">
                    Xush kelibsiz! 👋
                </p>
            </div>
        </div>
    </div>
</template>
