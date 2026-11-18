<template>
  <svg
    class="h-6 w-6"
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    stroke-width="1.75"
    stroke-linecap="round"
    stroke-linejoin="round"
    aria-hidden="true"
  >
    <template v-if="name === 'home'">
      <path d="M4 10.5 12 4l8 6.5V20a1 1 0 0 1-1 1h-5v-6H10v6H5a1 1 0 0 1-1-1v-9.5Z" />
    </template>
    <template v-else-if="name === 'stats'">
      <path d="M4 20V10M10 20V4M16 20v-6M22 20V8" />
    </template>
    <template v-else-if="name === 'add'">
      <circle cx="12" cy="12" r="9" />
      <path d="M12 8v8M8 12h8" />
    </template>
    <template v-else-if="name === 'ledger'">
      <path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01" />
    </template>
    <template v-else-if="name === 'tools'">
      <rect x="4" y="3" width="16" height="18" rx="2" />
      <path d="M8 7h8M8 11h2M14 11h2M8 15h2M14 15h3" />
    </template>
    <template v-else-if="name === 'user'">
      <circle cx="12" cy="8" r="3.5" />
      <path d="M5 20c0-3.5 3.1-6 7-6s7 2.5 7 6" />
    </template>
  </svg>
</template>

<script setup>
defineProps({
    name: { type: String, required: true },
});
</script>