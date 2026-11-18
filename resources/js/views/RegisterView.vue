<template>
  <div class="mx-auto flex min-h-dvh max-w-lg flex-col justify-center px-6 py-10">
    <h1 class="text-2xl font-bold text-teal-800">创建账号</h1>
    <form class="mt-8 space-y-4" @submit.prevent="submit">
      <div>
        <label class="text-sm font-medium">昵称</label>
        <input
          v-model="form.name"
          required
          class="mt-1 w-full rounded-xl border border-slate-200 px-4 py-3 text-base outline-none focus:border-teal-500"
        />
      </div>
      <div>
        <label class="text-sm font-medium">邮箱</label>
        <input
          v-model="form.email"
          type="email"
          required
          class="mt-1 w-full rounded-xl border border-slate-200 px-4 py-3 text-base outline-none focus:border-teal-500"
        />
      </div>
      <div>
        <label class="text-sm font-medium">密码</label>
        <input
          v-model="form.password"
          type="password"
          required
          class="mt-1 w-full rounded-xl border border-slate-200 px-4 py-3 text-base outline-none focus:border-teal-500"
        />
      </div>
      <div>
        <label class="text-sm font-medium">确认密码</label>
        <input
          v-model="form.password_confirmation"
          type="password"
          required
          class="mt-1 w-full rounded-xl border border-slate-200 px-4 py-3 text-base outline-none focus:border-teal-500"
        />
      </div>
      <p v-if="error" class="text-sm text-rose-600">{{ error }}</p>
      <button
        type="submit"
        class="w-full rounded-xl bg-teal-600 py-3 text-base font-semibold text-white"
        :disabled="loading"
      >
        注册
      </button>
    </form>
    <p class="mt-6 text-center text-sm">
      <RouterLink to="/login" class="text-teal-700">返回登录</RouterLink>
    </p>
  </div>
</template>

<script setup>
import { reactive, ref } from 'vue';
import { useRouter, RouterLink } from 'vue-router';
import { register } from '../api/http';

const router = useRouter();
const form = reactive({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});
const loading = ref(false);
const error = ref('');

async function submit() {
    loading.value = true;
    error.value = '';
    try {
        await register(form);
        router.push({ name: 'verify-email', query: { email: form.email } });
    } catch (e) {
        error.value = e.response?.data?.message || '注册失败';
    } finally {
        loading.value = false;
    }
}
</script>