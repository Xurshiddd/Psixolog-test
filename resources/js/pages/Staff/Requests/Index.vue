<script setup lang="ts">
import { computed, ref, watch, onBeforeUnmount, nextTick } from 'vue'
import { Head, router, usePage } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { BreadcrumbItem } from '@/types'

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Murojaalar', href: '#' }]

type Student = { id: number; name: string; email: string }
type Message = {
  id: number
  sender_role: 'student' | 'staff'
  sender_id: number
  sender_name?: string | null
  body: string
  created_at: string
}

const props = defineProps<{
  channel: 'admin' | 'psiholog'
  q: string
  students: Student[]
  activeStudent: Student | null
  activeConversation: { id: number; status: 'open' | 'closed'; subject: string | null } | null
  messages: Message[]
}>()

const page = usePage()
const meId = computed(() => (page.props.auth as any)?.user?.id as number)

const search = ref(props.q ?? '')
const messageBody = ref('')
const sending = ref(false)
// scroll
const localMessages = ref<Message[]>([...props.messages])
const autoScrollEnabled = ref(true)
const messagesElDesktop = ref<HTMLElement | null>(null)
const messagesElMobile = ref<HTMLElement | null>(null)
const messagesElActive = computed(() => {
  const d = messagesElDesktop.value
  const m = messagesElMobile.value

  if (m && m.offsetParent !== null) return m
  if (d && d.offsetParent !== null) return d

  // fallback
  return m ?? d ?? null
})

function isNearBottom(el: HTMLElement, threshold = 120) {
  return el.scrollHeight - el.scrollTop - el.clientHeight < threshold
}

async function scrollToBottom(force = false) {
  await nextTick()
  await nextTick()
  requestAnimationFrame(() => {
    const el = messagesElActive.value
    if (!el) return
    if (!force && !autoScrollEnabled.value) return
    el.scrollTop = el.scrollHeight
  })
}

function onScrollMessages() {
  const el = messagesElActive.value
  if (!el) return
  autoScrollEnabled.value = isNearBottom(el)
}

// ✅ route() bo'lmasa ham ishlashi uchun URL base
const baseUrl = computed(() => (props.channel === 'admin' ? '/admin/requests' : '/psiholog/requests'))

function openStudent(studentId: number) {
  router.get(
    baseUrl.value,
    { student: studentId, q: search.value || undefined },
    {
      preserveScroll: true,
      preserveState: false, // ✅ MUHIM: props/messages yangilansin
    }
  )
}

function doSearch() {
  router.get(
    baseUrl.value,
    { q: search.value || undefined, student: props.activeStudent?.id },
    { preserveScroll: true, preserveState: false }
  )
}

function sendMessage() {
  if (!props.activeConversation) return
  const body = messageBody.value.trim()
  if (!body) return

  sending.value = true
  router.post(
    `${baseUrl.value}/${props.activeConversation.id}/messages`,
    { body },
    {
      preserveScroll: true,
      onFinish: () => {
        sending.value = false
        messageBody.value = ''
      },
    }
  )
}

const canSend = computed(() => !!props.activeConversation && messageBody.value.trim().length > 0 && !sending.value)
const typingName = computed(() => props.activeStudent?.name ?? 'Talaba')

// ======================
// Realtime (Reverb/Echo)
// ======================
let channelRef: any = null
const typingUntil = ref(0)

function subscribe(conversationId: number) {
  if (!window.Echo) return

  channelRef = window.Echo.private(`conversations.${conversationId}`)
    .listen('.message.created', async (e: any) => {
      if (!localMessages.value.some(m => m.id === e.id)) {
        localMessages.value.push(e)
        await scrollToBottom(false)
      }
    })
    .listenForWhisper('typing', () => {
      typingUntil.value = Date.now() + 1500
    })
}

function unsubscribe(conversationId: number) {
  if (!window.Echo) return
  window.Echo.leave(`conversations.${conversationId}`)
  channelRef = null
}

const activeConversationId = computed(() => props.activeConversation?.id ?? null)

// ✅ props.messages kelganda localMessages sync + scroll
watch(
  () => props.messages,
  async (v) => {
    // merge/sort (sizda bor edi)
    const map = new Map<number, Message>()
    for (const m of localMessages.value) map.set(m.id, m)
    for (const m of v) map.set(m.id, m)
    localMessages.value = Array.from(map.values()).sort((a, b) => a.id - b.id)

    autoScrollEnabled.value = true
    await scrollToBottom(true)
  },
  { immediate: true }
)
watch(() => localMessages.value.length, () => {
  scrollToBottom(false)
})



