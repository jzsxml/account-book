<template>
  <div>
    <h1 class="text-xl font-bold">编辑明细</h1>
    <p v-if="loading" class="mt-8 text-center text-slate-400">加载中…</p>
    <TransactionForm
      v-else-if="ready"
      show-date
      submit-label="保存修改"
      :initial="initial"
      :existing-attachments="existingAttachments"
      :saving="saving"
      :error="error"
      @submit="save"
    />
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { getTransaction, updateTransaction } from '../api/http';
import TransactionForm from '../components/TransactionForm.vue';

const route = useRoute();
const router = useRouter();
const loading = ref(true);
const ready = ref(false);
const saving = ref(false);
const error = ref('');
const initial = ref({});
const existingAttachments = ref([]);

function toDatetimeLocal(iso) {
    const d = new Date(iso);
    const pad = (n) => String(n).padStart(2, '0');
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
}

onMounted(async () => {
    try {
        const tx = await getTransaction(route.params.id);
        initial.value = {
            type: tx.type,
            amount: tx.amount,
            category_id: tx.category_id,
            account_id: tx.account_id,
            note: tx.note ?? '',
            occurred_at: toDatetimeLocal(tx.occurred_at),
        };
        existingAttachments.value = tx.metadata?.attachments ?? [];
        ready.value = true;
    } catch {
        error.value = '无法加载该条流水';
    } finally {
        loading.value = false;
    }
});

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
    const { files, remove_attachment_paths, ...data } = payload;
    try {
        await updateTransaction(route.params.id, data, files, remove_attachment_paths);
        router.push('/transactions');
    } catch (e) {
        error.value = e.response?.data?.message || '保存失败';
    } finally {
        saving.value = false;
    }
}
</script>