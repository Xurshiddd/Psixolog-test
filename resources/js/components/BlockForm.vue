<script setup lang="ts">
import { computed, ref } from 'vue';

/**
 * Blok yaratish/tahrirlash formasi. Tanlangan modullar ketma-ket raqamlanadi —
 * shu tartib talaba uchun majburiy yechish ketma-ketligi bo'ladi.
 */
type AvailableModule = { id: number; name: string; is_active: boolean };

const props = defineProps<{
    availableModules: AvailableModule[];
    modelValue: {
        name: string;
        description: string;
        is_active: boolean;
        modules: number[];
    };
    errors: Record<string, string>;
    processing: boolean;
    submitLabel: string;
}>();

const emit = defineEmits<{
    submit: [];
}>();

const form = props.modelValue;
const search = ref('');

const moduleById = computed(() => {
    const map = new Map<number, AvailableModule>();
    props.availableModules.forEach((module) => map.set(module.id, module));
    return map;
});

const selectedModules = computed(() =>
    form.modules
        .map((id) => moduleById.value.get(id))
        .filter((module): module is AvailableModule => Boolean(module)),
);

const unselectedModules = computed(() => {
    const needle = search.value.trim().toLowerCase();

    return props.availableModules.filter(
        (module) =>
            !form.modules.includes(module.id) &&
            (needle === '' || module.name.toLowerCase().includes(needle)),
    );
});

const addModule = (id: number) => {
    if (!form.modules.includes(id)) {
        form.modules.push(id);
    }
};

const removeModule = (id: number) => {
    form.modules = form.modules.filter((moduleId) => moduleId !== id);
};

const move = (index: number, direction: -1 | 1) => {
    const target = index + direction;

    if (target < 0 || target >= form.modules.length) {
        return;
    }

    const next = [...form.modules];
    [next[index], next[target]] = [next[target], next[index]];
    form.modules = next;
};

// `modules.0`, `modules.1` ... ko'rinishidagi element xatolarini ham chiqaramiz.
const moduleError = computed(
    () =>
        props.errors.modules ||
        Object.entries(props.errors).find(([key]) => key.startsWith('modules.'))?.[1],
);
</script>

