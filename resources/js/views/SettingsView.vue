<template>
  <div>
    <h1 class="text-xl font-bold text-slate-900 dark:text-slate-100">我的</h1>

    <div v-if="loading" class="mt-8 text-center text-slate-400">加载中…</div>

    <section v-if="!loading" class="app-card mt-4 rounded-2xl border p-4 shadow-sm">
      <h2 class="font-semibold text-slate-800 dark:text-slate-100">外观</h2>
      <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">默认黑夜模式，可切换白天或跟随系统</p>
      <div class="mt-3 flex rounded-xl bg-slate-100 p-1 dark:bg-slate-800">
        <button
          v-for="opt in themeOptions"
          :key="opt.value"
          type="button"
          class="flex-1 rounded-lg py-2 text-sm font-medium"
          :class="
            theme === opt.value
              ? 'bg-white text-teal-700 shadow dark:bg-slate-700 dark:text-teal-300'
              : 'text-slate-600 dark:text-slate-400'
          "
          @click="setThemeMode(opt.value)"
        >
          {{ opt.label }}
        </button>
      </div>
    </section>

    <section v-if="!loading && habits" class="app-card mt-4 rounded-2xl border p-4 shadow-sm">
      <h2 class="font-semibold text-slate-800 dark:text-slate-100">记账里程碑</h2>
      <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">按历史最长连续记账天数解锁，断签后徽章仍保留</p>
      <StreakMilestoneBadges class="!mt-4" :milestones="habits.milestones" />
    </section>

    <RouterLink
      v-if="!loading"
      to="/tools"
      class="app-card mt-4 flex items-center justify-between rounded-2xl border p-4 shadow-sm"
    >
      <div>
        <p class="font-semibold text-slate-900 dark:text-slate-100">财务工具</p>
        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">存款 / 贷款计算器</p>
      </div>
      <span class="text-sm text-teal-600 dark:text-teal-400">进入 ›</span>
    </RouterLink>

    <section v-if="!loading" class="app-card mt-4 rounded-2xl border p-4 shadow-sm">
      <h2 class="font-semibold text-slate-800 dark:text-slate-100">固定账单日</h2>
      <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
        自定义每月入账/扣款提醒，如工资、奖金、花呗、借呗、信用卡等
      </p>
      <div class="mt-3 flex flex-wrap gap-2">
        <button
          v-for="preset in schedulePresets"
          :key="preset.label"
          type="button"
          class="rounded-full border border-slate-200 px-2.5 py-1 text-xs text-slate-600 dark:border-slate-600 dark:text-slate-300"
          @click="addSchedulePreset(preset)"
        >
          + {{ preset.label }}
        </button>
      </div>
      <ul v-if="scheduleItems.length" class="mt-3 space-y-3">
        <li
          v-for="(item, index) in scheduleItems"
          :key="item._key"
          class="rounded-xl border border-slate-200 p-3 dark:border-slate-600"
        >
          <div class="grid grid-cols-2 gap-2">
            <label class="col-span-2 text-xs text-slate-500 dark:text-slate-400">
              名称
              <input v-model="item.label" type="text" maxlength="64" class="app-field mt-1 w-full py-2 text-sm" />
            </label>
            <label class="text-xs text-slate-500 dark:text-slate-400">
              类型
              <select v-model="item.kind" class="app-field mt-1 w-full py-2 text-sm">
                <option value="income">收入入账</option>
                <option value="expense">支出扣款</option>
              </select>
            </label>
            <label class="text-xs text-slate-500 dark:text-slate-400">
              每月几号
              <select v-model.number="item.day" class="app-field mt-1 w-full py-2 text-sm">
                <option v-for="d in dayOptions" :key="'d-' + index + '-' + d" :value="d">{{ d }} 号</option>
              </select>
            </label>
          </div>
          <button
            type="button"
            class="mt-2 text-xs text-rose-600 dark:text-rose-400"
            @click="removeScheduleItem(index)"
          >
            删除
          </button>
        </li>
      </ul>
      <p v-else class="mt-3 text-sm text-slate-400 dark:text-slate-500">暂无项目，可点上方快捷添加或下方按钮。</p>
      <button
        type="button"
        class="mt-3 w-full rounded-xl border border-dashed border-slate-300 py-2.5 text-sm text-slate-600 dark:border-slate-600 dark:text-slate-300"
        @click="addEmptyScheduleItem"
      >
        + 添加一项
      </button>
      <p class="mt-2 text-[11px] text-slate-400 dark:text-slate-500">若当月没有该日期（如 31 号），按当月最后一天计算。</p>
      <p v-if="scheduleError" class="mt-2 text-sm text-rose-600 dark:text-rose-400">{{ scheduleError }}</p>
      <p v-if="scheduleOk" class="mt-2 text-sm text-emerald-600 dark:text-emerald-400">{{ scheduleOk }}</p>
      <button
        type="button"
        class="mt-3 w-full rounded-xl border border-teal-200 bg-teal-50 py-3 font-medium text-teal-800 disabled:opacity-60 dark:border-teal-800 dark:bg-teal-950/40 dark:text-teal-200"
        :disabled="scheduleSaving"
        @click="saveSchedules"
      >
        {{ scheduleSaving ? '保存中…' : '保存账单日' }}
      </button>
    </section>

    <section v-if="!loading" class="app-card mt-4 rounded-2xl border p-4 shadow-sm">
      <h2 class="font-semibold text-slate-800 dark:text-slate-100">本月预算</h2>
      <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">在统计页查看预算使用进度</p>
      <input
        v-model="budgetInput"
        type="number"
        min="0"
        step="0.01"
        class="app-field mt-3 w-full"
        placeholder="月度支出预算（元）"
      />
      <p v-if="budgetError" class="mt-2 text-sm text-rose-600 dark:text-rose-400">{{ budgetError }}</p>
      <p v-if="budgetOk" class="mt-2 text-sm text-emerald-600 dark:text-emerald-400">{{ budgetOk }}</p>
      <button
        type="button"
        class="mt-3 w-full rounded-xl bg-teal-600 py-3 font-medium text-white disabled:opacity-60"
        :disabled="budgetSaving"
        @click="saveBudget"
      >
        {{ budgetSaving ? '保存中…' : '保存预算' }}
      </button>
    </section>

    <div v-if="user" class="app-card mt-4 rounded-2xl border p-4 shadow-sm">
      <p class="font-semibold text-slate-900 dark:text-slate-100">{{ user.name }}</p>
      <p class="text-sm text-slate-500 dark:text-slate-400">{{ user.email }}</p>
      <p v-if="user.birthday" class="mt-1 text-sm text-slate-600 dark:text-slate-300">生日：{{ user.birthday }}</p>
      <p v-if="user.default_book" class="mt-2 text-sm text-slate-600 dark:text-slate-300">
        账本：{{ user.default_book.name }}（{{ user.default_book.currency }}）
      </p>
    </div>

    <section v-if="user" class="app-card mt-4 rounded-2xl border p-4 shadow-sm">
      <h2 class="font-semibold text-slate-800 dark:text-slate-100">昵称</h2>
      <input
        v-model="nickname"
        type="text"
        maxlength="255"
        class="app-field mt-3 w-full"
        placeholder="显示名称"
      />
      <p v-if="nameError" class="mt-2 text-sm text-rose-600 dark:text-rose-400">{{ nameError }}</p>
      <p v-if="nameOk" class="mt-2 text-sm text-emerald-600 dark:text-emerald-400">{{ nameOk }}</p>
      <button
        type="button"
        class="mt-3 w-full rounded-xl bg-teal-600 py-3 font-medium text-white disabled:opacity-60"
        :disabled="nameSaving"
        @click="saveNickname"
      >
        {{ nameSaving ? '保存中…' : '保存昵称' }}
      </button>
    </section>

    <section v-if="user" class="app-card mt-4 rounded-2xl border p-4 shadow-sm">
      <h2 class="font-semibold text-slate-800 dark:text-slate-100">头像</h2>
      <div class="mt-3 flex flex-wrap items-center gap-3">
        <img
          v-if="previewUrl || user.avatar_url"
          :src="previewUrl || user.avatar_url"
          alt=""
          class="h-20 w-20 rounded-full object-cover ring-2 ring-slate-100 dark:ring-slate-600"
        />
        <div
          v-else
          class="flex h-20 w-20 items-center justify-center rounded-full bg-teal-100 text-2xl font-bold text-teal-700 dark:bg-teal-950/60 dark:text-teal-300"
        >
          {{ user.name?.charAt(0) || '?' }}
        </div>
        <div class="flex flex-col gap-2">
          <label class="cursor-pointer rounded-xl bg-teal-600 px-4 py-2 text-center text-sm font-medium text-white">
            {{ avatarSaving ? '上传中…' : '选择图片' }}
            <input
              ref="avatarInput"
              type="file"
              accept="image/jpeg,image/png,image/gif,image/webp"
              class="hidden"
              :disabled="avatarSaving"
              @change="onAvatarPicked"
            />
          </label>
          <button
            v-if="user.avatar_url || previewUrl"
            type="button"
            class="text-sm text-slate-500 dark:text-slate-400"
            :disabled="avatarSaving"
            @click="removeAvatar"
          >
            移除头像
          </button>
        </div>
      </div>
      <p class="mt-2 text-xs text-slate-400 dark:text-slate-500">支持 JPG/PNG/GIF/WebP，≤2MB</p>
      <p v-if="avatarError" class="mt-2 text-sm text-rose-600 dark:text-rose-400">{{ avatarError }}</p>
      <p v-if="avatarOk" class="mt-2 text-sm text-emerald-600 dark:text-emerald-400">{{ avatarOk }}</p>
    </section>

    <section v-if="user" class="app-card mt-4 rounded-2xl border p-4 shadow-sm">
      <h2 class="font-semibold text-slate-800 dark:text-slate-100">生日</h2>
      <input
        v-model="birthday"
        type="date"
        :max="maxBirthday"
        class="app-field mt-3 w-full"
      />
      <p v-if="profileError" class="mt-2 text-sm text-rose-600 dark:text-rose-400">{{ profileError }}</p>
      <p v-if="profileOk" class="mt-2 text-sm text-emerald-600 dark:text-emerald-400">{{ profileOk }}</p>
      <button
        type="button"
        class="mt-3 w-full rounded-xl bg-teal-600 py-3 font-medium text-white disabled:opacity-60"
        :disabled="profileSaving"
        @click="saveBirthday"
      >
        {{ profileSaving ? '保存中…' : '保存生日' }}
      </button>
    </section>

    <section v-if="user" class="app-card mt-4 rounded-2xl border p-4 shadow-sm">
      <h2 class="font-semibold text-slate-800 dark:text-slate-100">修改密码</h2>
      <input
        v-model="pwd.current"
        type="password"
        autocomplete="current-password"
        class="app-field mt-3 w-full"
        placeholder="当前密码"
      />
      <input
        v-model="pwd.new"
        type="password"
        autocomplete="new-password"
        class="app-field mt-2 w-full"
        placeholder="新密码"
      />
      <input
        v-model="pwd.confirm"
        type="password"
        autocomplete="new-password"
        class="app-field mt-2 w-full"
        placeholder="确认新密码"
      />
      <p v-if="pwdError" class="mt-2 text-sm text-rose-600 dark:text-rose-400">{{ pwdError }}</p>
      <p v-if="pwdOk" class="mt-2 text-sm text-emerald-600 dark:text-emerald-400">{{ pwdOk }}</p>
      <button
        type="button"
        class="mt-3 w-full rounded-xl border border-slate-300 py-3 font-medium text-slate-700 disabled:opacity-60 dark:border-slate-600 dark:text-slate-200"
        :disabled="pwdSaving"
        @click="savePassword"
      >
        {{ pwdSaving ? '保存中…' : '更新密码' }}
      </button>
    </section>

    <button
      class="mt-8 w-full rounded-xl border border-slate-300 py-3 font-medium text-slate-700 dark:border-slate-600 dark:text-slate-200"
      @click="signOut"
    >
      退出登录
    </button>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { RouterLink, useRouter } from 'vue-router';
