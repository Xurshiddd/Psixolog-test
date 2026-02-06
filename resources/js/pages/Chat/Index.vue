<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import type { ConversationListItem } from '@/types/chat'

defineProps<{ conversations: ConversationListItem[] }>()
</script>

<template>
  <Head title="Chat" />
  <div class="p-6 max-w-4xl mx-auto">
    <h1 class="text-xl font-semibold mb-4">Chatlar</h1>
    <button @click="router.post('/chat/start', { psychologist_id: 2 })">
      Chat boshlash
    </button>

    <div class="space-y-2">
      <Link
        v-for="c in conversations"
        :key="c.id"
        :href="'/chat/' + c.id"
        class="block rounded border p-3 hover:bg-gray-50"
      >
        <div class="flex items-center justify-between">
          <div class="font-medium">
            {{ c.title ?? 'Murojaat' }}
          </div>
          <div class="text-xs text-gray-500">
            {{ c.last_message?.created_at ? new Date(c.last_message.created_at).toLocaleString() : '' }}
          </div>
        </div>

        <div class="text-sm text-gray-600 line-clamp-1">
          {{ c.last_message?.body ?? 'Hozircha xabar yo‘q' }}
        </div>
      </Link>
    </div>
  </div>
</template>
