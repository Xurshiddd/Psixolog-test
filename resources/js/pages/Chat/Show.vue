<script setup lang="ts">
import { Head, usePage, router } from '@inertiajs/vue3'
import { onBeforeUnmount, onMounted, ref, computed, nextTick } from 'vue'
import type { ChatMessage, ChatUser } from '@/types/chat'

type Props = {
  conversation: { id: number; title: string | null; users: ChatUser[] }
  messages: {
    data: Array<{
      id: number
      body: string
      created_at: string
      sender: ChatUser
      conversation_id: number
    }>
    next_page_url: string | null
    prev_page_url: string | null
  }
}

const props = defineProps<Props>()
const page = usePage()

const authUser = computed(() => page.props.auth.user as { id: number; name: string })

// backend latest paginate qaytargan bo‘lsa reverse qilib “pastga” tushiramiz
const list = ref<ChatMessage[]>(
  [...props.messages.data].reverse().map(m => ({
    id: m.id,
    body: m.body,
    created_at: m.created_at,
    conversation_id: props.conversation.id,
    sender: m.sender,
  }))
)

const body = ref('')
const sending = ref(false)
const containerRef = ref<HTMLElement | null>(null)

function scrollToBottom() {
  nextTick(() => {
    const el = containerRef.value
    if (!el) return
    el.scrollTop = el.scrollHeight
  })
}

async function send() {
  const text = body.value.trim()
  if (!text || sending.value) return

  sending.value = true

  // Optimistic UI
  const tempId = `tmp_${Date.now()}`
  list.value.push({
    id: tempId,
    conversation_id: props.conversation.id,
    body: text,
    created_at: new Date().toISOString(),
    sender: { id: authUser.value.id, name: authUser.value.name },
  })
  body.value = ''
  scrollToBottom()

  try {
    const res = await fetch(route('chat.messages.store', props.conversation.id), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': (page.props.csrf_token as string) ?? '',
      },
      body: JSON.stringify({ body: text }),
    })

    const json = await res.json()
    if (!json?.ok) throw new Error('Send failed')

    // temp message ni real id bilan almashtirish
    const idx = list.value.findIndex(m => m.id === tempId)
    if (idx !== -1) list.value[idx] = json.message as ChatMessage
  } catch (e) {
    // xatoda optimistic xabarni olib tashlash
    list.value = list.value.filter(m => m.id !== tempId)
    alert('Xabar yuborilmadi. Qayta urinib ko‘ring.')
  } finally {
    sending.value = false
  }
}

function isMine(m: ChatMessage) {
  return m.sender.id === authUser.value.id
}

let channel: any = null

onMounted(() => {
  scrollToBottom()

  channel = window.Echo.private(`conversations.${props.conversation.id}`)
    .listen('.message.sent', (payload: any) => {
      // toOthers() bo‘lgani uchun bu yerga faqat qarshi tomondan keladi
      list.value.push(payload as ChatMessage)
      scrollToBottom()
    })
})

onBeforeUnmount(() => {
  if (channel) {
    window.Echo.leave(`private-conversations.${props.conversation.id}`)
  }
})
</script>

<template>
  <Head :title="conversation.title ?? 'Chat'" />
  <div class="max-w-4xl mx-auto p-6 flex flex-col h-[calc(100vh-80px)]">
    <div class="mb-3">
      <h1 class="text-xl font-semibold">{{ conversation.title ?? 'Psixolog bilan chat' }}</h1>
      <p class="text-sm text-gray-500">
        Ishtirokchilar:
        <span v-for="u in conversation.users" :key="u.id" class="mr-2">{{ u.name }}</span>
      </p>
    </div>

    <div ref="containerRef" class="flex-1 overflow-y-auto border rounded p-4 space-y-3 bg-white">
      <div v-for="m in list" :key="m.id" class="flex" :class="isMine(m) ? 'justify-end' : 'justify-start'">
        <div class="max-w-[75%] rounded px-3 py-2"
             :class="isMine(m) ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-900'">
          <div class="text-xs opacity-80 mb-1">{{ m.sender.name }}</div>
          <div class="whitespace-pre-wrap">{{ m.body }}</div>
          <div class="text-[11px] opacity-70 mt-1">
            {{ new Date(m.created_at).toLocaleString() }}
          </div>
        </div>
      </div>
    </div>

    <div class="mt-3 flex gap-2">
      <textarea
        v-model="body"
        rows="2"
        class="flex-1 border rounded p-2"
        placeholder="Xabar yozing..."
        @keydown.enter.exact.prevent="send"
      />
      <button
        class="px-4 rounded bg-blue-600 text-white disabled:opacity-60"
        :disabled="sending || !body.trim()"
        @click="send"
      >
        Yuborish
      </button>
    </div>
  </div>
</template>
