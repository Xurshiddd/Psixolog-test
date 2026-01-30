<script setup lang="ts">
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

const languages = [
  { code: 'uz', label: "Oʻz" },
  { code: 'ru', label: 'Рус' },
]

const page = usePage()

// Inertia shared prop: props.locale (HandleInertiaRequests.php dan keladi)
const current = computed(() => {
  const loc = (page.props as any).locale as string | undefined
  return languages.some(l => l.code === loc) ? (loc as string) : 'uz'
})

function changeLocale(lang: string) {
  if (!lang) return
  if (lang === current.value) return
  window.location.href = `/locale/${encodeURIComponent(lang)}`
}
</script>

<template>
  <div class="inline-block">
    <label for="site-lang" class="sr-only">Language</label>

    <select
      id="site-lang"
      :value="current"
      @change="changeLocale(($event.target as HTMLSelectElement).value)"
      class="bg-white/80 text-sm rounded-md px-2 py-1 border border-gray-200 dark:bg-gray-800 dark:border-gray-700"
    >
      <option v-for="lang in languages" :key="lang.code" :value="lang.code">
        {{ lang.label }}
      </option>
    </select>
  </div>
</template>

<style scoped>
#site-lang { min-width: 68px; }
</style>