import {
    fetchMe,
    getBookkeepingHabits,
    getBookSchedules,
    getBudget,
    logout,
    updateAvatar,
    updatePassword,
    updateProfile,
    updateBookSchedules,
    updateBudget,
} from '../api/http';
import StreakMilestoneBadges from '../components/StreakMilestoneBadges.vue';
import { getStoredTheme, useTheme, THEME_OPTIONS } from '../composables/useTheme';

const router = useRouter();
const { setTheme } = useTheme();
const themeOptions = THEME_OPTIONS;
const theme = ref(getStoredTheme());
const user = ref(null);
const loading = ref(true);
const nickname = ref('');
const birthday = ref('');
const previewUrl = ref('');
const avatarInput = ref(null);

const nameSaving = ref(false);
const nameError = ref('');
const nameOk = ref('');
const profileSaving = ref(false);
const profileError = ref('');
const profileOk = ref('');
const avatarSaving = ref(false);
const avatarError = ref('');
const avatarOk = ref('');
const pwdSaving = ref(false);
const pwdError = ref('');
const pwdOk = ref('');
const budgetInput = ref('');
const budgetSaving = ref(false);
const budgetError = ref('');
const budgetOk = ref('');
const habits = ref(null);
const dayOptions = Array.from({ length: 31 }, (_, i) => i + 1);
const scheduleItems = ref([]);
const schedulePresets = [
    { label: '工资', kind: 'income', day: 10 },
    { label: '奖金', kind: 'income', day: 15 },
    { label: '花呗', kind: 'expense', day: 9 },
    { label: '借呗', kind: 'expense', day: 12 },
    { label: '信用卡', kind: 'expense', day: 5 },
    { label: '房贷', kind: 'expense', day: 20 },
];
let scheduleKeySeq = 0;
const scheduleSaving = ref(false);
const scheduleError = ref('');
const scheduleOk = ref('');

