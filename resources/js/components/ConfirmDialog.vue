<template>
  <Teleport to="body">
    <div
      v-if="open"
      class="fixed inset-0 z-[60] flex items-center justify-center p-4"
      role="dialog"
      aria-modal="true"
      :aria-labelledby="titleId"
    >
      <button
        type="button"
        class="absolute inset-0 bg-black/45"
        aria-label="关闭"
        @click="cancel"
      />
      <div
        class="relative w-full max-w-sm rounded-2xl border border-slate-200 bg-white p-5 shadow-2xl dark:border-slate-700 dark:bg-slate-900"
      >
        <h3 :id="titleId" class="text-lg font-semibold text-slate-900 dark:text-slate-100">
          {{ payload.title }}
        </h3>
        <p class="mt-2 text-sm leading-relaxed text-slate-600 dark:text-slate-300">
          {{ payload.message }}
        </p>
        <div class="mt-5 grid grid-cols-2 gap-2">
          <button
            type="button"
            class="rounded-xl border border-slate-200 py-2.5 text-sm font-medium text-slate-700 dark:border-slate-600 dark:text-slate-200"
            @click="cancel"
          >
            {{ payload.cancelText }}
          </button>
          <button
            type="button"
            class="rounded-xl bg-rose-600 py-2.5 text-sm font-medium text-white"
            @click="confirm"
          >
            {{ payload.confirmText }}
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { useId } from 'vue';
import { useConfirm } from '../composables/useConfirm';

const titleId = useId();
const { open, payload, confirm, cancel } = useConfirm();
</script>