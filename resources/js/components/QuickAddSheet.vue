<template>
  <Teleport to="body">
    <div v-if="sheetOpen" class="fixed inset-0 z-[55]">
      <button
        type="button"
        class="absolute inset-0 bg-black/45"
        aria-label="关闭"
        @click="tryClose"
      />
      <div
        class="absolute inset-x-0 bottom-0 mx-auto flex max-h-[92dvh] max-w-lg flex-col rounded-t-2xl border border-slate-200 bg-white shadow-2xl dark:border-slate-700 dark:bg-slate-900"
        style="padding-bottom: env(safe-area-inset-bottom)"
      >
        <div class="flex shrink-0 items-center justify-between border-b border-slate-100 px-4 py-3 dark:border-slate-800">
          <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">记一笔</h2>
          <button
            type="button"
            class="rounded-lg px-2 py-1 text-sm text-slate-500 dark:text-slate-400"
            @click="tryClose"
          >
            关闭
          </button>
        </div>
        <div class="min-h-0 flex-1 overflow-y-auto px-4 pb-28 pt-2">
          <TransactionForm
            :key="formKey"
            mode="quick"
            :saving="saving"
            :error="error"
            submit-label="完成"
            @submit="save"
          />
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, watch } from 'vue';
import { createTransaction, getBookkeepingHabits } from '../api/http';
import { useQuickAdd } from '../composables/useQuickAdd';
import { popNewlyEarnedMilestones } from '../utils/streakMilestones';
import TransactionForm from './TransactionForm.vue';

const { sheetOpen, closeQuickAdd, notifyTransactionSaved } = useQuickAdd();

const saving = ref(false);
const error = ref('');
const formKey = ref(0);

watch(sheetOpen, (isOpen) => {
    if (isOpen) {
        error.value = '';
        formKey.value += 1;
    }
});

function tryClose() {
    if (saving.value) {
        return;
    }
    closeQuickAdd();
}

async function save(payload) {
    if (!payload) {
        error.value = '请填写金额并选择分类';
        return;
    }
    if (payload.error) {
        error.value = payload.error;
        return;
    }
    saving.value = true;
    error.value = '';
    const { files, remove_attachment_paths: _r, ...data } = payload;
    try {
        const result = await createTransaction(data, files);
        let msg = result.flash_message || '已记录';
        try {
            const habits = await getBookkeepingHabits();
            const unlocked = popNewlyEarnedMilestones(habits.milestones);
            if (unlocked.length) {
                const names = unlocked.map((m) => m.label).join('、');
                msg = `🏅 解锁「${names}」· ${msg}`;
            } else if (habits.streak_days > 0) {
                msg += ` · 连续 ${habits.streak_days} 天`;
            }
        } catch {
            // ignore habits enrichment
        }
        notifyTransactionSaved(msg);
        closeQuickAdd();
    } catch (e) {
        error.value = e.response?.data?.message || '保存失败';
    } finally {
        saving.value = false;
    }
}
</script>