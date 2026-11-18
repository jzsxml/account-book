<template>
  <div class="mx-auto max-w-lg px-6 py-16 text-center">
    <h1 class="text-xl font-bold text-slate-800">验证邮箱</h1>
    <p class="mt-3 text-sm text-slate-600">
      请打开注册邮箱中的验证链接。完成验证后再登录。
    </p>
    <div class="mt-8">
      <input v-model="email" type="email" class="w-full rounded-xl border px-4 py-3" placeholder="注册邮箱" />
      <button class="mt-4 w-full rounded-xl bg-teal-600 py-3 text-white" @click="resend">
        重发验证邮件
      </button>
      <p v-if="message" class="mt-3 text-sm text-teal-700">{{ message }}</p>
    </div>
    <RouterLink to="/login" class="mt-8 inline-block text-teal-700">去登录</RouterLink>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, RouterLink } from 'vue-router';
import { resendVerification } from '../api/http';

const route = useRoute();
const email = ref('');
const message = ref('');

onMounted(() => {
    email.value = route.query.email || '';
});

async function resend() {
    message.value = '';
    const { message: msg } = await resendVerification(email.value);
    message.value = msg;
}
</script>