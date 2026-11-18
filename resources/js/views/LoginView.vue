<template>
  <div class="mx-auto flex min-h-dvh max-w-lg flex-col justify-center px-6 py-10">
    <h1 class="text-2xl font-bold text-teal-800 dark:text-teal-400">记账本</h1>
    <p class="mt-1 text-sm text-slate-500">登录后查看收支与统计</p>

    <form class="mt-8 space-y-4" @submit.prevent="submit">
      <div>
        <label class="text-sm font-medium text-slate-700">邮箱</label>
        <input
          v-model="form.email"
          type="email"
          required
          class="mt-1 w-full rounded-xl border border-slate-200 px-4 py-3 text-base outline-none focus:border-teal-500"
          placeholder="you@example.com"
        />
      </div>
      <div>
        <label class="text-sm font-medium text-slate-700">密码</label>
        <input
          v-model="form.password"
          type="password"
          required
          class="mt-1 w-full rounded-xl border border-slate-200 px-4 py-3 text-base outline-none focus:border-teal-500"
        />
      </div>
      <p v-if="error" class="text-sm text-rose-600">{{ error }}</p>
      <p v-if="verifiedBanner" class="rounded-lg bg-teal-50 px-3 py-2 text-sm text-teal-800">
        邮箱已验证，请登录。
      </p>
      <button
        type="submit"
        class="w-full rounded-xl bg-teal-600 py-3 text-base font-semibold text-white active:scale-[0.99]"
        :disabled="loading"
      >
        {{ loading ? '登录中…' : '登录' }}
      </button>
    </form>

    <p class="mt-6 text-center text-sm text-slate-600">
      没有账号？
      <RouterLink to="/register" class="font-medium text-teal-700">注册</RouterLink>
    </p>
    <p class="mt-2 text-center text-sm">
      <RouterLink to="/verify-email" class="text-teal-700">重发验证邮件</RouterLink>
    </p>
  </div>
</template>

<script setup>
import { reactive, ref, onMounted } from 'vue';
import { useRoute, useRouter, RouterLink } from 'vue-router';
import { login } from '../api/http';

const route = useRoute();
const router = useRouter();
const form = reactive({ email: '', password: '' });
const loading = ref(false);
const error = ref('');
const verifiedBanner = ref(false);

onMounted(() => {
    verifiedBanner.value = route.query.verified === '1';
});

async function submit() {
    loading.value = true;
    error.value = '';
    try {
        await login(form);
        router.replace(route.query.redirect || '/');
    } catch (e) {
        const data = e.response?.data;
        if (data?.code === 'EMAIL_NOT_VERIFIED') {
            error.value = '请先完成邮箱验证。';
        } else if (data?.code === 'ACCOUNT_BANNED') {
            error.value = '账号已停用。';
        } else {
            error.value = data?.message || '登录失败';
        }
    } finally {
        loading.value = false;
    }
}
</script>