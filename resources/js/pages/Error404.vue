<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
</script>

<template>
  <Head title="Sahifa topilmadi" />

  <div class="min-h-screen flex items-center justify-center bg-gray-50 dark:bg-gray-900">
    <div class="text-center">
      <h1 class="text-6xl font-bold text-red-500">404</h1>

      <p class="mt-4 text-xl text-gray-700 dark:text-gray-300">
        Kechirasiz, bunday sahifa topilmadi
      </p>

      <div class="mt-6">
        <Link
          href="/"
          class="px-6 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition"
        >
          Bosh sahifaga qaytish
        </Link>
      </div>
    </div>
  </div>
</template>
