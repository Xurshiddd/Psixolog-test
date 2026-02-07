<script setup lang="ts">
import { computed, ref, watch, onMounted, onBeforeUnmount } from 'vue'
import { Head, router, usePage } from '@inertiajs/vue3'
import AppStudentLayout from '@/layouts/AppStudentLayout.vue'

type Conversation = {
  id: number
  channel: 'admin' | 'psiholog'
  subject: string | null
  status: 'open' | 'closed'
  last_message_at: string | null
  created_at: string
}

type ActiveConversation = {
  id: number
  channel: 'admin' | 'psiholog'
  subject: string | null
  status: 'open' | 'closed'
} | null

type Message = {
  id: number
  sender_role: 'student' | 'staff'
  sender_id: number
  sender_name?: string | null
  body: string
  created_at: string
}

const props = defineProps<{
  conversations: Conversation[]
  activeConversation: ActiveConversation
  messages: Message[]
}>()
const localMessages = ref<Message[]>([...props.messages])
const messagesEl = ref<HTMLElement | null>(null)
const autoScrollEnabled = ref(true)

function isNearBottom(el: HTMLElement, threshold = 120) {
  return el.scrollHeight - el.scrollTop - el.clientHeight < threshold
}

function scrollToBottom(force = false) {
  requestAnimationFrame(() => {
    const el = messagesEl.value
    if (!el) return
    if (!force && !autoScrollEnabled.value) return
    el.scrollTop = el.scrollHeight
  })
}

function onScrollMessages() {
  const el = messagesEl.value
  if (!el) return
  autoScrollEnabled.value = isNearBottom(el)
}

const page = usePage()
const meId = computed(() => (page.props.auth as any)?.user?.id as number)

const messageBody = ref('')
const sending = ref(false)

const activeId = computed(() => props.activeConversation?.id ?? null)

function openConversation(id: number) {
  router.get('/student/requests', { conversation: id }, { preserveScroll: true })
}

function createConversation(channel: 'admin' | 'psiholog') {
  router.post('/student/requests', { channel }, { preserveScroll: true })
}

async function sendMessage() {
  if (!activeId.value) return
  const body = messageBody.value.trim()
  if (!body) return

  // ✅ optimistic message (temp id)
  const tempId = Date.now() * -1
  localMessages.value.push({
    id: tempId,
    sender_role: 'student',
    sender_id: meId.value,
    sender_name: 'Siz',
    body,
    created_at: new Date().toISOString(),
  })

  messageBody.value = ''
  sending.value = true

  router.post(`/student/requests/${activeId.value}/messages`, { body }, {
    preserveScroll: true,
    onFinish: () => {
      sending.value = false
    },
    onSuccess: () => {
      localMessages.value = [...props.messages]
    },
    onError: () => {
      localMessages.value = localMessages.value.filter(m => m.id !== tempId)
    }
  })
}

let channelRef: any = null 
function subscribe(conversationId: number) {
  if (!window.Echo) return

  channelRef = window.Echo.private(`conversations.${conversationId}`)
    .listen('.message.created', (e: any) => {
      if (!localMessages.value.some(m => m.id === e.id)) {
        localMessages.value.push(e)
      }
    })
    .listenForWhisper('typing', (e: any) => {
      typingName.value = e?.name ?? ''
      typingUntil.value = Date.now() + 1500
    })
}

function unsubscribe(conversationId: number) {
  if (!window.Echo) return
  window.Echo.leave(`conversations.${conversationId}`)
  channelRef = null
}


const typingName = ref('')
const typingUntil = ref(0)

watch(
  () => props.messages,
  (v) => {
    localMessages.value = [...v]
    autoScrollEnabled.value = true
    scrollToBottom(true)
  },
  { immediate: true }
)
watch(
  () => localMessages.value.length,
  () => {
    scrollToBottom(false)
  }
)

onBeforeUnmount(() => {
  if (activeId.value) unsubscribe(activeId.value)
})
const canSend = computed(() => !!activeId.value && messageBody.value.trim().length > 0 && !sending.value)
let typingTimer: number | null = null

function whisperTyping() {
  if (!activeId.value || !channelRef) return
  channelRef.whisper('typing', { name: 'Siz yozayapsiz...' })

  if (typingTimer) window.clearTimeout(typingTimer)
  typingTimer = window.setTimeout(() => {
    // typing tugadi
  }, 800)
}