<template>
    <form class="mx-auto max-w-5xl px-4 py-10 sm:px-6 lg:px-8" @submit.prevent="emit('submit')">
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
            <div class="border-b border-slate-100 px-6 py-5 dark:border-slate-700">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Blok ma'lumotlari</h2>
            </div>

            <div class="grid gap-6 px-6 py-6">
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300" for="block-name">
                        Blok nomi <span class="text-rose-600">*</span>
                    </label>
                    <input
                        id="block-name"
                        v-model="form.name"
                        type="text"
                        placeholder="Masalan: Kirish diagnostikasi"
                        class="mt-2 block w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                    />
                    <p v-if="errors.name" class="mt-1 text-sm text-rose-600">{{ errors.name }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300" for="block-description">
                        Tavsif
                    </label>
                    <textarea
                        id="block-description"
                        v-model="form.description"
                        rows="3"
                        placeholder="Blok haqida qisqacha ma'lumot"
                        class="mt-2 block w-full resize-none rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                    ></textarea>
                    <p v-if="errors.description" class="mt-1 text-sm text-rose-600">{{ errors.description }}</p>
                </div>

                <label class="flex items-center gap-3">
                    <input
                        v-model="form.is_active"
                        type="checkbox"
                        class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500/20"
                    />
                    <span class="text-sm text-slate-700 dark:text-slate-300">Faol (platformada ko'rinadi)</span>
                </label>
            </div>
        </div>

        <div class="mt-6 rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
            <div class="border-b border-slate-100 px-6 py-5 dark:border-slate-700">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Modullar ketma-ketligi</h2>
                <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">
                    Tanlangan modullar quyidagi tartibda yechiladi. Talaba oldingi modulni tugatmaguncha keyingisi
                    ochilmaydi.
                </p>
            </div>

            <div class="grid gap-6 px-6 py-6 lg:grid-cols-2">
                <!-- Tanlangan modullar (tartiblangan) -->
                <div>
                    <h3 class="mb-3 text-sm font-medium text-slate-700 dark:text-slate-300">
                        Tanlangan modullar ({{ selectedModules.length }})
                    </h3>

                    <p
                        v-if="selectedModules.length === 0"
                        class="rounded-xl border border-dashed border-slate-300 px-4 py-8 text-center text-sm text-slate-500 dark:border-slate-600 dark:text-slate-400"
                    >
                        Hali modul tanlanmagan.
                    </p>

                    <ul v-else class="space-y-2">
                        <li
                            v-for="(module, index) in selectedModules"
                            :key="module.id"
                            class="flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 dark:border-slate-700 dark:bg-slate-900/40"
                        >
                            <span
                                class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-blue-600 text-xs font-semibold text-white"
                            >
                                {{ index + 1 }}
                            </span>
                            <span class="flex-1 truncate text-sm text-slate-800 dark:text-slate-200">
                                {{ module.name }}
                                <span v-if="!module.is_active" class="ml-1 text-xs text-amber-600">(o'chirilgan)</span>
                            </span>
                            <button
                                type="button"
                                :disabled="index === 0"
                                class="rounded-lg p-1.5 text-slate-500 transition hover:bg-slate-200 disabled:opacity-30 dark:hover:bg-slate-700"
                                title="Yuqoriga"
                                @click="move(index, -1)"
                            >
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7" />
                                </svg>
                            </button>
                            <button
                                type="button"
                                :disabled="index === selectedModules.length - 1"
                                class="rounded-lg p-1.5 text-slate-500 transition hover:bg-slate-200 disabled:opacity-30 dark:hover:bg-slate-700"
                                title="Pastga"
                                @click="move(index, 1)"
                            >
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                            <button
                                type="button"
                                class="rounded-lg p-1.5 text-rose-600 transition hover:bg-rose-50 dark:hover:bg-rose-900/20"
                                title="Olib tashlash"
                                @click="removeModule(module.id)"
                            >
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </li>
                    </ul>

                    <p v-if="moduleError" class="mt-2 text-sm text-rose-600">{{ moduleError }}</p>
                </div>

                <!-- Mavjud modullar -->
                <div>
                    <h3 class="mb-3 text-sm font-medium text-slate-700 dark:text-slate-300">Mavjud modullar</h3>
                    <input
                        v-model="search"
                        type="search"
                        placeholder="Modul qidirish..."
                        class="mb-3 block w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                    />

                    <p
                        v-if="unselectedModules.length === 0"
                        class="rounded-xl border border-dashed border-slate-300 px-4 py-8 text-center text-sm text-slate-500 dark:border-slate-600 dark:text-slate-400"
                    >
                        Biriktirish uchun bo'sh modul yo'q. Modul boshqa blokka tegishli bo'lsa, avval o'sha blokdan
                        olib tashlang.
                    </p>

                    <ul v-else class="max-h-80 space-y-2 overflow-y-auto pr-1">
                        <li v-for="module in unselectedModules" :key="module.id">
                            <button
                                type="button"
                                class="flex w-full items-center gap-3 rounded-xl border border-slate-200 px-3 py-2.5 text-left transition hover:border-blue-400 hover:bg-blue-50/50 dark:border-slate-700 dark:hover:bg-blue-500/10"
                                @click="addModule(module.id)"
                            >
                                <span class="text-lg leading-none text-blue-600">+</span>
                                <span class="flex-1 truncate text-sm text-slate-800 dark:text-slate-200">
                                    {{ module.name }}
                                    <span v-if="!module.is_active" class="ml-1 text-xs text-amber-600">(o'chirilgan)</span>
                                </span>
                            </button>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="mt-8 flex justify-end">
            <button
                type="submit"
                :disabled="processing"
                class="inline-flex items-center justify-center gap-2 rounded-2xl bg-emerald-600 px-6 py-4 text-base font-semibold text-white shadow-sm transition hover:bg-emerald-700 focus:outline-none focus:ring-4 focus:ring-emerald-600/20 disabled:opacity-60"
            >
                {{ processing ? 'Saqlanmoqda...' : submitLabel }}
            </button>
        </div>
    </form>
</template>