const pwd = reactive({
    current: '',
    new: '',
    confirm: '',
});

const maxBirthday = computed(() => {
    const d = new Date();
    d.setDate(d.getDate() - 1);
    return d.toISOString().slice(0, 10);
});

function applyUser(data) {
    user.value = data;
    nickname.value = data.name ?? '';
    birthday.value = data.birthday ?? '';
}

onMounted(async () => {
    try {
        applyUser(await fetchMe());
        const now = new Date();
        const [bud, habitsData, schedResult] = await Promise.allSettled([
            getBudget(now.getFullYear(), now.getMonth() + 1),
            getBookkeepingHabits(),
            getBookSchedules(),
        ]);
        if (bud.status === 'fulfilled') {
            budgetInput.value = bud.value.amount != null ? String(bud.value.amount) : '';
        }
        if (habitsData.status === 'fulfilled') {
            habits.value = habitsData.value;
        }
        if (schedResult.status === 'fulfilled') {
            scheduleItems.value = (schedResult.value.items || []).map((row) => ({
                _key: `s-${++scheduleKeySeq}`,
                label: row.label,
                kind: row.kind,
                day: row.day,
            }));
        } else {
            scheduleError.value =
                schedResult.reason?.response?.data?.message ||
                '无法加载账单日设置，请执行 php artisan migrate 后重试';
        }
    } finally {
        loading.value = false;
    }
});

