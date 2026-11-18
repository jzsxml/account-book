<template>
  <div class="mt-4 border-t border-slate-200 pt-4 dark:border-slate-600">
    <p class="mb-2 text-sm font-medium text-slate-700 dark:text-slate-200">{{ title }}（共 {{ rows.length }} 期）</p>
    <div class="max-h-72 overflow-auto rounded-xl border border-slate-200 dark:border-slate-600">
      <table class="w-full min-w-[28rem] text-left text-xs">
        <thead class="sticky top-0 bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300">
          <tr>
            <th v-for="col in columns" :key="col.key" class="px-2 py-2 font-medium whitespace-nowrap">
              {{ col.label }}
            </th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="row in rows"
            :key="row.period"
            class="border-t border-slate-100 odd:bg-white even:bg-slate-50 dark:border-slate-700 dark:odd:bg-slate-900 dark:even:bg-slate-800/80"
          >
            <td v-for="col in columns" :key="col.key" class="px-2 py-1.5 whitespace-nowrap tabular-nums text-slate-700 dark:text-slate-200">
              {{ col.format ? col.format(row[col.key]) : row[col.key] }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
defineProps({
    title: { type: String, default: '分期明细' },
    columns: { type: Array, required: true },
    rows: { type: Array, default: () => [] },
});
</script>