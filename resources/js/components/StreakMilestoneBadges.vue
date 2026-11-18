<template>
  <ul v-if="milestones?.length" class="mt-3 grid grid-cols-2 gap-2">
    <li
      v-for="m in milestones"
      :key="m.id"
      class="rounded-xl border px-3 py-2.5"
      :class="
        m.earned
          ? 'border-amber-200 bg-amber-50 dark:border-amber-900/60 dark:bg-amber-950/40'
          : 'border-slate-200 bg-slate-50 dark:border-slate-600 dark:bg-slate-800/50'
      "
    >
      <div class="flex items-start gap-2">
        <span class="text-lg leading-none" aria-hidden="true">{{ m.earned ? '🏅' : '🔒' }}</span>
        <div class="min-w-0 flex-1">
          <p
            class="text-xs font-semibold"
            :class="m.earned ? 'text-amber-900 dark:text-amber-200' : 'text-slate-600 dark:text-slate-300'"
          >
            {{ m.label }}
          </p>
          <p class="mt-0.5 text-[10px] text-slate-500 dark:text-slate-400">
            <template v-if="m.earned">已达成连续 {{ m.target }} 天</template>
            <template v-else>再坚持 {{ m.days_to_go }} 天解锁</template>
          </p>
          <div
            v-if="!m.earned"
            class="mt-1.5 h-1 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-700"
          >
            <div
              class="h-full rounded-full bg-teal-500 transition-all"
              :style="{ width: Math.min(100, m.progress_percent) + '%' }"
            />
          </div>
        </div>
      </div>
    </li>
  </ul>
</template>

<script setup>
defineProps({
    milestones: {
        type: Array,
        default: () => [],
    },
});
</script>