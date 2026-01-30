<script setup lang="ts">
import { ref, onMounted } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { router } from '@inertiajs/vue3'

const props = defineProps<{ module: any }>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Testlar',
        href: '/test/index',
    },
    {
        title: 'Testni Tahrirlash',
        href: '#',
    },
];

type QuestionType = 'single' | 'multi' | 'text';

interface Question {
    id?: number;
    type: QuestionType;
    text: string;
    required: boolean;
    reverseScoring: boolean;
  options: Array<{ id?: number; label: string; value: number }>;
  imageFile?: File | null;
  imagePreview?: string | null;
  existingImage?: string | null;
}
const shuffle = ref<boolean>(!!props.module.shuffle)
const moduleName = ref<string>(props.module.name);
const moduleDescription = ref<string>(props.module.description || '');
const questions = ref<Question[]>([]);
let questionIdCounter = 1; // Used for new questions only (temp id logic)
let optionIdCounter = 1;

// Initialize state from props
onMounted(() => {
    if (props.module.tests) {
        questions.value = props.module.tests.map((t: any) => ({
            id: t.id,
            type: t.type,
            text: t.question,
            required: true, // Assuming default as true since not in DB
            reverseScoring: false,
            options: t.options ? t.options.map((o: any) => ({
                id: o.id,
                label: o.option_text,
                value: o.option_value
            })) : [],
            imageFile: null,
            imagePreview: t.image ? '/storage/' + t.image : null,
            existingImage: t.image
        }));
    }
});

const addQuestion = (type: QuestionType) => {
    const newQuestion: Question = {
        // No ID for new question
        type,
        text: '',
        required: true,
        reverseScoring: false,
        options: type !== 'text' ? [
            { label: '', value: 1 },
            { label: '', value: 1 },
        ] : [],
        imageFile: null,
        imagePreview: null,
    };
    questions.value.push(newQuestion);
};

const deleteQuestion = (index: number) => {
  const q = questions.value[index];
  if (q && q.imagePreview && !q.existingImage) {
    try { URL.revokeObjectURL(q.imagePreview); } catch (e) { /* noop */ }
  }
  questions.value.splice(index, 1); 
};

const onImageSelected = (index: number, e: Event) => {
  const input = e.target as HTMLInputElement;
  const file = input.files && input.files[0] ? input.files[0] : null;
  const question = questions.value[index];
  if (!question) return;
  
  if (question.imagePreview && !question.existingImage) {
    try { URL.revokeObjectURL(question.imagePreview); } catch (err) { /* noop */ }
  }
  
  if (file) {
    question.imageFile = file;
    question.imagePreview = URL.createObjectURL(file);
  } else {
    // keeping existing if cancel? No, file input clear means clear.
    // If user clears input, does it mean remove image?
      }
  if (input) input.value = '';
};

const removeImage = (index: number) => {
  const question = questions.value[index];
  if (!question) return;
  if (question.imagePreview && !question.existingImage) {
    try { URL.revokeObjectURL(question.imagePreview); } catch (err) { /* noop */ }
  }
  question.imageFile = null;
  question.imagePreview = null;
  question.existingImage = null; 
};

const addOption = (index: number) => {
    const question = questions.value[index];
    if (question) {
        question.options.push({ label: '', value: 1 });
    }
};

const deleteOption = (qIndex: number, oIndex: number) => {
    const question = questions.value[qIndex];
    if (question) {
        question.options.splice(oIndex, 1);
    }
};

const getQuestionTypeLabel = (type: QuestionType) => {
    const labels: Record<QuestionType, string> = {
        single: 'Single',
        multi: 'Multi',
        text: 'Text',
    };
    return labels[type];
};

