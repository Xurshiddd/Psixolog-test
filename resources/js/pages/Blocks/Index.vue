<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

type BlockModule = { id: number; name: string; is_active: boolean; position: number };

defineProps<{
    blocks: any;
    blocksCount: number;
    unassignedModulesCount: number;
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Bloklar', href: '/blocks' }];

const loadingId = ref<number | null>(null);

const toggleStatus = (blockId: number) => {
    loadingId.value = blockId;
    router.patch(
        '/blocks/change-status',
        { block_id: blockId },
        { preserveScroll: true, onFinish: () => (loadingId.value = null) },
    );
};

const destroy = (block: { id: number; name: string }) => {
    if (!confirm(`"${block.name}" bloki o'chirilsinmi? Modullar o'chmaydi, faqat blokdan ajratiladi.`)) {
        return;
    }

    loadingId.value = block.id;
    router.delete(`/blocks/${block.id}`, {
        preserveScroll: true,
        onFinish: () => (loadingId.value = null),
    });
};

const goToPage = (page: number) => {
    router.get(window.location.pathname, { page }, { preserveScroll: true });
};
</script>

<template>
    <Head title="Bloklar" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-800">
                    <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Jami bloklar</p>
                    <h3 class="text-2xl font-bold text-slate-900 dark:text-slate-100">{{ blocksCount }}</h3>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-800">
                    <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Blokka biriktirilmagan modullar</p>
                    <h3 class="text-2xl font-bold text-slate-900 dark:text-slate-100">{{ unassignedModulesCount }}</h3>
                </div>
            </div>

            <div class="flex items-center justify-end">
                <Link
                    href="/blocks/create"
                    class="inline-flex items-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-blue-700"
                >
                    Blok yaratish
                </Link>
            </div>

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100 dark:divide-slate-700">
                        <thead>
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400">ID</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400">Nomi</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400">
                                    Modullar (ketma-ketlik)
                                </th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-400">Status</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-400">Harakatlar</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                            <tr v-if="blocks.data.length === 0">
                                <td colspan="5" class="px-4 py-10 text-center text-sm text-slate-500 dark:text-slate-400">
                                    Hali blok yaratilmagan.
                                </td>
                            </tr>
                            <tr
                                v-for="block in blocks.data"
                                :key="block.id"
                                class="transition-colors hover:bg-slate-50 dark:hover:bg-slate-700/50"
                            >
                                <td class="px-4 py-3 text-sm">{{ block.id }}</td>
                                <td class="px-4 py-3">
                                    <p class="text-sm font-medium text-slate-900 dark:text-slate-100">{{ block.name }}</p>
                                    <p v-if="block.description" class="mt-0.5 max-w-md truncate text-xs text-slate-500 dark:text-slate-400">
                                        {{ block.description }}
                                    </p>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap items-center gap-1.5">
                                        <span
                                            v-for="(module, index) in (block.modules as BlockModule[])"
                                            :key="module.id"
                                            class="inline-flex items-center gap-1 rounded-lg px-2 py-1 text-xs"
                                            :class="
                                                module.is_active
                                                    ? 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-200'
                                                    : 'bg-amber-50 text-amber-700 line-through dark:bg-amber-900/20 dark:text-amber-400'
                                            "
                                            :title="module.is_active ? '' : 'Modul o\'chirilgan — ketma-ketlikda hisobga olinmaydi'"
                                        >
                                            <span class="font-semibold">{{ index + 1 }}.</span> {{ module.name }}
                                        </span>
                                        <span v-if="block.modules.length === 0" class="text-xs text-slate-400">—</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <label class="inline-flex cursor-pointer items-center" :style="{ opacity: loadingId === block.id ? 0.6 : 1 }">
                                        <input
                                            :checked="block.is_active"
                                            :disabled="loadingId === block.id"
                                            type="checkbox"
                                            class="peer sr-only"
                                            @change="toggleStatus(block.id)"
                                        />
                                        <div
                                            class="peer relative h-6 w-11 rounded-full after:absolute after:top-[2px] after:left-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:ring-4 peer-focus:outline-none dark:border-gray-600"
                                            :class="
                                                block.is_active
                                                    ? 'bg-green-600 peer-focus:ring-green-300 dark:bg-green-600'
                                                    : 'bg-gray-200 peer-focus:ring-gray-300 dark:bg-gray-700'
                                            "
                                        ></div>
                                    </label>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-center gap-2">
                                        <Link
                                            :href="`/blocks/${block.id}/edit`"
                                            class="rounded-lg p-2 text-blue-500 transition-colors hover:bg-blue-50 dark:text-blue-400 dark:hover:bg-blue-900/20"
                                            title="Tahrirlash"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                                            </svg>
                                        </Link>
                                        <button
                                            type="button"
                                            :disabled="loadingId === block.id"
                                            class="rounded-lg p-2 text-red-500 transition-colors hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/20"
                                            title="O'chirish"
                                            @click="destroy(block)"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div
                    v-if="blocks.last_page > 1"
                    class="flex items-center justify-between border-t border-slate-100 bg-slate-50 px-4 py-4 dark:border-slate-700 dark:bg-slate-900/50"
                >
                    <div class="text-sm text-slate-600 dark:text-slate-400">
                        {{ blocks.from }}–{{ blocks.to }} / {{ blocks.total }}
                    </div>
                    <div class="flex items-center gap-1">
                        <template v-for="(link, index) in blocks.links.slice(1, -1)" :key="index">
                            <button
                                :disabled="!link.url"
                                :class="[
                                    'inline-flex items-center justify-center rounded-lg px-3 py-2 text-sm font-medium transition-colors',
                                    link.active
                                        ? 'bg-blue-600 text-white'
                                        : link.url
                                          ? 'border border-slate-300 text-slate-700 hover:bg-slate-100 dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-800'
                                          : 'cursor-not-allowed text-slate-400',
                                ]"
                                @click="link.url && goToPage(parseInt(link.label))"
                                v-html="link.label"
                            ></button>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