// UX: conversation o‘zgarsa inputni tozalab qo‘yamiz
watch(activeId, (id, oldId) => {
  if (oldId) unsubscribe(oldId)
  if (id) subscribe(id)
  localMessages.value = [...props.messages]
  messageBody.value = ''
  autoScrollEnabled.value = true
  scrollToBottom(true)
})

const showChat = ref(!!activeId.value)

watch(activeId, (id) => {
  if (id) showChat.value = true
})

function goBackToList() {
  showChat.value = false
}

</script>

<template>
  <Head title="Murojaatlar" />
  <AppStudentLayout>
    <div class="h-[calc(100vh-0px)] w-full p-4">
      <div class="grid h-full grid-cols-1 lg:grid-cols-12 gap-4">

        <!-- ===================== -->
        <!-- DESKTOP: SIDEBAR      -->
        <!-- ===================== -->
        <aside class="lg:col-span-3 h-full rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-950 overflow-hidden hidden lg:block">
          <div class="border-b border-gray-200 px-4 py-3 dark:border-gray-800">
            <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">Murojaatlar</div>
          </div>

          <div class="p-3 flex gap-2">
            <button
              class="w-full rounded-xl border px-3 py-2 text-xs font-medium
                     border-gray-200 hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-gray-900"
              @click="createConversation('admin')"
            >
              Admin bilan suhbat
            </button>
            <button
              class="w-full rounded-xl border px-3 py-2 text-xs font-medium
                     border-gray-200 hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-gray-900"
              @click="createConversation('psiholog')"
            >
              Psiholog bilan suhbat
            </button>
          </div>

          <div class="h-[calc(100%-110px)] overflow-y-auto px-2 pb-3">
            <button
              v-for="c in conversations"
              :key="c.id"
              class="mb-2 w-full rounded-xl border px-3 py-3 text-left transition
                     border-gray-200 hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-gray-900"
              :class="activeId === c.id ? 'ring-2 ring-offset-2 ring-gray-300 dark:ring-gray-700 dark:ring-offset-gray-950' : ''"
              @click="openConversation(c.id)"
            >
              <div class="flex items-center justify-between gap-2">
                <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                  {{ c.channel === 'admin' ? 'Admin' : 'Psiholog' }}
                </div>
                <span
                  class="rounded-full px-2 py-0.5 text-[10px] font-semibold"
                  :class="c.status === 'open'
                    ? 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-200'
                    : 'bg-gray-100 text-gray-700 dark:bg-gray-900 dark:text-gray-200'"
                >
                  {{ c.status }}
                </span>
              </div>

              <div class="mt-1 text-xs text-gray-600 dark:text-gray-400 line-clamp-2">
                {{ c.subject ?? 'Mavzu: (yo‘q)' }}
              </div>

              <div class="mt-2 text-[11px] text-gray-500 dark:text-gray-500">
                Oxirgi: {{ c.last_message_at ?? c.created_at }}
              </div>
            </button>

            <div v-if="conversations.length === 0" class="px-3 py-6 text-sm text-gray-500 dark:text-gray-400">
              Hali murojaat yo‘q. Yuqoridan suhbat boshlang.
            </div>
          </div>
        </aside>

        <!-- ===================== -->
        <!-- DESKTOP: CHAT         -->
        <!-- ===================== -->
        <section class="lg:col-span-9 h-full rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-950 overflow-hidden hidden lg:block">
          <!-- Header -->
          <div class="flex items-center justify-between border-b border-gray-200 px-4 py-3 dark:border-gray-800">
            <div class="min-w-0">
              <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                <template v-if="activeConversation">
                  {{ activeConversation.channel === 'admin' ? 'Admin bilan chat' : 'Psiholog bilan chat' }}
                </template>
                <template v-else>
                  Chat tanlang
                </template>
              </div>
              <div class="text-xs text-gray-600 dark:text-gray-400 truncate">
                <template v-if="activeConversation">
                  {{ activeConversation.subject ?? 'Mavzu: (yo‘q)' }}
                </template>
                <template v-else>
                  Chapdan murojaat tanlang yoki yangisini yarating
                </template>
              </div>
            </div>

            <div class="flex gap-2">
              <div class="h-8 w-40 rounded-lg border border-gray-200 dark:border-gray-800"></div>
              <div class="h-8 w-40 rounded-lg border border-gray-200 dark:border-gray-800"></div>
            </div>
          </div>

          <!-- Messages -->
          <div ref="messagesEl" @scroll="onScrollMessages" class="h-[calc(100%-120px)] overflow-y-auto p-4">
            <template v-if="activeConversation">
              <div v-if="localMessages.length === 0" class="text-sm text-gray-500 dark:text-gray-400">
                Hali xabar yo‘q. Birinchi bo‘lib yozing.
              </div>

              <div
                v-for="m in localMessages"
                :key="m.id"
                class="mb-3 flex"
                :class="m.sender_id === meId ? 'justify-end' : 'justify-start'"
              >
                <div
                  class="max-w-[70%] rounded-2xl border px-3 py-2 text-sm leading-relaxed"
                  :class="m.sender_id === meId
                    ? 'border-gray-200 bg-gray-50 text-gray-900 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-100'
                    : 'border-gray-200 bg-white text-gray-900 dark:border-gray-800 dark:bg-gray-950 dark:text-gray-100'"
                >
                  <div class="text-[11px] font-semibold opacity-70 mb-1">
                    {{ m.sender_id === meId ? 'Siz' : (m.sender_name ?? 'Operator') }}
                  </div>
                  <div class="whitespace-pre-wrap">{{ m.body }}</div>
                  <div class="mt-1 text-[10px] opacity-60">{{ m.created_at }}</div>
                </div>
              </div>

              <div v-if="typingName && Date.now() < typingUntil" class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                {{ typingName }} yozayapti...
              </div>
            </template>

            <template v-else>
              <div class="h-full flex items-center justify-center text-gray-500 dark:text-gray-400">
                Chat tanlanmagan
              </div>
            </template>
          </div>

          <!-- Input -->
          <div class="border-t border-gray-200 p-3 dark:border-gray-800">
            <div class="flex items-center gap-3">
              <input
                v-model="messageBody"
                @input="whisperTyping"
                :disabled="!activeId || sending"
                @keydown.enter.prevent="sendMessage"
                class="h-11 flex-1 rounded-xl border border-gray-200 bg-white px-3 text-sm
                       focus:outline-none focus:ring-2 focus:ring-gray-300
                       disabled:opacity-60
                       dark:border-gray-800 dark:bg-gray-950 dark:focus:ring-gray-700"
                placeholder="Xabar yozing..."
              />

              <button
                class="h-11 rounded-xl border px-4 text-sm font-semibold
                       border-gray-200 hover:bg-gray-50 disabled:opacity-60
                       dark:border-gray-800 dark:hover:bg-gray-900"
                :disabled="!canSend"
                @click="sendMessage"
              >
                Send
              </button>
            </div>
          </div>
        </section>

        <!-- ===================== -->
        <!-- MOBILE: LIST (showChat=false) -->
        <!-- ===================== -->
        <aside v-if="!showChat" class="h-full rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-950 overflow-hidden lg:hidden">
          <div class="border-b border-gray-200 px-4 py-3 dark:border-gray-800">
            <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">Murojaatlar</div>
          </div>

          <div class="p-3 flex gap-2">
            <button
              class="w-full rounded-xl border px-3 py-2 text-xs font-medium
                     border-gray-200 hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-gray-900"
              @click="createConversation('admin')"
            >
              Admin bilan suhbat
            </button>
            <button
              class="w-full rounded-xl border px-3 py-2 text-xs font-medium
                     border-gray-200 hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-gray-900"
              @click="createConversation('psiholog')"
            >
              Psiholog bilan suhbat
            </button>
          </div>

          <div class="h-[calc(100%-110px)] overflow-y-auto px-2 pb-3">
            <button
              v-for="c in conversations"
              :key="c.id"
              class="mb-2 w-full rounded-xl border px-3 py-3 text-left transition
                     border-gray-200 hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-gray-900"
              :class="activeId === c.id ? 'ring-2 ring-offset-2 ring-gray-300 dark:ring-gray-700 dark:ring-offset-gray-950' : ''"
              @click="openConversation(c.id)"
            >
              <div class="flex items-center justify-between gap-2">
                <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                  {{ c.channel === 'admin' ? 'Admin' : 'Psiholog' }}
                </div>
                <span
                  class="rounded-full px-2 py-0.5 text-[10px] font-semibold"
                  :class="c.status === 'open'
                    ? 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-200'
                    : 'bg-gray-100 text-gray-700 dark:bg-gray-900 dark:text-gray-200'"
                >
                  {{ c.status }}
                </span>
              </div>

              <div class="mt-1 text-xs text-gray-600 dark:text-gray-400 line-clamp-2">
                {{ c.subject ?? 'Mavzu: (yo‘q)' }}
              </div>

              <div class="mt-2 text-[11px] text-gray-500 dark:text-gray-500">
                Oxirgi: {{ c.last_message_at ?? c.created_at }}
              </div>
            </button>

            <div v-if="conversations.length === 0" class="px-3 py-6 text-sm text-gray-500 dark:text-gray-400">
              Hali murojaat yo‘q. Yuqoridan suhbat boshlang.
            </div>
          </div>
        </aside>

        <!-- ===================== -->
        <!-- MOBILE: CHAT (showChat=true) -->
        <!-- ===================== -->
        <section v-else class="h-full rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-950 overflow-hidden lg:hidden">
          <!-- Header (with Back) -->
          <div class="flex items-center justify-between border-b border-gray-200 px-4 py-3 dark:border-gray-800">
            <div class="flex items-center gap-2 min-w-0">
              <button
                class="inline-flex items-center justify-center rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-semibold
                       hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-gray-900"
                @click="goBackToList"
              >
                Back
              </button>

              <div class="min-w-0">
                <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                  <template v-if="activeConversation">
                    {{ activeConversation.channel === 'admin' ? 'Admin bilan chat' : 'Psiholog bilan chat' }}
                  </template>
                  <template v-else>Chat tanlang</template>
                </div>
                <div class="text-xs text-gray-600 dark:text-gray-400 truncate">
                  <template v-if="activeConversation">
                    {{ activeConversation.subject ?? 'Mavzu: (yo‘q)' }}
                  </template>
                  <template v-else>Chat tanlanmagan</template>
                </div>
              </div>
            </div>
          </div>

          <!-- Messages -->
          <div ref="messagesEl" @scroll="onScrollMessages" class="h-[calc(100%-120px)] overflow-y-auto p-4">
            <template v-if="activeConversation">
              <div v-if="localMessages.length === 0" class="text-sm text-gray-500 dark:text-gray-400">
                Hali xabar yo‘q. Birinchi bo‘lib yozing.
              </div>

              <div
                v-for="m in localMessages"
                :key="m.id"
                class="mb-3 flex"
                :class="m.sender_id === meId ? 'justify-end' : 'justify-start'"
              >
                <div
                  class="max-w-[85%] rounded-2xl border px-3 py-2 text-sm leading-relaxed"
                  :class="m.sender_id === meId
                    ? 'border-gray-200 bg-gray-50 text-gray-900 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-100'
                    : 'border-gray-200 bg-white text-gray-900 dark:border-gray-800 dark:bg-gray-950 dark:text-gray-100'"
                >
                  <div class="text-[11px] font-semibold opacity-70 mb-1">
                    {{ m.sender_id === meId ? 'Siz' : (m.sender_name ?? 'Operator') }}
                  </div>
                  <div class="whitespace-pre-wrap">{{ m.body }}</div>
                  <div class="mt-1 text-[10px] opacity-60">{{ m.created_at }}</div>
                </div>
              </div>

              <div v-if="typingName && Date.now() < typingUntil" class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                {{ typingName }} yozayapti...
              </div>
            </template>

            <template v-else>
              <div class="h-full flex items-center justify-center text-gray-500 dark:text-gray-400">
                Chat tanlanmagan
              </div>
            </template>
          </div>

          <!-- Input -->
          <div class="border-t border-gray-200 p-3 dark:border-gray-800">
            <div class="flex items-center gap-3">
              <input
                v-model="messageBody"
                @input="whisperTyping"
                :disabled="!activeId || sending"
                @keydown.enter.prevent="sendMessage"
                class="h-11 flex-1 rounded-xl border border-gray-200 bg-white px-3 text-sm
                       focus:outline-none focus:ring-2 focus:ring-gray-300
                       disabled:opacity-60
                       dark:border-gray-800 dark:bg-gray-950 dark:focus:ring-gray-700"
                placeholder="Xabar yozing..."
              />

              <button
                class="h-11 rounded-xl border px-4 text-sm font-semibold
                       border-gray-200 hover:bg-gray-50 disabled:opacity-60
                       dark:border-gray-800 dark:hover:bg-gray-900"
                :disabled="!canSend"
                @click="sendMessage"
              >
                Send
              </button>
            </div>
          </div>
        </section>

      </div>
    </div>
  </AppStudentLayout>
</template>

