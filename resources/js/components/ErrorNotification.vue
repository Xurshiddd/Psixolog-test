<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { usePage } from '@inertiajs/vue3'

const page = usePage()
const isVisible = ref(false)
const message = ref('')

const errors = computed(() => {
    const pageErrors = page.props.errors as Record<string, any>
    if (typeof pageErrors === 'object' && pageErrors !== null) {
        return Object.values(pageErrors).flat()
    }
    return []
})

onMounted(() => {
    if (errors.value.length > 0) {
        message.value = errors.value[0]
        isVisible.value = true
        setTimeout(() => {
            isVisible.value = false
        }, 5000)
    }
})
</script>

<template>
    <transition
        enter-active-class="transition ease-out duration-300"
        enter-from-class="opacity-0 translate-y-2"
        enter-to-class="opacity-100 translate-y-0"
        leave-active-class="transition ease-in duration-200"
        leave-from-class="opacity-100 translate-y-0"
        leave-to-class="opacity-0 translate-y-2"
    >
        <div
            v-if="isVisible"
            class="fixed top-6 left-1/2 -translate-x-1/2 z-50 max-w-sm"
        >
            <div class="rounded-lg bg-red-50 border border-red-200 shadow-lg p-4 flex items-start gap-3">
                <div class="flex-shrink-0 mt-0.5">
                    <svg
                        class="h-5 w-5 text-red-500"
                        xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 20 20"
                        fill="currentColor"
                    >
                        <path
                            fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                            clip-rule="evenodd"
                        />
                    </svg>
                </div>
                <div class="flex-1">
                    <p class="text-sm font-medium text-red-800">
                        {{ message }}
                    </p>
                </div>
            </div>
        </div>
    </transition>
</template>
