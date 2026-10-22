<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';

/**
 * Tanlangan turdagi (talaba / xodim) barcha passportlarni ZIP qilib yuklab
 * olish. Arxiv serverda fon rejimida yasaladi, tugagach avtomatik yuklanadi.
 */
const props = defineProps<{
    defaultRole?: 'student' | 'employee';
}>();

type ArchiveStatus = {
    status: 'idle' | 'running' | 'done' | 'failed';
    total?: number;
    processed?: number;
    failed?: number;
    message?: string;
};

const role = ref<'student' | 'employee'>(props.defaultRole ?? 'student');
const state = ref<ArchiveStatus>({ status: 'idle' });
const error = ref('');
let poll: ReturnType<typeof setInterval> | null = null;

const running = computed(() => state.value.status === 'running');
const percent = computed(() => {
    const total = state.value.total || 0;
    return total > 0 ? Math.min(100, Math.round(((state.value.processed || 0) / total) * 100)) : 0;
});

const csrf = () => (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '';

const stopPolling = () => {
    if (poll !== null) {
        clearInterval(poll);
        poll = null;
    }
};

const startDownload = () => {
    window.location.href = `/admin/passports/archive/download?role=${role.value}`;
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
        const res = await fetch(`/admin/passports/archive/status?role=${role.value}`, {
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

    try {
        const res = await fetch('/admin/passports/archive', {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf(),
            },
            body: JSON.stringify({ role: role.value }),
        });

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
watch(role, () => {
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
        <div class="flex gap-2">
            <select
                v-model="role"
                :disabled="running"
                class="rounded-md border border-input bg-background px-3 py-2 text-sm"
            >
                <option value="student">Talabalar</option>
                <option value="employee">Xodimlar</option>
            </select>
            <Button variant="outline" class="flex-1" :disabled="running" @click="start">
                <span v-if="running">⏳ Tayyorlanmoqda... {{ state.processed || 0 }}/{{ state.total || 0 }}</span>
                <span v-else>🗂️ Passportlarni yuklab olish (ZIP)</span>
            </Button>
        </div>
        <div v-if="running" class="h-1.5 w-full overflow-hidden rounded bg-muted">
            <div class="h-full bg-primary transition-all" :style="{ width: percent + '%' }" />
        </div>
        <p v-if="error" class="text-sm text-red-600">{{ error }}</p>
    </div>
</template>