const updateTest = async () => {
    const questionsData = questions.value.map(q => ({
        id: q.id, 
        question: q.text,
        question_image: q.imageFile, 
        type: q.type,
        options: q.options.map(o => ({
            id: o.id, 
            option_text: o.label,
            option_value: o.value,
        })),
    }));

    const answerJson = {
        id: props.module.id,
        module: moduleName.value,
        module_description: moduleDescription.value,
        shuffle: shuffle.value ? 1 : 0,
        questions: questionsData
    };
    
    router.put(`/test/update/${props.module.id}`, answerJson, {
      onSuccess: () => {
        alert('Test muvaffaqiyatli yangilandi!')
      },
      onError: (errors) => {
        console.log(errors)
        alert('Validatsiya xatosi bor')
      },
  })
};
</script>
<template>
    <AppLayout :breadcrumbs="breadcrumbs">
  <div class="min-h-screen bg-slate-50 dark:bg-slate-900">
    <div class="mx-auto max-w-5xl px-4 py-10 sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="mb-8">
        <h1 class="text-3xl font-semibold tracking-tight text-slate-900 dark:text-slate-100">
          Testni Tahrirlash
        </h1>
        <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">
            {{ moduleName }} testini o'zgartirish
        </p>
      </div>

      <!-- Test Information -->
      <div class="rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 shadow-sm">
        <div class="flex items-center gap-3 border-b border-slate-100 dark:border-slate-700 px-6 py-5">
          <div
            class="flex h-9 w-9 items-center justify-center rounded-xl bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-200"
            aria-hidden="true"
          >
            <!-- gear icon -->
            <svg viewBox="0 0 24 24" fill="none" class="h-5 w-5" stroke="currentColor" stroke-width="1.8">
              <path stroke-linecap="round" stroke-linejoin="round"
                d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.607 2.296.07 2.572-1.065z" />
              <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
          </div>

          <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Test malumotlari</h2>
        </div>

        <div class="px-6 py-6">
          <div class="grid grid-cols-1 gap-6">
            <!-- Title -->
            <div>
              <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                Module nomi <span class="text-rose-600">*</span>
              </label>
              <input
                v-model="moduleName"
                type="text"
                placeholder="Strees darajasini baholash"
                class="mt-2 block w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-4 py-3 text-sm text-slate-900 dark:text-slate-100 placeholder:text-slate-400 dark:placeholder:text-slate-500 shadow-sm outline-none ring-0 transition focus:border-blue-500 dark:focus:border-blue-400 focus:ring-4 focus:ring-blue-500/10"
              />
            </div>

            <!-- Description -->
            <div>
              <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Tavsif</label>
              <textarea
                v-model="moduleDescription"
                rows="4"
                placeholder="Test haqida qisqacha ma'lumot"
                class="mt-2 block w-full resize-none rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-4 py-3 text-sm text-slate-900 dark:text-slate-100 placeholder:text-slate-400 dark:placeholder:text-slate-500 shadow-sm outline-none transition focus:border-blue-500 dark:focus:border-blue-400 focus:ring-4 focus:ring-blue-500/10"
              ></textarea>
            </div>

            <!-- Time + Shuffle -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-[1fr_auto] sm:items-end">

              <label class="flex items-center gap-3 sm:justify-end">
                <input
                  type="checkbox"
                  v-model="shuffle"
                  class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500/20"
                />
                <span class="text-sm text-slate-700 dark:text-slate-300">Aralashtirish</span>
              </label>
            </div>
          </div>
        </div>
      </div>

      <!-- Questions -->
      <div class="mt-6 rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 shadow-sm">
        <div class="flex flex-col gap-4 px-6 py-5 sm:flex-row sm:items-center sm:justify-between border-b border-slate-100 dark:border-slate-700">
          <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Savollar</h2>

          <div class="flex flex-wrap gap-2">
            <button
              @click="addQuestion('single')"
              type="button"
              class="rounded-xl bg-blue-600 px-3.5 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-600/20"
            >
              + Single
            </button>
            <button
              @click="addQuestion('multi')"
              type="button"
              class="rounded-xl bg-blue-600 px-3.5 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-600/20"
            >
              + Multi
            </button>
            <button
              @click="addQuestion('text')"
              type="button"
              class="rounded-xl bg-blue-600 px-3.5 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-600/20"
            >
              + Text
            </button>
          </div>
        </div>

        <!-- Questions List -->
        <div class="divide-y divide-slate-100">
          <div v-if="questions.length === 0" class="px-6 py-12 text-center">
            <p class="text-slate-500 dark:text-slate-400">Hali savollar qo'shilmagan. Yuqoridagi tugmalar orqali savollarni qo'shing</p>
          </div>

          <div v-for="(question, index) in questions" :key="index" class="px-6 py-6">
            <div class="flex items-start justify-between gap-4 mb-4">
              <div class="flex-1">
                <div class="flex items-center gap-2 mb-2">
                  <span class="text-sm font-medium text-slate-600 dark:text-slate-300">Savol {{ index + 1 }} ({{ getQuestionTypeLabel(question.type) }})</span>
                </div>
              </div>
              <div class="flex items-center gap-2">
                <button
                  @click="deleteQuestion(index)"
                  type="button"
                  class="rounded p-1 text-red-600 hover:bg-red-50 transition"
                >
                  <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                  </svg>
                </button>
              </div>
            </div>

            <!-- Question Text -->
            <div class="mb-4">
              <label class="block text-sm font-medium text-slate-700 mb-2 dark:text-slate-300">Savol matni <span class="text-rose-600">*</span></label>
              <input
                v-model="question.text"
                type="text"
                placeholder="Savol matnini kiriting"
                class="block w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-4 py-3 text-sm text-slate-900 dark:text-slate-100 placeholder:text-slate-400 dark:placeholder:text-slate-500 shadow-sm outline-none transition focus:border-blue-500 dark:focus:border-blue-400 focus:ring-4 focus:ring-blue-500/10"
              />
            </div>

            <!-- Image upload (optional) -->
            <div class="mb-4">
              <label class="block text-sm font-medium text-slate-700 mb-2 dark:text-slate-300">Rasm (ixtiyoriy)</label>
              <div class="flex items-center gap-3">
                <input
                  @change="onImageSelected(index, $event)"
                  type="file"
                  accept="image/*"
                  class="text-sm text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 shadow-sm outline-none transition focus:border-blue-500 dark:focus:border-blue-400 focus:ring-4 focus:ring-blue-500/10"
                />
                <button
                  v-if="question.imagePreview"
                  @click.prevent="removeImage(index)"
                  type="button"
                  class="text-sm text-red-600 hover:underline transition border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 bg-transparent"
                >
                  Rasmni olib tashlash
                </button>
              </div>
              <div v-if="question.imagePreview" class="mt-3">
                <img :src="question.imagePreview" alt="preview" class="max-w-xs rounded-lg border border-slate-200 dark:border-slate-700" />
              </div>
            </div>

            <!-- Options (for single and multi) -->
            <div v-if="question.type !== 'text'" class="mt-6">
              <div class="mb-4 flex items-center justify-between">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Variantlar</label>
                <button
                  @click="addOption(index)"
                  type="button"
                  class="text-sm font-medium text-blue-600 hover:text-blue-700 transition"
                >
                  + Variant
                </button>
              </div>

              <div class="space-y-3">
                <div v-for="(option, oIndex) in question.options" :key="oIndex" class="flex items-end gap-3">
                  <div class="flex-1">
                    <input
                      v-model="option.label"
                      type="text"
                      placeholder="Variant"
                      class="block w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-4 py-2.5 text-sm text-slate-900 dark:text-slate-100 placeholder:text-slate-400 dark:placeholder:text-slate-500 shadow-sm outline-none transition focus:border-blue-500 dark:focus:border-blue-400 focus:ring-4 focus:ring-blue-500/10"
                    />
                  </div>
                  <div class="w-24">
                    <input
                      v-model.number="option.value"
                      type="number"
                      min="1"
                      placeholder="1"
                      class="block w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-4 py-2.5 text-sm text-slate-900 dark:text-slate-100 placeholder:text-slate-400 dark:placeholder:text-slate-500 shadow-sm outline-none transition focus:border-blue-500 dark:focus:border-blue-400 focus:ring-4 focus:ring-blue-500/10"
                    />
                  </div>
                  <button
                    @click="deleteOption(index, oIndex)"
                    type="button"
                    class="rounded p-2 text-red-600 hover:bg-red-50 transition"
                  >
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Bottom actions -->
      <div class="mt-8 flex justify-end">
        <button
          @click="updateTest"
          type="button"
          class="inline-flex items-center justify-center gap-2 rounded-2xl bg-emerald-600 px-6 py-4 text-base font-semibold text-white shadow-sm transition hover:bg-emerald-700 focus:outline-none focus:ring-4 focus:ring-emerald-600/20"
        >
          <!-- save icon -->
          <svg viewBox="0 0 24 24" fill="none" class="h-5 w-5" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 7a2 2 0 012-2h10l4 4v10a2 2 0 01-2 2H6a2 2 0 01-2-2V7z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M8 5v6h8V5" />
          </svg>
          Saqlash
        </button>
      </div>
    </div>
  </div>
  </AppLayout>
</template>