function addEmptyScheduleItem() {
    scheduleItems.value.push({
        _key: `s-${++scheduleKeySeq}`,
        label: '',
        kind: 'expense',
        day: 1,
    });
}

function addSchedulePreset(preset) {
    if (scheduleItems.value.length >= 20) {
        scheduleError.value = '最多 20 条账单日';
        return;
    }
    scheduleItems.value.push({
        _key: `s-${++scheduleKeySeq}`,
        label: preset.label,
        kind: preset.kind,
        day: preset.day,
    });
}

function removeScheduleItem(index) {
    scheduleItems.value = scheduleItems.value.filter((_, i) => i !== index);
}

async function saveSchedules() {
    scheduleSaving.value = true;
    scheduleError.value = '';
    scheduleOk.value = '';
    const items = scheduleItems.value
        .map(({ label, kind, day }) => ({ label: label.trim(), kind, day: Number(day) }))
        .filter((row) => row.label);
    try {
        const data = await updateBookSchedules({ items });
        scheduleItems.value = (data.items || []).map((row) => ({
            _key: `s-${++scheduleKeySeq}`,
            label: row.label,
            kind: row.kind,
            day: row.day,
        }));
        scheduleOk.value = '账单日已保存';
    } catch (e) {
        scheduleError.value = e.response?.data?.message || '保存失败';
    } finally {
        scheduleSaving.value = false;
    }
}

