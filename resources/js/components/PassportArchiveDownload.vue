<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';

/**
 * Tanlangan turdagi (talaba / xodim) barcha passportlarni ZIP qilib yuklab
 * olish. Arxiv serverda fon rejimida yasaladi, tugagach avtomatik yuklanadi.
 */
const props = defineProps<{
    defaultRole?: 'student' | 'employee';
    /** Talabalar uchun fakultet tanlash ro'yxati. */
    faculities?: Array<{ id: number; name: string }>;
}>();

type ArchiveStatus = {
    status: 'idle' | 'running' | 'done' | 'failed';
    total?: number;
    processed?: number;
    failed?: number;
    message?: string;
};

const role = ref<'student' | 'employee'>(props.defaultRole ?? 'student');
const faculityId = ref<number | ''>('');
// Test topshirilgan sana oralig'i: "dan" bo'sh — boshidan, "gacha" bo'sh —
// shu kungacha, ikkalasi bo'sh — hammasi.
const dateFrom = ref('');
const dateTo = ref('');
const now = new Date();
const today = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`;
const state = ref<ArchiveStatus>({ status: 'idle' });
const error = ref('');
let poll: ReturnType<typeof setInterval> | null = null;

const running = computed(() => state.value.status === 'running');
const percent = computed(() => {
    const total = state.value.total || 0;
    return total > 0 ? Math.min(100, Math.round(((state.value.processed || 0) / total) * 100)) : 0;
});

// Fakultet faqat talabalarda bor.
const target = () => {
    const params = new URLSearchParams({ role: role.value });
    if (role.value === 'student' && faculityId.value !== '') {
        params.set('faculity_id', String(faculityId.value));
    }
    if (dateFrom.value) {
        params.set('from', dateFrom.value);
    }
    if (dateTo.value) {
        params.set('to', dateTo.value);
    }
    return params;
};

const csrf = () => (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '';

const stopPolling = () => {
    if (poll !== null) {
        clearInterval(poll);
        poll = null;
    }
};

const startDownload = () => {
    window.location.href = `/admin/passports/archive/download?${target()}`;
    state.value = { status: 'idle' };
};

const handleStatus = (data: ArchiveStatus) => {
    state.value = data;

    if (data.status === 'running') {
        if (poll === null) {
            poll = setInterval(fetchStatus, 3000);
        }
        return;
    }

    stopPolling();

    if (data.status === 'done') {
        startDownload();
    } else if (data.status === 'failed') {
        error.value = data.message || 'Arxiv tayyorlashda xatolik yuz berdi.';
    }
};

const fetchStatus = async () => {
    try {
        const res = await fetch(`/admin/passports/archive/status?${target()}`, {
            headers: { Accept: 'application/json' },
        });
        if (res.ok) {
            handleStatus(await res.json());
        }
    } catch {
        // tarmoq uzilishi — keyingi urinishda qayta so'raladi
    }
};

const start = async () => {
    if (running.value) return;
    error.value = '';

    if (dateFrom.value && dateTo.value && dateFrom.value > dateTo.value) {
        error.value = '“Dan” sanasi “gacha” sanasidan keyin bo‘lishi mumkin emas.';
        return;
    }

    try {
        const res = await fetch('/admin/passports/archive', {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf(),
            },
            body: JSON.stringify(Object.fromEntries(target())),
        });

        if (res.status === 422) {
            const payload = await res.json().catch(() => null);
            error.value = Object.values(payload?.errors || {}).flat()[0] as string
                || 'Tanlangan qiymatlar noto‘g‘ri.';
            return;
        }

        if (!res.ok) {
            error.value = 'Arxivni boshlab bo‘lmadi. Sahifani yangilab qayta urinib ko‘ring.';
            return;
        }

        handleStatus(await res.json());
    } catch {
        error.value = 'Server bilan aloqa yo‘q.';
    }
};

// Sahifa yangilansa ham yasalayotgan arxiv holatini davom ettiramiz.
watch([role, faculityId, dateFrom, dateTo], () => {
    stopPolling();
    state.value = { status: 'idle' };
    error.value = '';
    fetchStatus();
});
onMounted(fetchStatus);
onBeforeUnmount(stopPolling);
</script>

<template>
    <div class="flex flex-1 flex-col gap-2">
        <div class="flex flex-wrap gap-2">
            <select
                v-model="role"
                :disabled="running"
                class="rounded-md border border-input bg-background px-3 py-2 text-sm"
            >
                <option value="student">Talabalar</option>
                <option value="employee">Xodimlar</option>
            </select>
            <select
                v-if="role === 'student' && faculities?.length"
                v-model="faculityId"
                :disabled="running"
                class="min-w-0 max-w-48 rounded-md border border-input bg-background px-3 py-2 text-sm"
            >
                <option value="">Barcha fakultetlar</option>
                <option v-for="faculity in faculities" :key="faculity.id" :value="faculity.id">
                    {{ faculity.name }}
                </option>
            </select>
            <label class="flex items-center gap-1 text-sm text-muted-foreground">
                dan
                <input
                    v-model="dateFrom"
                    type="date"
                    :max="dateTo || today"
                    :disabled="running"
                    class="rounded-md border border-input bg-background px-2 py-2 text-sm text-foreground"
                />
            </label>
            <label class="flex items-center gap-1 text-sm text-muted-foreground">
                gacha
                <input
                    v-model="dateTo"
                    type="date"
                    :min="dateFrom || undefined"
                    :max="today"
                    :disabled="running"
                    class="rounded-md border border-input bg-background px-2 py-2 text-sm text-foreground"
                />
            </label>
            <Button variant="outline" class="flex-1" :disabled="running" @click="start">
                <span v-if="running">⏳ Tayyorlanmoqda... {{ state.processed || 0 }}/{{ state.total || 0 }}</span>
                <span v-else>🗂️ Passportlarni yuklab olish (ZIP)</span>
            </Button>
        </div>
        <div v-if="running" class="h-1.5 w-full overflow-hidden rounded bg-muted">
            <div class="h-full bg-primary transition-all" :style="{ width: percent + '%' }" />
        </div>
        <p class="text-xs text-muted-foreground">
            Muddat test topshirilgan sana bo‘yicha. “Dan” bo‘sh bo‘lsa — boshidan, “gacha” bo‘sh bo‘lsa — bugungacha,
            ikkalasi bo‘sh bo‘lsa — hammasi olinadi.
        </p>
        <p v-if="role === 'student'" class="text-xs text-muted-foreground">
            Zudlik bilan hal qilinmagan holati bor talabalar fayli <b class="text-red-600">QIZIL__</b> bilan boshlanadi.
        </p>
        <p v-if="error" class="text-sm text-red-600">{{ error }}</p>
    </div>
</template>
