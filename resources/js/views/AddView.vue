<script setup>
import { onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useQuickAdd } from '../composables/useQuickAdd';

const router = useRouter();
const { openQuickAdd } = useQuickAdd();

onMounted(() => {
    router.replace({ name: 'home' });
    openQuickAdd();
});
</script>

<template>
  <p class="text-center text-sm text-slate-400">正在打开记账…</p>
</template>