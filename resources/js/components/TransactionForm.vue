<template>
  <div>
    <div class="mt-2 grid grid-cols-2 gap-2">
      <button
        type="button"
        class="rounded-xl py-3 font-semibold transition-colors"
        :class="
          form.type === 'expense'
            ? 'bg-orange-100 text-orange-800 ring-2 ring-orange-400 dark:bg-orange-950/60 dark:text-orange-200 dark:ring-orange-500'
            : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300'
        "
        @click="form.type = 'expense'"
      >
        支出
      </button>
      <button
        type="button"
        class="rounded-xl py-3 font-semibold transition-colors"
        :class="
          form.type === 'income'
            ? 'bg-emerald-100 text-emerald-800 ring-2 ring-emerald-400 dark:bg-emerald-950/60 dark:text-emerald-200 dark:ring-emerald-500'
            : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300'
        "
        @click="form.type = 'income'"
      >
        收入
      </button>
    </div>

    <template v-if="mode === 'quick'">
      <div class="mb-2 mt-4 flex items-center justify-between">
        <p class="text-sm font-medium text-slate-600 dark:text-slate-300">选择分类</p>
        <button
          type="button"
          class="text-sm font-medium text-teal-600 dark:text-teal-400"
          @click="openCategoryDialog"
        >
          + 分类
        </button>
      </div>
      <div class="grid grid-cols-4 gap-2">
        <button
          v-for="cat in categories"
          :key="cat.id"
          type="button"
          class="flex flex-col items-center rounded-xl border p-2 text-xs transition-colors"
          :class="
            form.category_id === cat.id
              ? 'border-teal-500 bg-teal-50 text-teal-900 dark:border-teal-500 dark:bg-teal-950/50 dark:text-teal-100'
              : 'border-slate-200 bg-white text-slate-700 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200'
          "
          @click="selectCategory(cat.id)"
        >
          <CategoryIcon :icon="cat.icon" :name="cat.name" :active="form.category_id === cat.id" />
          <span class="mt-1 line-clamp-1 w-full text-center">{{ cat.name }}</span>
        </button>
      </div>

      <button
        type="button"
        class="mt-4 flex w-full items-center justify-between rounded-xl border border-slate-200 px-3 py-2.5 text-sm text-slate-600 dark:border-slate-600 dark:text-slate-300"
        @click="moreOpen = !moreOpen"
      >
        <span>更多（账户、日期、备注、附件）</span>
        <span class="text-teal-600 dark:text-teal-400">{{ moreOpen ? '收起' : '展开' }}</span>
      </button>

      <div v-show="moreOpen" class="mt-3 space-y-4">
        <div v-if="accounts.length">
          <label class="text-sm font-medium text-slate-600 dark:text-slate-300">账户</label>
          <select v-model="form.account_id" class="app-field mt-1 w-full">
            <option v-for="acc in accounts" :key="acc.id" :value="String(acc.id)">
              {{ acc.name }}
            </option>
          </select>
        </div>
        <div>
          <label class="text-sm font-medium text-slate-600 dark:text-slate-300">日期时间</label>
          <input v-model="form.occurred_at" type="datetime-local" class="app-field mt-1 w-full" />
        </div>
        <textarea
          v-model="form.note"
          rows="2"
          class="app-field w-full p-3"
          placeholder="备注（可选）"
        />
        <div>
          <label class="text-sm font-medium text-slate-600 dark:text-slate-300">附件</label>
          <input
            ref="fileInput"
            type="file"
            class="mt-2 w-full text-sm text-slate-600 dark:text-slate-300"
            accept="image/*,application/pdf"
            multiple
            capture="environment"
            @change="onFilesPicked"
          />
          <ul v-if="existingVisible.length || newFiles.length" class="mt-2 space-y-2">
            <li
              v-for="item in existingVisible"
              :key="item.path"
              class="flex items-center justify-between rounded-lg border border-slate-200 px-3 py-2 text-sm dark:border-slate-600"
            >
              <a :href="item.url" target="_blank" rel="noopener" class="truncate text-teal-700 dark:text-teal-400">
                {{ item.name }}
              </a>
              <button type="button" class="text-rose-500" @click="removeExisting(item.path)">移除</button>
            </li>
            <li
              v-for="(file, idx) in newFiles"
              :key="'new-' + idx"
              class="flex items-center justify-between rounded-lg border border-slate-200 px-3 py-2 text-sm dark:border-slate-600"
            >
              <span class="truncate">{{ file.name }}</span>
              <button type="button" class="text-rose-500" @click="removeNew(idx)">移除</button>
            </li>
          </ul>
        </div>
      </div>

      <p v-if="error" class="mt-3 text-sm text-rose-600 dark:text-rose-400">{{ error }}</p>

      <div
        class="sticky bottom-0 -mx-4 mt-4 border-t border-slate-100 bg-white px-4 pb-1 pt-3 dark:border-slate-800 dark:bg-slate-900"
      >
        <input
          v-model="form.amount"
          inputmode="decimal"
          class="w-full border-b-2 border-teal-200 bg-transparent py-2 text-center text-3xl font-bold text-slate-900 outline-none focus:border-teal-500 dark:border-teal-700 dark:text-slate-100"
          placeholder="0.00"
        />
        <button
          type="button"
          class="mt-3 w-full rounded-2xl bg-teal-600 py-3.5 text-lg font-semibold text-white disabled:opacity-60"
          :disabled="saving"
          @click="handleSubmit"
        >
          {{ saving ? '保存中…' : submitLabel }}
        </button>
      </div>
    </template>

    <template v-else>
      <input
        v-model="form.amount"
        inputmode="decimal"
        class="mt-6 w-full border-b-2 border-teal-200 bg-transparent py-3 text-center text-4xl font-bold text-slate-900 outline-none focus:border-teal-500 dark:border-teal-700 dark:text-slate-100 dark:focus:border-teal-400"
        placeholder="0.00"
      />

      <div v-if="accounts.length" class="mt-6">
        <label class="text-sm font-medium text-slate-600 dark:text-slate-300">账户</label>
        <select v-model="form.account_id" class="app-field mt-1 w-full">
          <option v-for="acc in accounts" :key="acc.id" :value="String(acc.id)">
            {{ acc.name }}
          </option>
        </select>
      </div>

      <div v-if="showDate" class="mt-6">
        <label class="text-sm font-medium text-slate-600 dark:text-slate-300">日期时间</label>
        <input v-model="form.occurred_at" type="datetime-local" class="app-field mt-1 w-full" />
      </div>

      <div class="mt-6 mb-2 flex items-center justify-between">
        <p class="text-sm font-medium text-slate-600 dark:text-slate-300">分类</p>
        <button
          type="button"
          class="text-sm font-medium text-teal-600 dark:text-teal-400"
          @click="openCategoryDialog"
        >
          + 添加分类
        </button>
      </div>
      <div class="grid grid-cols-4 gap-2">
        <button
          v-for="cat in categories"
          :key="cat.id"
          type="button"
          class="flex flex-col items-center rounded-xl border p-2 text-xs transition-colors"
          :class="
            form.category_id === cat.id
              ? 'border-teal-500 bg-teal-50 text-teal-900 dark:border-teal-500 dark:bg-teal-950/50 dark:text-teal-100'
              : 'border-slate-200 bg-white text-slate-700 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200'
          "
          @click="form.category_id = cat.id"
        >
          <CategoryIcon :icon="cat.icon" :name="cat.name" :active="form.category_id === cat.id" />
          <span class="mt-1 line-clamp-1 w-full text-center">{{ cat.name }}</span>
        </button>
      </div>

      <textarea
        v-model="form.note"
        rows="2"
        class="app-field mt-6 w-full p-3"
        placeholder="备注（可选）"
      />

      <div class="mt-6">
        <label class="text-sm font-medium text-slate-600 dark:text-slate-300">附件（图片/PDF，最多 5 个，每个≤10MB）</label>
        <input
          ref="fileInput"
          type="file"
          class="mt-2 w-full text-sm text-slate-600 dark:text-slate-300"
          accept="image/*,application/pdf"
          multiple
          capture="environment"
          @change="onFilesPicked"
        />
        <ul v-if="existingVisible.length || newFiles.length" class="mt-3 space-y-2">
          <li
            v-for="item in existingVisible"
            :key="item.path"
            class="flex items-center justify-between rounded-lg border border-slate-200 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800/50"
          >
            <a :href="item.url" target="_blank" rel="noopener" class="truncate text-teal-700 dark:text-teal-400">
              {{ item.name }}
            </a>
            <button type="button" class="ml-2 shrink-0 text-rose-500" @click="removeExisting(item.path)">
              移除
            </button>
          </li>
          <li
            v-for="(file, idx) in newFiles"
            :key="'new-' + idx"
            class="flex items-center justify-between rounded-lg border border-slate-200 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800/50"
          >
            <span class="truncate text-slate-700 dark:text-slate-200">{{ file.name }}</span>
            <button type="button" class="ml-2 shrink-0 text-rose-500 dark:text-rose-400" @click="removeNew(idx)">移除</button>
          </li>
        </ul>
      </div>

      <p v-if="error" class="mt-3 text-sm text-rose-600">{{ error }}</p>
      <button
        type="button"
        class="mt-6 w-full rounded-2xl bg-teal-600 py-4 text-lg font-semibold text-white disabled:opacity-60"
        :disabled="saving"
        @click="handleSubmit"
      >
        {{ saving ? '保存中…' : submitLabel }}
      </button>
    </template>

    <div
      v-if="categoryDialogOpen"
      class="fixed inset-0 z-[65] flex items-end justify-center bg-black/40 p-4 sm:items-center"
      @click.self="closeCategoryDialog"
    >
      <div class="app-card w-full max-w-sm rounded-2xl p-5 shadow-lg">
        <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">
          新建{{ form.type === 'income' ? '收入' : '支出' }}分类
        </h2>
        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">仅在你的账本中显示，其他用户不可见。</p>
        <label class="mt-4 block text-sm font-medium text-slate-600 dark:text-slate-300">名称</label>
        <input
          v-model="newCategory.name"
          type="text"
          maxlength="32"
          class="app-field mt-1 w-full"
          placeholder="例如：宠物、房租"
        />
        <p class="mt-4 text-sm font-medium text-slate-600 dark:text-slate-300">图标</p>
        <div class="mt-2 grid grid-cols-6 gap-2">
          <button
            v-for="iconKey in pickableIcons"
            :key="iconKey"
            type="button"
            class="flex items-center justify-center rounded-lg border p-2"
            :class="
              newCategory.icon === iconKey
                ? 'border-teal-500 bg-teal-50 dark:border-teal-500 dark:bg-teal-950/50'
                : 'border-slate-200 dark:border-slate-600'
            "
            @click="newCategory.icon = iconKey"
          >
            <CategoryIcon :icon="iconKey" :active="newCategory.icon === iconKey" />
          </button>
        </div>
        <p v-if="categoryError" class="mt-3 text-sm text-rose-600 dark:text-rose-400">{{ categoryError }}</p>
        <div class="mt-5 grid grid-cols-2 gap-2">
          <button
            type="button"
            class="rounded-xl border border-slate-200 py-3 font-medium text-slate-700 dark:border-slate-600 dark:text-slate-200"
            @click="closeCategoryDialog"
          >
            取消
          </button>
          <button
            type="button"
            class="rounded-xl bg-teal-600 py-3 font-medium text-white disabled:opacity-60"
            :disabled="categorySaving"
            @click="saveCategory"
          >
            {{ categorySaving ? '保存中…' : '保存' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { createCategory, getAccounts, getCategories } from '../api/http';
import { defaultDatetimeLocal } from '../utils/dates';
import CategoryIcon from './CategoryIcon.vue';
import { PICKABLE_ICONS } from '../utils/categoryIcons';

const props = defineProps({
    mode: {
        type: String,
        default: 'full',
    },
    initial: {
        type: Object,
        default: () => ({}),
    },
    existingAttachments: {
        type: Array,
        default: () => [],
    },
    showDate: {
        type: Boolean,
        default: false,
    },
    submitLabel: {
        type: String,
        default: '保存',
    },
    saving: {
        type: Boolean,
        default: false,
    },
    error: {
        type: String,
        default: '',
    },
});

const emit = defineEmits(['submit']);

const form = reactive({
    type: props.initial.type ?? 'expense',
    amount: props.initial.amount != null ? String(props.initial.amount) : '',
    category_id: props.initial.category_id ?? null,
    account_id: props.initial.account_id != null ? String(props.initial.account_id) : '',
    note: props.initial.note ?? '',
    occurred_at: props.initial.occurred_at ?? '',
});

const categories = ref([]);
const accounts = ref([]);
const categoryDialogOpen = ref(false);
const categorySaving = ref(false);
const categoryError = ref('');
const pickableIcons = PICKABLE_ICONS;
const newCategory = reactive({ name: '', icon: 'tag' });
const newFiles = ref([]);
const removedPaths = ref([]);
const fileInput = ref(null);
const moreOpen = ref(false);

const existingVisible = computed(() =>
    props.existingAttachments.filter((a) => !removedPaths.value.includes(a.path))
);

const totalAttachments = computed(() => existingVisible.value.length + newFiles.value.length);

function lastCategoryKey(type) {
    return `account-book-last-category-${type}`;
}

function readLastCategoryId(type) {
    const raw = localStorage.getItem(lastCategoryKey(type));
    if (!raw) {
        return null;
    }
    const id = Number(raw);
    return Number.isFinite(id) ? id : null;
}

function applyDefaultCategory() {
    const lastId = readLastCategoryId(form.type);
    if (lastId && categories.value.some((c) => c.id === lastId)) {
        form.category_id = lastId;
        return;
    }
    if (!categories.value.find((c) => c.id === form.category_id)) {
        form.category_id = categories.value[0]?.id ?? null;
    }
}

function selectCategory(id) {
    form.category_id = id;
}

async function loadCategories() {
    categories.value = await getCategories(form.type);
    applyDefaultCategory();
}

watch(() => form.type, loadCategories);

async function loadAccounts() {
    accounts.value = await getAccounts();
    if (!form.account_id && accounts.value.length) {
        const def = accounts.value.find((a) => a.is_default) ?? accounts.value[0];
        form.account_id = String(def.id);
    }
}

onMounted(async () => {
    if (props.mode === 'quick' && !form.occurred_at) {
        form.occurred_at = defaultDatetimeLocal();
    }
    await Promise.all([loadCategories(), loadAccounts()]);
});

function openCategoryDialog() {
    categoryError.value = '';
    newCategory.name = '';
    newCategory.icon = 'tag';
    categoryDialogOpen.value = true;
}

function closeCategoryDialog() {
    categoryDialogOpen.value = false;
}

async function saveCategory() {
    const name = newCategory.name.trim();
    if (!name) {
        categoryError.value = '请填写分类名称';
        return;
    }
    categorySaving.value = true;
    categoryError.value = '';
    try {
        const cat = await createCategory({
            name,
            type: form.type,
            icon: newCategory.icon,
        });
        categories.value = [...categories.value, cat];
        form.category_id = cat.id;
        closeCategoryDialog();
    } catch (e) {
        categoryError.value =
            e.response?.data?.message ||
            e.response?.data?.errors?.name?.[0] ||
            '添加失败';
    } finally {
        categorySaving.value = false;
    }
}

function onFilesPicked(event) {
    const picked = Array.from(event.target.files ?? []);
    const room = Math.max(0, 5 - totalAttachments.value);
    newFiles.value = [...newFiles.value, ...picked.slice(0, room)];
    if (fileInput.value) {
        fileInput.value.value = '';
    }
}

function removeNew(index) {
    newFiles.value = newFiles.value.filter((_, i) => i !== index);
}

function removeExisting(path) {
    removedPaths.value = [...removedPaths.value, path];
}

function handleSubmit() {
    if (!form.category_id || !form.amount) {
        emit('submit', null);
        return;
    }
    if (totalAttachments.value > 5) {
        emit('submit', { error: '附件最多 5 个' });
        return;
    }
    if (props.mode === 'quick' && form.category_id) {
        localStorage.setItem(lastCategoryKey(form.type), String(form.category_id));
    }
    const occurredAt = form.occurred_at || (props.mode === 'quick' ? defaultDatetimeLocal() : null);
    emit('submit', {
        type: form.type,
        amount: form.amount,
        category_id: form.category_id,
        account_id: form.account_id ? Number(form.account_id) : null,
        note: form.note || null,
        occurred_at: occurredAt,
        files: [...newFiles.value],
        remove_attachment_paths: [...removedPaths.value],
    });
}
</script>