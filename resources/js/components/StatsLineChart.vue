<template>
  <div class="h-52 w-full">
    <Line v-if="chartData" :data="chartData" :options="chartOptions" />
    <p v-else class="flex h-full items-center justify-center text-sm text-slate-400">暂无趋势数据</p>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { isDarkMode } from '../composables/useTheme';
import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    Tooltip,
    Filler,
} from 'chart.js';
import { Line } from 'vue-chartjs';

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, Tooltip, Filler);

const props = defineProps({
    series: {
        type: Array,
        default: () => [],
    },
    label: {
        type: String,
        default: '收入',
    },
    variant: {
        type: String,
        default: 'income',
        validator: (v) => ['income', 'expense'].includes(v),
    },
});

const palette = computed(() =>
    props.variant === 'expense'
        ? { border: 'rgb(234, 88, 12)', fill: 'rgba(234, 88, 12, 0.12)' }
        : { border: 'rgb(5, 150, 105)', fill: 'rgba(5, 150, 105, 0.12)' }
);

function formatLabel(dateStr) {
    const d = new Date(dateStr + 'T12:00:00');
    return `${d.getMonth() + 1}/${d.getDate()}`;
}

const chartData = computed(() => {
    const points = props.series ?? [];
    if (!points.length) {
        return null;
    }

    const colors = palette.value;

    return {
        labels: points.map((p) => formatLabel(p.date)),
        datasets: [
            {
                label: props.label,
                data: points.map((p) => p.total),
                borderColor: colors.border,
                backgroundColor: colors.fill,
                fill: true,
                tension: 0.35,
                pointRadius: points.length > 20 ? 0 : 3,
                pointHoverRadius: 4,
                borderWidth: 2,
            },
        ],
    };
});

const chartOptions = computed(() => {
    const muted = isDarkMode.value ? '#94a3b8' : '#64748b';
    const grid = isDarkMode.value ? 'rgba(51, 65, 85, 0.6)' : 'rgba(148, 163, 184, 0.2)';

    return {
        responsive: true,
        maintainAspectRatio: false,
        interaction: {
            mode: 'index',
            intersect: false,
        },
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: isDarkMode.value ? '#1e293b' : '#fff',
                titleColor: isDarkMode.value ? '#e2e8f0' : '#334155',
                bodyColor: isDarkMode.value ? '#e2e8f0' : '#334155',
                borderColor: isDarkMode.value ? '#475569' : '#e2e8f0',
                borderWidth: 1,
                callbacks: {
                    label(ctx) {
                        const v = ctx.parsed.y ?? 0;
                        return ` ¥${Number(v).toFixed(2)}`;
                    },
                },
            },
        },
        scales: {
            x: {
                grid: { display: false },
                ticks: {
                    maxTicksLimit: 8,
                    font: { size: 11 },
                    color: muted,
                },
            },
            y: {
                beginAtZero: true,
                ticks: {
                    font: { size: 11 },
                    color: muted,
                    callback(value) {
                        return value >= 1000 ? `${value / 1000}k` : value;
                    },
                },
                grid: { color: grid },
            },
        },
    };
});
</script>