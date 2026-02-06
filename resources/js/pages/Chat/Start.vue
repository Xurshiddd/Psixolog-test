<script setup lang="ts">
import { useForm, Head, router } from '@inertiajs/vue3'

defineProps<{ psychologists: { id: number; name: string }[] }>()

const form = useForm({
  psychologist_id: null as number | null,
})

function submit() {
  router.post('/chat/start', form.data())
}
</script>

<template>
  <Head title="Murojaat" />
  <div class="p-6 max-w-xl mx-auto">
    <h1 class="text-xl font-semibold mb-4">Psixologga murojaat</h1>

    <select v-model="form.psychologist_id" class="border rounded p-2 w-full">
      <option :value="null" disabled>Psixolog tanlang</option>
      <option v-for="p in psychologists" :key="p.id" :value="p.id">{{ p.name }}</option>
    </select>

    <button
      class="mt-4 px-4 py-2 rounded bg-blue-600 text-white disabled:opacity-60"
      :disabled="!form.psychologist_id || form.processing"
      @click="submit"
    >
      Chatni boshlash
    </button>
  </div>
</template>
