<template>
  <h3 class="rounded-xl border border-slate-200 bg-white p-3 shadow-sm dark:border-slate-600 dark:bg-slate-800">
    <p class="text-[11px] text-slate-500 dark:text-slate-400">{{ label }}</p>
    <p
      class="mt-1 truncate text-sm font-bold"
      :class="
        tone === 'income'
          ? 'text-emerald-600 dark:text-emerald-400'
          : 'text-slate-800 dark:text-slate-100'
      "
    >
      <span v-if="loading" class="text-slate-300 dark:text-slate-500">…</span>
      <span v-else>{{ formatMoney(value) }}</span>
    </p>
  </h3>
</template>

<script setup>
import { formatMoney } from '../utils/money';

defineProps({
    label: String,
    value: { type: Number, default: 0 },
    loading: Boolean,
    tone: { type: String, default: 'default' },
});
</script>