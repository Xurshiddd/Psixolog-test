<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { home } from '@/routes';
import { Link } from '@inertiajs/vue3';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div
        class="flex min-h-svh w-full justify-center bg-gradient-to-br from-indigo-500 via-purple-500 to-pink-500 px-4 py-8 sm:px-6 lg:py-12"
    >
        <div class="w-full max-w-2xl">
            <div
                class="overflow-hidden rounded-3xl bg-white/95 shadow-2xl backdrop-blur-md dark:bg-slate-900/95"
            >
                <div class="px-6 py-8 sm:px-10 sm:py-10">
                    <div class="mb-6 flex flex-col items-center gap-3 text-center">
                        <Link :href="home()" class="flex items-center gap-2">
                            <AppLogoIcon class="size-9 fill-current text-indigo-600 dark:text-indigo-400" />
                        </Link>
                        <div>
                            <h1
                                v-if="title"
                                class="text-xl font-bold tracking-tight text-gray-900 dark:text-slate-100 sm:text-2xl"
                            >
                                {{ title }}
                            </h1>
                            <p
                                v-if="description"
                                class="mt-1 text-sm text-gray-600 dark:text-slate-400"
                            >
                                {{ description }}
                            </p>
                        </div>
                    </div>

                    <slot />
                </div>
            </div>
        </div>
    </div>
</template>
