<template>
  <div class="mx-auto h-56 w-full max-w-sm">
    <Pie v-if="chartData" :data="chartData" :options="chartOptions" />
    <p v-else class="flex h-full items-center justify-center text-sm text-slate-400">暂无占比数据</p>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { isDarkMode } from '../composables/useTheme';
import {
    ArcElement,
    Chart as ChartJS,
    Legend,
    Tooltip,
} from 'chart.js';
import { Pie } from 'vue-chartjs';

ChartJS.register(ArcElement, Tooltip, Legend);

const props = defineProps({
    rows: {
        type: Array,
        default: () => [],
    },
    variant: {
        type: String,
        default: 'income',
    },
});

const incomeColors = [
    'rgb(5, 150, 105)',
    'rgb(16, 185, 129)',
    'rgb(52, 211, 153)',
    'rgb(110, 231, 183)',
    'rgb(4, 120, 87)',
    'rgb(6, 95, 70)',
    'rgb(20, 184, 166)',
];

const expenseColors = [
    'rgb(234, 88, 12)',
    'rgb(249, 115, 22)',
    'rgb(251, 146, 60)',
    'rgb(253, 186, 116)',
    'rgb(194, 65, 12)',
    'rgb(154, 52, 18)',
    'rgb(234, 179, 8)',
];

const chartData = computed(() => {
    const rows = props.rows ?? [];
    if (!rows.length) {
        return null;
    }

    const palette = props.variant === 'expense' ? expenseColors : incomeColors;

    return {
        labels: rows.map((r) => r.name),
        datasets: [
            {
                data: rows.map((r) => Number(r.total)),
                backgroundColor: rows.map((_, i) => palette[i % palette.length]),
                borderWidth: 2,
                borderColor: isDarkMode.value ? '#0f172a' : '#ffffff',
            },
        ],
    };
});

const chartOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: 'bottom',
            labels: {
                boxWidth: 10,
                boxHeight: 10,
                font: { size: 11 },
                color: isDarkMode.value ? '#cbd5e1' : '#475569',
                padding: 12,
            },
        },
        tooltip: {
            backgroundColor: isDarkMode.value ? '#1e293b' : '#fff',
            titleColor: isDarkMode.value ? '#e2e8f0' : '#334155',
            bodyColor: isDarkMode.value ? '#e2e8f0' : '#334155',
            borderColor: isDarkMode.value ? '#475569' : '#e2e8f0',
            borderWidth: 1,
            callbacks: {
                label(ctx) {
                    const value = ctx.parsed ?? 0;
                    const data = ctx.dataset.data ?? [];
                    const total = data.reduce((sum, n) => sum + Number(n), 0);
                    const pct = total > 0 ? ((value / total) * 100).toFixed(1) : '0';
                    return ` ${ctx.label}: ¥${Number(value).toFixed(2)} (${pct}%)`;
                },
            },
        },
    },
}));
</script>