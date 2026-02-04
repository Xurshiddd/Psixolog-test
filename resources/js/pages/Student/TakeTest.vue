<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3'
import AppStudentLayout from '@/layouts/AppStudentLayout.vue'
import { dashboard, student_test_index } from '@/routes'
import type { BreadcrumbItem } from '@/types'
import { ref, computed, watch } from 'vue'

type TestType = 'single' | 'multi' | 'text'

type TestOption = {
  id: number
  option_text: string
  option_value?: number
}

type Test = {
  id: number
  question: string
  image?: string | null
  type: TestType
  options?: TestOption[]
}

type Module = {
  id: number
  name: string
  tests: Test[]
}

type ResultItem = {
  type: TestType
  consequence_fake: string
}

const props = defineProps<{ module: Module }>()
const page = usePage()

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Student Dashboard', href: dashboard().url },
  { title: 'Tests', href: student_test_index().url },
  { title: props.module.name, href: '#' },
]

// -------------------- State --------------------
const currentQuestionIndex = ref(0)

// answers format:
// single: { [testId]: number }  -> option_id
// multi : { [testId]: number[] }-> option_id[]
// text  : { [testId]: string }  -> answer text
const answers = ref<Record<number, number | number[] | string | null>>({})

const submitting = ref(false)
const showResults = ref(false)

// -------------------- Computed --------------------
const tests = computed(() => props.module.tests || [])
const totalQuestions = computed(() => tests.value.length)

const currentTest = computed<Test | null>(() => {
  if (!tests.value.length) return null
  return tests.value[currentQuestionIndex.value] || null
})

const answeredCount = computed(() => {
  let count = 0
  for (const t of tests.value) {
    const a = answers.value[t.id]
    if (t.type === 'single') {
      if (typeof a === 'number') count++
    } else if (t.type === 'multi') {
      if (Array.isArray(a) && a.length > 0) count++
    } else if (t.type === 'text') {
      if (typeof a === 'string' && a.trim().length > 0) count++
    }
  }
  return count
})

const progress = computed(() => {
  if (!totalQuestions.value) return 0
  return (answeredCount.value / totalQuestions.value) * 100
})

// Inertia flash results (Laravel back()->with('results', ...))
const results = computed<Record<number, ResultItem> | null>(() => {
  // @ts-ignore
  return (page.props.flash?.results ?? page.props.results ?? null) as any
})

watch(results, (val) => {
  if (val) showResults.value = true
})

// -------------------- Helpers --------------------
const isAnswered = (test: Test) => {
  const a = answers.value[test.id]
  if (test.type === 'single') return typeof a === 'number'
  if (test.type === 'multi') return Array.isArray(a) && a.length > 0
  if (test.type === 'text') return typeof a === 'string' && a.trim().length > 0
  return false
}

const isCurrentAnswered = computed(() => (currentTest.value ? isAnswered(currentTest.value) : false))

const initIfMissing = (test: Test) => {
  if (test.id in answers.value) return
  if (test.type === 'single') answers.value[test.id] = null
  if (test.type === 'multi') answers.value[test.id] = []
  if (test.type === 'text') answers.value[test.id] = ''
}

// -------------------- Actions --------------------
const goToQuestion = (index: number) => {
  if (index < 0 || index >= totalQuestions.value) return
  currentQuestionIndex.value = index
  const t = tests.value[index]
  if (t) initIfMissing(t)
}
const echoType = (type: string) => {
  if (type === 'single') return 'Faqat 1 ta tanlov'
  if (type === 'multi') return 'Bir nechta tanlov qilish mumkin'
  if (type === 'text') return 'Faqat matn yozish mumkin'
}
const nextQuestion = () => {
  if (currentQuestionIndex.value < totalQuestions.value - 1) {
    goToQuestion(currentQuestionIndex.value + 1)
  }
}

const previousQuestion = () => {
  if (currentQuestionIndex.value > 0) {
    goToQuestion(currentQuestionIndex.value - 1)
  }
}

const selectSingle = (testId: number, optionId: number) => {
  answers.value[testId] = optionId
}

const toggleMulti = (testId: number, optionId: number) => {
  const curr = answers.value[testId]
  const arr = Array.isArray(curr) ? [...curr] : []
  const idx = arr.indexOf(optionId)
  if (idx >= 0) arr.splice(idx, 1)
  else arr.push(optionId)
  answers.value[testId] = arr
}

const setText = (testId: number, value: string) => {
  answers.value[testId] = value
}

