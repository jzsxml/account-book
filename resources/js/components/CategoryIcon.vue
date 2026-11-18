<template>
  <svg
    class="h-7 w-7 text-slate-600 dark:text-slate-300"
    :class="active ? 'text-teal-600 dark:text-teal-400' : ''"
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    stroke-width="1.75"
    stroke-linecap="round"
    stroke-linejoin="round"
    aria-hidden="true"
  >
    <template v-if="keyName === 'wallet'">
      <rect x="3" y="6" width="18" height="13" rx="2" />
      <path d="M3 10h18M16 14h.01" />
    </template>
    <template v-else-if="keyName === 'award'">
      <circle cx="12" cy="9" r="3" />
      <path d="M8.2 9 6 20l6-3 6 3-2.2-11M12 6V3" />
    </template>
    <template v-else-if="keyName === 'briefcase'">
      <rect x="4" y="8" width="16" height="11" rx="2" />
      <path d="M9 8V6a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2" />
    </template>
    <template v-else-if="keyName === 'trend-up'">
      <path d="M4 18h16M6 14l4-4 3 3 5-6" />
    </template>
    <template v-else-if="keyName === 'plus-circle'">
      <circle cx="12" cy="12" r="9" />
      <path d="M12 8v8M8 12h8" />
    </template>
    <template v-else-if="keyName === 'utensil'">
      <path d="M6 3v8a3 3 0 0 0 6 0V3M9 11v10M18 3v18" />
    </template>
    <template v-else-if="keyName === 'car'">
      <path d="M5 15h14l-1.5-5H6.5L5 15Z" />
      <circle cx="7.5" cy="17.5" r="1.5" />
      <circle cx="16.5" cy="17.5" r="1.5" />
    </template>
    <template v-else-if="keyName === 'cart'">
      <path d="M6 6h15l-1.5 9h-11L6 6ZM6 6 5 3H3" />
      <circle cx="9" cy="19" r="1.5" />
      <circle cx="17" cy="19" r="1.5" />
    </template>
    <template v-else-if="keyName === 'building'">
      <path d="M4 20V8l8-4 8 4v12H4Z" />
      <path d="M9 20v-5h6v5M9 10h.01M15 10h.01" />
    </template>
    <template v-else-if="keyName === 'leisure'">
      <rect x="5" y="6" width="14" height="12" rx="2" />
      <path d="M9 10h6M12 10v4" />
    </template>
    <template v-else-if="keyName === 'health'">
      <path d="M12 5v14M5 12h14" />
      <circle cx="12" cy="12" r="9" />
    </template>
    <template v-else-if="keyName === 'minus-circle'">
      <circle cx="12" cy="12" r="9" />
      <path d="M8 12h8" />
    </template>
    <template v-else-if="keyName === 'folder'">
      <path d="M4 8V6a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V8Z" />
    </template>
    <template v-else>
      <path d="M20.6 7.4 16 3l-2.3 5.3-5.3 2.3 5.3 2.3 2.3 5.3 5.3-2.3 2.3-5.3Z" />
      <circle cx="8" cy="14" r="2" />
    </template>
  </svg>
</template>

<script setup>
import { computed } from 'vue';
import { resolveCategoryIconKey } from '../utils/categoryIcons';

const props = defineProps({
    icon: { type: String, default: '' },
    name: { type: String, default: '' },
    active: { type: Boolean, default: false },
});

const keyName = computed(() => resolveCategoryIconKey(props.icon, props.name));
</script>