<template>
  <Teleport to="body">
    <Transition name="app-toast">
      <div
        v-if="toast"
        class="pointer-events-none fixed inset-x-0 top-0 z-[70] flex justify-center px-4 pt-[max(0.75rem,env(safe-area-inset-top))]"
        role="status"
      >
        <p
          class="app-toast-banner pointer-events-auto flex max-w-sm items-center justify-center gap-2 rounded-xl border border-teal-200 bg-teal-50 px-4 py-3 text-center text-sm font-medium text-teal-900 shadow-lg dark:border-teal-800 dark:bg-teal-950/90 dark:text-teal-100"
          :class="{ 'app-toast-banner--celebrate': toastCelebrate }"
        >
          <span
            v-if="toastCelebrate"
            class="app-toast-check inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-teal-600 text-xs text-white"
            aria-hidden="true"
          >
            ✓
          </span>
          <span>{{ toast }}</span>
        </p>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { useQuickAdd } from '../composables/useQuickAdd';

const { toast, toastCelebrate } = useQuickAdd();
</script>