const submitTest = () => {
  submitting.value = true


  // faqat module tests ichidagi id larni yuboramiz (keraksiz keylar ketmasin)
  const payload: Record<number, any> = {}
  for (const t of tests.value) {
    payload[t.id] = answers.value[t.id] ?? (t.type === 'multi' ? [] : null)
  }

  router.post(
    '/student/test/solve',
    { 
      module_id: props.module.id,
      answers: payload 
    },
    {
      preserveScroll: true,
      onFinish: () => {
        submitting.value = false
      },
    }
  )
}
const canSubmit = computed(() => {
  if (totalQuestions.value === 0) return false
  return answeredCount.value === totalQuestions.value
})
// init first test
if (tests.value[0]) initIfMissing(tests.value[0])
</script>

<template>
  <Head :title="`Test: ${module.name}`" />

  <AppStudentLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
      <!-- Header -->
      <div class="rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 shadow-sm p-6">
        <div class="flex items-start justify-between gap-4">
          <div>
            <h2 class="text-2xl font-bold text-slate-900 dark:text-slate-100">
              {{ module.name }}
            </h2>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
              Savol {{ totalQuestions ? (currentQuestionIndex + 1) : 0 }} / {{ totalQuestions }}
            </p>
          </div>

          <div class="text-right">
            <p class="text-sm text-slate-500 dark:text-slate-400">Javob berilgan</p>
            <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">
              {{ answeredCount }} / {{ totalQuestions }}
            </p>
          </div>
        </div>

        <!-- Progress -->
        <div class="mt-4">
          <div class="w-full bg-slate-200 dark:bg-slate-700 rounded-full h-2">
            <div
              class="bg-blue-600 dark:bg-blue-500 h-2 rounded-full transition-all duration-300"
              :style="{ width: `${progress}%` }"
            />
          </div>

          <div class="mt-4 flex items-center justify-between gap-3">
            <div class="text-xs text-slate-500 dark:text-slate-400">
              {{ isCurrentAnswered ? "Bu savolga javob berilgan" : "Hali javob berilmagan" }}
            </div>

            <button
              @click="submitTest"
              :disabled="submitting || !canSubmit"
              class="inline-flex items-center gap-2 px-4 py-2 rounded-lg font-medium transition-all"
              :class="(submitting || !canSubmit)
                ? 'bg-emerald-600/70 text-white cursor-not-allowed'
                : 'bg-emerald-600 text-white hover:bg-emerald-700'"
            >
              <svg v-if="!submitting" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>

              <svg v-else class="w-5 h-5 animate-spin" viewBox="0 0 24 24" fill="none">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
              </svg>

              {{ submitting ? 'Yuborilmoqda...' : 'Testni yakunlash' }}
            </button>
          </div>
        </div>
      </div>

      <!-- Results -->
      <div
        v-if="showResults && results"
        class="rounded-2xl border border-emerald-200 dark:border-emerald-800 bg-emerald-50 dark:bg-emerald-900/20 shadow-sm p-6"
      >
        <div class="flex items-start justify-between gap-4">
          <div>
            <h3 class="text-xl font-semibold text-emerald-900 dark:text-emerald-200">
              Natijalar (consequence_fake)
            </h3>
            <p class="text-sm text-emerald-800/70 dark:text-emerald-200/70 mt-1">
              consequence_real ni keyin psixolog beradi.
            </p>
          </div>

          <button
            class="px-3 py-2 rounded-lg bg-white/70 dark:bg-slate-800 border border-emerald-200 dark:border-emerald-800 text-sm text-slate-700 dark:text-slate-200 hover:bg-white dark:hover:bg-slate-700 transition"
            @click="showResults = false"
          >
            Yopish
          </button>
        </div>

        <div class="mt-4 space-y-3">
          <div
            v-for="(t, idx) in tests"
            :key="t.id"
            class="rounded-xl border border-emerald-200 dark:border-emerald-800 bg-white dark:bg-slate-900/30 p-4"
          >
            <div class="flex items-start justify-between gap-3">
              <div class="text-sm font-medium text-slate-900 dark:text-slate-100">
                {{ idx + 1 }}. {{ t.question }}
              </div>
              <button
                class="text-xs px-2 py-1 rounded-md border border-emerald-200 dark:border-emerald-800 text-emerald-700 dark:text-emerald-300 hover:bg-emerald-100/60 dark:hover:bg-emerald-900/30 transition"
                @click="goToQuestion(idx)"
              >
                Ko‘rish
              </button>
            </div>

            <div class="mt-2 text-sm text-slate-700 dark:text-slate-300">
              {{ results[t.id]?.consequence_fake ?? "Natija yo‘q" }}
            </div>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-4 gap-4">
        <!-- Sidebar -->
        <div class="lg:col-span-1">
          <div class="rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 shadow-sm p-4">
            <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100 mb-4">Savollar</h3>

            <div class="grid grid-cols-5 lg:grid-cols-4 gap-2">
              <button
                v-for="(t, index) in tests"
                :key="t.id"
                @click="goToQuestion(index)"
                class="aspect-square rounded-lg text-sm font-medium transition-all"
                :class="{
                  'bg-blue-600 text-white': currentQuestionIndex === index,
                  'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400':
                    currentQuestionIndex !== index && isAnswered(t),
                  'bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300':
                    currentQuestionIndex !== index && !isAnswered(t),
                }"
              >
                {{ index + 1 }}
              </button>
            </div>

            <div class="mt-4 text-xs text-slate-500 dark:text-slate-400 space-y-1">
              <div class="flex items-center gap-2">
                <span class="inline-block w-3 h-3 rounded bg-blue-600" /> <span>Hozirgi savol</span>
              </div>
              <div class="flex items-center gap-2">
                <span class="inline-block w-3 h-3 rounded bg-emerald-200 dark:bg-emerald-900/30" /> <span>Javob berilgan</span>
              </div>
              <div class="flex items-center gap-2">
                <span class="inline-block w-3 h-3 rounded bg-slate-200 dark:bg-slate-700" /> <span>Javobsiz</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Main -->
        <div class="lg:col-span-3">
          <!-- Has test -->
          <div
            v-if="currentTest"
            class="rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 shadow-sm p-6"
          >
            <!-- Question -->
            <div class="mb-6">
              <div class="flex items-start gap-3">
                <span
                  class="flex-shrink-0 flex h-8 w-8 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 font-semibold"
                >
                  {{ currentQuestionIndex + 1 }}
                </span>

                <div class="flex-1">
                  <h3 class="text-xl font-semibold text-slate-900 dark:text-slate-100">
                    {{ currentTest.question }}
                  </h3>

                  <div v-if="currentTest.image" class="mt-4">
                    <img
                      :src="`/storage/${currentTest.image}`"
                      :alt="currentTest.question"
                      class="rounded-lg max-w-full h-auto border border-slate-200 dark:border-slate-700"
                    />
                  </div>

                  <div class="mt-2">
                    <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300">
                      Tanlov: {{ echoType(currentTest.type) }}
                    </span>
                  </div>
                </div>
              </div>
            </div>

            <!-- SINGLE -->
            <div v-if="currentTest.type === 'single'" class="space-y-3">
              <button
                v-for="opt in (currentTest.options || [])"
                :key="opt.id"
                @click="selectSingle(currentTest.id, opt.id)"
                class="w-full text-left p-4 rounded-xl border-2 transition-all"
                :class="{
                  'border-blue-600 bg-blue-50 dark:bg-blue-900/20': answers[currentTest.id] === opt.id,
                  'border-slate-200 dark:border-slate-700 hover:border-blue-300 dark:hover:border-blue-700': answers[currentTest.id] !== opt.id,
                }"
              >
                <div class="flex items-center gap-3">
                  <div
                    class="flex-shrink-0 w-6 h-6 rounded-full border-2 flex items-center justify-center"
                    :class="{
                      'border-blue-600 bg-blue-600': answers[currentTest.id] === opt.id,
                      'border-slate-300 dark:border-slate-600': answers[currentTest.id] !== opt.id,
                    }"
                  >
                    <svg
                      v-if="answers[currentTest.id] === opt.id"
                      class="w-4 h-4 text-white"
                      fill="currentColor"
                      viewBox="0 0 20 20"
                    >
                      <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                  </div>

                  <span class="text-slate-900 dark:text-slate-100">
                    {{ opt.option_text }}
                  </span>
                </div>
              </button>
            </div>

            <!-- MULTI -->
            <div v-else-if="currentTest.type === 'multi'" class="space-y-3">
              <button
                v-for="opt in (currentTest.options || [])"
                :key="opt.id"
                @click="toggleMulti(currentTest.id, opt.id)"
                class="w-full text-left p-4 rounded-xl border-2 transition-all"
                :class="{
                  'border-blue-600 bg-blue-50 dark:bg-blue-900/20': Array.isArray(answers[currentTest.id]) && (answers[currentTest.id] as number[]).includes(opt.id),
                  'border-slate-200 dark:border-slate-700 hover:border-blue-300 dark:hover:border-blue-700': !(Array.isArray(answers[currentTest.id]) && (answers[currentTest.id] as number[]).includes(opt.id)),
                }"
              >
                <div class="flex items-center gap-3">
                  <div
                    class="flex-shrink-0 w-6 h-6 rounded-md border-2 flex items-center justify-center"
                    :class="{
                      'border-blue-600 bg-blue-600': Array.isArray(answers[currentTest.id]) && (answers[currentTest.id] as number[]).includes(opt.id),
                      'border-slate-300 dark:border-slate-600': !(Array.isArray(answers[currentTest.id]) && (answers[currentTest.id] as number[]).includes(opt.id)),
                    }"
                  >
                    <svg
                      v-if="Array.isArray(answers[currentTest.id]) && (answers[currentTest.id] as number[]).includes(opt.id)"
                      class="w-4 h-4 text-white"
                      fill="currentColor"
                      viewBox="0 0 20 20"
                    >
                      <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                  </div>

                  <span class="text-slate-900 dark:text-slate-100">
                    {{ opt.option_text }}
                  </span>
                </div>
              </button>

              <div class="text-xs text-slate-500 dark:text-slate-400">
                Tanlanganlar:
                <span class="font-medium text-slate-700 dark:text-slate-200">
                  {{ Array.isArray(answers[currentTest.id]) ? (answers[currentTest.id] as number[]).length : 0 }}
                </span>
              </div>
            </div>

            <!-- TEXT -->
            <div v-else-if="currentTest.type === 'text'" class="space-y-3">
              <label class="text-sm font-medium text-slate-700 dark:text-slate-300">
                Javobingiz
              </label>

              <textarea
                :value="typeof answers[currentTest.id] === 'string' ? (answers[currentTest.id] as string) : ''"
                @input="setText(currentTest.id, ($event.target as HTMLTextAreaElement).value)"
                rows="5"
                class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900/40 p-4 text-slate-900 dark:text-slate-100 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500"
                placeholder="Javobni yozing..."
              />

              <div class="text-xs text-slate-500 dark:text-slate-400">
                {{ (typeof answers[currentTest.id] === 'string' ? (answers[currentTest.id] as string).trim().length : 0) }} ta belgi
              </div>
            </div>

            <!-- Unknown type fallback -->
            <div v-else class="rounded-xl border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/20 p-4 text-sm text-amber-900 dark:text-amber-200">
              Bu test turi qo‘llab-quvvatlanmaydi: <b>{{ currentTest.type }}</b>
            </div>

            <!-- Navigation -->
            <div class="flex items-center justify-between mt-8 pt-6 border-t border-slate-200 dark:border-slate-700">
              <button
                @click="previousQuestion"
                :disabled="currentQuestionIndex === 0"
                class="inline-flex items-center gap-2 px-4 py-2 rounded-lg font-medium transition-all"
                :class="{
                  'bg-slate-100 dark:bg-slate-700 text-slate-400 dark:text-slate-500 cursor-not-allowed': currentQuestionIndex === 0,
                  'bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-600': currentQuestionIndex > 0,
                }"
              >
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                </svg>
                Oldingi
              </button>

              <button
                v-if="currentQuestionIndex < totalQuestions - 1"
                @click="nextQuestion"
                class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-blue-600 text-white font-medium hover:bg-blue-700 transition-all"
              >
                Keyingi
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                </svg>
              </button>

              <button
                v-else
                @click="submitTest"
                :disabled="submitting || !canSubmit"
                class="inline-flex items-center gap-2 px-6 py-2 rounded-lg font-medium transition-all"
                :class="submitting || !canSubmit
                  ? 'bg-emerald-600/70 text-white cursor-not-allowed'
                  : 'bg-emerald-600 text-white hover:bg-emerald-700'"
              >
                <svg v-if="!submitting" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0 9 9 0 0118 0z" />
                </svg>

                <svg v-else class="w-5 h-5 animate-spin" viewBox="0 0 24 24" fill="none">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
                </svg>

                {{ submitting ? 'Yuborilmoqda...' : 'Testni yakunlash' }}
              </button>
            </div>
          </div>

          <!-- No tests -->
          <div
            v-else
            class="rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 shadow-sm p-12 text-center"
          >
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-16 h-16 mx-auto text-slate-400 dark:text-slate-600 mb-4">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9 5.25h.008v.008H12v-.008z" />
            </svg>
            <h3 class="text-xl font-semibold text-slate-900 dark:text-slate-100 mb-2">
              Testlar topilmadi
            </h3>
            <p class="text-slate-500 dark:text-slate-400">
              Ushbu modul uchun hozircha faol testlar mavjud emas.
            </p>
          </div>
        </div>
      </div>
    </div>
  </AppStudentLayout>
</template>