async function saveBudget() {
    budgetSaving.value = true;
    budgetError.value = '';
    budgetOk.value = '';
    const now = new Date();
    try {
        const amount = budgetInput.value.trim() === '' ? null : budgetInput.value;
        await updateBudget({
            year: now.getFullYear(),
            month: now.getMonth() + 1,
            amount,
        });
        budgetOk.value = '预算已保存';
    } catch (e) {
        budgetError.value = e.response?.data?.message || '保存失败';
    } finally {
        budgetSaving.value = false;
    }
}

function onAvatarPicked(event) {
    const file = event.target.files?.[0];
    if (!file) {
        return;
    }
    if (file.size > 2 * 1024 * 1024) {
        avatarError.value = '图片不能超过 2MB';
        return;
    }
    avatarError.value = '';
    avatarOk.value = '';
    if (previewUrl.value) {
        URL.revokeObjectURL(previewUrl.value);
    }
    previewUrl.value = URL.createObjectURL(file);
    uploadAvatar(file);
    if (avatarInput.value) {
        avatarInput.value.value = '';
    }
}

async function uploadAvatar(file) {
    avatarSaving.value = true;
    avatarError.value = '';
    avatarOk.value = '';
    try {
        const data = await updateAvatar(file);
        applyUser(data);
        avatarOk.value = '头像已更新';
        if (previewUrl.value) {
            URL.revokeObjectURL(previewUrl.value);
            previewUrl.value = '';
        }
    } catch (e) {
        avatarError.value = e.response?.data?.message || '上传失败';
    } finally {
        avatarSaving.value = false;
    }
}

async function removeAvatar() {
    avatarSaving.value = true;
    avatarError.value = '';
    avatarOk.value = '';
    try {
        const data = await updateProfile({ remove_avatar: true });
        applyUser(data);
        avatarOk.value = '已移除头像';
        if (previewUrl.value) {
            URL.revokeObjectURL(previewUrl.value);
            previewUrl.value = '';
        }
    } catch (e) {
        avatarError.value = e.response?.data?.message || '操作失败';
    } finally {
        avatarSaving.value = false;
    }
}

async function saveNickname() {
    const name = nickname.value.trim();
    if (!name) {
        nameError.value = '昵称不能为空';
        return;
    }
    nameSaving.value = true;
    nameError.value = '';
    nameOk.value = '';
    try {
        const data = await updateProfile({ name });
        applyUser(data);
        nameOk.value = '昵称已保存';
    } catch (e) {
        nameError.value =
            e.response?.data?.message ||
            e.response?.data?.errors?.name?.[0] ||
            '保存失败';
    } finally {
        nameSaving.value = false;
    }
}

async function saveBirthday() {
    profileSaving.value = true;
    profileError.value = '';
    profileOk.value = '';
    try {
        const data = await updateProfile({
            birthday: birthday.value || null,
        });
        applyUser(data);
        profileOk.value = '生日已保存';
    } catch (e) {
        profileError.value =
            e.response?.data?.message ||
            e.response?.data?.errors?.birthday?.[0] ||
            '保存失败';
    } finally {
        profileSaving.value = false;
    }
}

async function savePassword() {
    if (!pwd.current || !pwd.new) {
        pwdError.value = '请填写当前密码和新密码';
        return;
    }
    if (pwd.new !== pwd.confirm) {
        pwdError.value = '两次新密码不一致';
        return;
    }
    pwdSaving.value = true;
    pwdError.value = '';
    pwdOk.value = '';
    try {
        await updatePassword({
            current_password: pwd.current,
            password: pwd.new,
            password_confirmation: pwd.confirm,
        });
        pwdOk.value = '密码已更新';
        pwd.current = '';
        pwd.new = '';
        pwd.confirm = '';
    } catch (e) {
        pwdError.value = e.response?.data?.message || '更新失败';
    } finally {
        pwdSaving.value = false;
    }
}

function setThemeMode(mode) {
    theme.value = mode;
    setTheme(mode);
}

async function signOut() {
    await logout();
    router.push('/login');
}
</script>