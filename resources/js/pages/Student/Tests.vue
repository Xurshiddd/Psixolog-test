<script setup lang="ts">
import AppStudentLayout from '@/layouts/AppStudentLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

type StudentModule = {
    id: number;
    name: string;
    description: string | null;
    position: number;
    tests_count: number;
    is_solved: boolean;
    is_locked: boolean;
};

type StudentBlock = {
    id: number | null;
    name: string;
    description: string | null;
    modules: StudentModule[];
};

const props = defineProps<{ blocks: StudentBlock[] }>();

const page = usePage();
const flashError = computed(() => (page.props.flash as any)?.error as string | undefined);

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Student Dashboard', href: dashboard().url },
    { title: 'Tests', href: '/student/index' },
];

const hasModules = computed(() => props.blocks.some((block) => block.modules.length > 0));
</script>

<template>
    <Head title="Testlar" />
    <AppStudentLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
            <div
                v-if="flashError"
                class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-800 dark:bg-amber-900/20 dark:text-amber-300"
            >
                {{ flashError }}
            </div>

            <p
                v-if="!hasModules"
                class="rounded-2xl border border-dashed border-slate-300 px-6 py-12 text-center text-sm text-slate-500 dark:border-slate-600 dark:text-slate-400"
            >
                Hozircha siz uchun faol test mavjud emas.
            </p>

            <section
                v-for="block in blocks"
                :key="block.id ?? 'standalone'"
                class="rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800"
            >
                <header class="border-b border-slate-100 px-6 py-5 dark:border-slate-700">
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">{{ block.name }}</h2>
                    <p v-if="block.description" class="mt-1 text-sm text-slate-600 dark:text-slate-400">
                        {{ block.description }}
                    </p>
                    <p v-else-if="block.id" class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        Modullar ketma-ket yechiladi.
                    </p>
                </header>

                <div class="grid grid-cols-1 gap-4 p-6 md:grid-cols-2 xl:grid-cols-3">
                    <article
                        v-for="module in block.modules"
                        :key="module.id"
                        class="rounded-2xl border p-5 transition"
                        :class="
                            module.is_locked
                                ? 'border-slate-200 bg-slate-50 opacity-70 dark:border-slate-700 dark:bg-slate-900/40'
                                : 'border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-800'
                        "
                    >
                        <div class="flex items-start gap-3">
                            <span
                                v-if="block.id"
                                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl text-sm font-semibold"
                                :class="
                                    module.is_solved
                                        ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400'
                                        : module.is_locked
                                          ? 'bg-slate-200 text-slate-500 dark:bg-slate-700 dark:text-slate-400'
                                          : 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400'
                                "
                            >
                                {{ module.position }}
                            </span>

                            <div class="min-w-0 flex-1">
                                <h3 class="truncate font-semibold text-slate-900 dark:text-slate-100">
                                    {{ module.name }}
                                </h3>
                                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                    Savollar soni: <span class="font-medium">{{ module.tests_count }}</span> ta
                                </p>

                                <span
                                    v-if="module.is_solved"
                                    class="mt-3 inline-flex items-center gap-1.5 rounded-lg bg-emerald-50 px-3 py-1.5 text-sm font-medium text-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-400"
                                >
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                    </svg>
                                    Yechilgan
                                </span>

                                <span
                                    v-else-if="module.is_locked"
                                    class="mt-3 inline-flex items-center gap-1.5 rounded-lg bg-slate-100 px-3 py-1.5 text-sm font-medium text-slate-500 dark:bg-slate-700 dark:text-slate-400"
                                >
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                    </svg>
                                    Oldingi modulni yeching
                                </span>

                                <Link
                                    v-else
                                    :href="`/test/take/${module.id}`"
                                    class="mt-3 inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:outline-none"
                                >
                                    Testni yechish
                                </Link>
                            </div>
                        </div>
                    </article>
                </div>
            </section>
        </div>
    </AppStudentLayout>
</template>