// ✅ conversation change: unsubscribe/subscribe + mobile showChat + scroll
const showChat = ref(!!activeConversationId.value)
watch(showChat, async (v) => {
  if (v) await scrollToBottom(true)
})

watch(
  activeConversationId,
  async (id, oldId) => {
    if (oldId) unsubscribe(oldId)
    if (id) subscribe(id)

    showChat.value = !!id
    messageBody.value = ''
    autoScrollEnabled.value = true

    await scrollToBottom(true)
  },
  { immediate: true }
)

onBeforeUnmount(() => {
  if (activeConversationId.value) unsubscribe(activeConversationId.value)
})

// typing whisper (staff yozayotganda studentga ko'rsatish uchun)
let typingTimer: number | null = null
function whisperTyping() {
  const id = activeConversationId.value
  if (!id) return
  if (!channelRef || typeof channelRef.whisper !== 'function') return

  channelRef.whisper('typing', { role: props.channel })

  if (typingTimer) window.clearTimeout(typingTimer)
  typingTimer = window.setTimeout(() => {}, 800)
}

function goBackToList() {
  showChat.value = false
}
</script>



<template>
  <Head title="Murojaalar" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="h-[calc(100vh-0px)] w-full p-4">
      <div class="grid h-full grid-cols-1 lg:grid-cols-12 gap-4">

        <!-- ===================== -->
        <!-- DESKTOP: STUDENTS LIST -->
        <!-- ===================== -->
        <aside class="lg:col-span-3 h-full rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-950 overflow-hidden hidden lg:block">
          <div class="border-b border-gray-200 px-4 py-3 dark:border-gray-800">
            <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">
              {{ channel === 'admin' ? 'Admin panel' : 'Psixolog panel' }}
            </div>

            <div class="mt-2 flex gap-2">
              <input
                v-model="search"
                @keydown.enter.prevent="doSearch"
                class="h-9 w-full rounded-xl border border-gray-200 bg-white px-3 text-sm
                       focus:outline-none focus:ring-2 focus:ring-gray-300
                       dark:border-gray-800 dark:bg-gray-950 dark:focus:ring-gray-700"
                placeholder="Talabani qidiring..."
              />
              <button
                class="h-9 rounded-xl border px-3 text-sm font-semibold
                       border-gray-200 hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-gray-900"
                @click="doSearch"
              >
                OK
              </button>
            </div>
          </div>

          <div class="h-[calc(100%-92px)] overflow-y-auto p-2">
            <button
              v-for="s in students"
              :key="s.id"
              class="mb-2 w-full rounded-xl border px-3 py-3 text-left transition
                     border-gray-200 hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-gray-900"
              :class="activeStudent?.id === s.id ? 'ring-2 ring-offset-2 ring-gray-300 dark:ring-gray-700 dark:ring-offset-gray-950' : ''"
              @click="openStudent(s.id)"
            >
              <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ s.name }}</div>
              <div class="mt-1 text-xs text-gray-600 dark:text-gray-400">{{ s.email }}</div>
            </button>

            <div v-if="students.length === 0" class="px-3 py-6 text-sm text-gray-500 dark:text-gray-400">
              Talabalar topilmadi.
            </div>
          </div>
        </aside>

        <!-- ===================== -->
        <!-- DESKTOP: CHAT          -->
        <!-- ===================== -->
        <section class="lg:col-span-9 h-full rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-950 overflow-hidden hidden lg:flex lg:flex-col">
          <!-- Header -->
          <div class="flex items-center justify-between border-b border-gray-200 px-4 py-3 dark:border-gray-800">
            <div class="min-w-0">
              <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                <template v-if="activeStudent">
                  {{ activeStudent.name }} bilan suhbat
                </template>
                <template v-else>
                  Talaba tanlang
                </template>
              </div>
              <div class="text-xs text-gray-600 dark:text-gray-400 truncate">
                <template v-if="activeStudent">{{ activeStudent.email }}</template>
                <template v-else>Chapdan talabani tanlang</template>
              </div>
            </div>

            <div class="flex gap-2">
              <div class="h-8 w-40 rounded-lg border border-gray-200 dark:border-gray-800"></div>
              <div class="h-8 w-40 rounded-lg border border-gray-200 dark:border-gray-800"></div>
            </div>
          </div>

          <!-- Messages -->
          <div ref="messagesElDesktop" @scroll="onScrollMessages" class="flex-1 min-h-0 overflow-y-auto p-4">
            <template v-if="activeStudent">
              <div v-if="localMessages.length === 0" class="text-sm text-gray-500 dark:text-gray-400">
                Hali xabar yo‘q.
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
                    {{ m.sender_id === meId ? 'Siz' : (m.sender_name ?? 'Talaba') }}
                  </div>
                  <div class="whitespace-pre-wrap">{{ m.body }}</div>
                  <div class="mt-1 text-[10px] opacity-60">{{ m.created_at }}</div>
                </div>
              </div>

              <div v-if="Date.now() < typingUntil" class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                {{ typingName }} yozayapti...
              </div>
            </template>

            <template v-else>
              <div class="h-full flex items-center justify-center text-gray-500 dark:text-gray-400">
                Talaba tanlanmagan
              </div>
            </template>
          </div>

          <!-- Input -->
          <div class="border-t border-gray-200 p-3 dark:border-gray-800">
            <div class="flex items-center gap-3">
              <input
                v-model="messageBody"
                @input="whisperTyping"
                :disabled="!activeConversationId || sending"
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
            <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">
              {{ channel === 'admin' ? 'Admin panel' : 'Psixolog panel' }}
            </div>

            <div class="mt-2 flex gap-2">
              <input
                v-model="search"
                @keydown.enter.prevent="doSearch"
                class="h-9 w-full rounded-xl border border-gray-200 bg-white px-3 text-sm
                       focus:outline-none focus:ring-2 focus:ring-gray-300
                       dark:border-gray-800 dark:bg-gray-950 dark:focus:ring-gray-700"
                placeholder="Talabani qidiring..."
              />
              <button
                class="h-9 rounded-xl border px-3 text-sm font-semibold
                       border-gray-200 hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-gray-900"
                @click="doSearch"
              >
                OK
              </button>
            </div>
          </div>

          <div class="h-[calc(100%-92px)] overflow-y-auto p-2">
            <button
              v-for="s in students"
              :key="s.id"
              class="mb-2 w-full rounded-xl border px-3 py-3 text-left transition
                     border-gray-200 hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-gray-900"
              :class="activeStudent?.id === s.id ? 'ring-2 ring-offset-2 ring-gray-300 dark:ring-gray-700 dark:ring-offset-gray-950' : ''"
              @click="openStudent(s.id)"
            >
              <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ s.name }}</div>
              <div class="mt-1 text-xs text-gray-600 dark:text-gray-400">{{ s.email }}</div>
            </button>

            <div v-if="students.length === 0" class="px-3 py-6 text-sm text-gray-500 dark:text-gray-400">
              Talabalar topilmadi.
            </div>
          </div>
        </aside>

        <!-- ===================== -->
        <!-- MOBILE: CHAT (showChat=true) -->
        <!-- ===================== -->
        <section v-else class="h-full rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-950 overflow-hidden lg:hidden flex flex-col">

          <!-- Header with Back -->
          <div class="shrink-0 flex items-center justify-between border-b border-gray-200 px-4 py-3 dark:border-gray-800">
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
                  <template v-if="activeStudent">
                    {{ activeStudent.name }} bilan suhbat
                  </template>
                  <template v-else>
                    Talaba tanlang
                  </template>
                </div>
                <div class="text-xs text-gray-600 dark:text-gray-400 truncate">
                  <template v-if="activeStudent">{{ activeStudent.email }}</template>
                  <template v-else>Talaba tanlanmagan</template>
                </div>
              </div>
            </div>
          </div>

          <!-- Messages -->
          <div ref="messagesElMobile" @scroll="onScrollMessages" class="flex-1 min-h-0 overflow-y-auto p-4">

            <template v-if="activeStudent">
              <div v-if="localMessages.length === 0" class="text-sm text-gray-500 dark:text-gray-400">
                Hali xabar yo‘q.
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
                    {{ m.sender_id === meId ? 'Siz' : (m.sender_name ?? 'Talaba') }}
                  </div>
                  <div class="whitespace-pre-wrap">{{ m.body }}</div>
                  <div class="mt-1 text-[10px] opacity-60">{{ m.created_at }}</div>
                </div>
              </div>

              <div v-if="Date.now() < typingUntil" class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                {{ typingName }} yozayapti...
              </div>
            </template>

            <template v-else>
              <div class="h-full flex items-center justify-center text-gray-500 dark:text-gray-400">
                Talaba tanlanmagan
              </div>
            </template>
          </div>

          <!-- Input -->
          <div class="shrink-0 border-t border-gray-200 p-3 dark:border-gray-800">
            <div class="flex items-center gap-3">
              <input
                v-model="messageBody"
                @input="whisperTyping"
                :disabled="!activeConversationId || sending"
                @keydown.enter.prevent="sendMessage"
                class="shrink-0 h-11 flex-1 rounded-xl border border-gray-200 bg-white px-3 text-sm
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
  </AppLayout>
</template>

