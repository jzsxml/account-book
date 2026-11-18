<template>
  <div>
  <div class="mb-2">
  <RouterLink to="/settings" class="text-sm text-teal-600 dark:text-teal-400">‹ 返回我的</RouterLink>
  </div>
    <h1 class="text-xl font-bold text-slate-900 dark:text-slate-100">财务工具</h1>

    <div class="mt-4 flex rounded-xl bg-slate-100 p-1 dark:bg-slate-800">
      <button
        v-for="tab in tabs"
        :key="tab.id"
        type="button"
        class="flex-1 rounded-lg py-2 text-sm font-medium"
        :class="activeTab === tab.id ? 'bg-white text-teal-700 shadow dark:bg-slate-900 dark:text-teal-400' : 'text-slate-500 dark:text-slate-400'"
        @click="activeTab = tab.id"
      >
        {{ tab.label }}
      </button>
    </div>

    <section v-if="activeTab === 'deposit'" class="app-card mt-4 rounded-2xl border p-4 shadow-sm">
      <h2 class="font-semibold text-slate-800 dark:text-slate-100">存款计算器</h2>
      <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">估算到期本息，不构成投资建议。</p>
      <label class="mt-4 block text-xs text-slate-500 dark:text-slate-400">本金（元）</label>
      <input v-model="deposit.principal" type="number" min="0" step="0.01" class="app-field mt-1 w-full" />
      <label class="mt-3 block text-xs text-slate-500 dark:text-slate-400">年利率（%）</label>
      <input v-model="deposit.rate" type="number" min="0" step="0.01" class="app-field mt-1 w-full" />
      <label class="mt-3 block text-xs text-slate-500 dark:text-slate-400">期限（月）</label>
      <input v-model="deposit.months" type="number" min="1" step="1" class="app-field mt-1 w-full" />
      <label class="mt-3 block text-xs text-slate-500 dark:text-slate-400">计息方式</label>
      <select v-model="deposit.compound" class="app-field mt-1 w-full">
        <option :value="false">单利</option>
        <option :value="true">复利（按月）</option>
      </select>
      <button type="button" class="mt-4 w-full rounded-xl bg-teal-600 py-3 text-sm font-medium text-white" @click="runDeposit">
        计算
      </button>
      <div v-if="depositResult" class="mt-4 space-y-2 border-t border-slate-200 pt-4 text-sm dark:border-slate-600">
        <p class="flex justify-between"><span class="text-slate-500 dark:text-slate-400">利息</span><span class="font-semibold">{{ formatMoney(depositResult.interest) }}</span></p>
        <p class="flex justify-between"><span class="text-slate-500 dark:text-slate-400">到期本息</span><span class="font-semibold text-teal-700 dark:text-teal-400">{{ formatMoney(depositResult.maturity) }}</span></p>
        <ScheduleTable
          title="每期明细"
          :rows="depositResult.schedule"
          :columns="depositColumns"
        />
      </div>
    </section>

    <section v-else class="app-card mt-4 rounded-2xl border p-4 shadow-sm">
      <h2 class="font-semibold text-slate-800 dark:text-slate-100">贷款计算器</h2>
      <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">等额本息 / 等额本金月供估算。</p>
      <label class="mt-4 block text-xs text-slate-500 dark:text-slate-400">贷款金额（元）</label>
      <input v-model="loan.principal" type="number" min="0" step="0.01" class="app-field mt-1 w-full" />
      <label class="mt-3 block text-xs text-slate-500 dark:text-slate-400">年利率（%）</label>
      <input v-model="loan.rate" type="number" min="0" step="0.01" class="app-field mt-1 w-full" />
      <label class="mt-3 block text-xs text-slate-500 dark:text-slate-400">期限（月）</label>
      <input v-model="loan.months" type="number" min="1" step="1" class="app-field mt-1 w-full" />
      <label class="mt-3 block text-xs text-slate-500 dark:text-slate-400">还款方式</label>
      <select v-model="loan.method" class="app-field mt-1 w-full">
        <option value="equal_payment">等额本息</option>
        <option value="equal_principal">等额本金</option>
      </select>
      <button type="button" class="mt-4 w-full rounded-xl bg-teal-600 py-3 text-sm font-medium text-white" @click="runLoan">
        计算
      </button>
      <div v-if="loanResult" class="mt-4 space-y-2 border-t border-slate-200 pt-4 text-sm dark:border-slate-600">
        <template v-if="loanResult.method === 'equal_payment'">
          <p class="flex justify-between"><span class="text-slate-500 dark:text-slate-400">参考月供</span><span class="font-semibold">{{ formatMoney(loanResult.monthlyPayment) }}</span></p>
        </template>
        <template v-else>
          <p class="flex justify-between"><span class="text-slate-500 dark:text-slate-400">首月还款</span><span class="font-semibold">{{ formatMoney(loanResult.firstPayment) }}</span></p>
          <p class="flex justify-between"><span class="text-slate-500 dark:text-slate-400">末月还款</span><span class="font-semibold">{{ formatMoney(loanResult.lastPayment) }}</span></p>
        </template>
        <p class="flex justify-between"><span class="text-slate-500 dark:text-slate-400">支付利息总额</span><span class="font-semibold text-orange-600 dark:text-orange-400">{{ formatMoney(loanResult.totalInterest) }}</span></p>
        <p class="flex justify-between"><span class="text-slate-500 dark:text-slate-400">还款总额</span><span class="font-semibold text-teal-700 dark:text-teal-400">{{ formatMoney(loanResult.totalPayment) }}</span></p>
        <ScheduleTable
          title="每期还款明细"
          :rows="loanResult.schedule"
          :columns="loanColumns"
        />
      </div>
    </section>
  </div>
</template>

<script setup>
import { reactive, ref } from 'vue';
import { RouterLink } from 'vue-router';
import ScheduleTable from '../components/ScheduleTable.vue';
import { calcDeposit, calcLoan } from '../utils/calculators';
import { formatMoney } from '../utils/money';

const tabs = [
    { id: 'deposit', label: '存款' },
    { id: 'loan', label: '贷款' },
];

const depositColumns = [
    { key: 'period', label: '期数' },
    { key: 'periodInterest', label: '本期利息', format: (v) => formatMoney(v) },
    { key: 'cumulativeInterest', label: '累计利息', format: (v) => formatMoney(v) },
    { key: 'balance', label: '本息合计', format: (v) => formatMoney(v) },
];

const loanColumns = [
    { key: 'period', label: '期数' },
    { key: 'payment', label: '还款额', format: (v) => formatMoney(v) },
    { key: 'principal', label: '本金', format: (v) => formatMoney(v) },
    { key: 'interest', label: '利息', format: (v) => formatMoney(v) },
    { key: 'remaining', label: '剩余本金', format: (v) => formatMoney(v) },
];

const activeTab = ref('deposit');
const depositResult = ref(null);
const loanResult = ref(null);

const deposit = reactive({
    principal: '100000',
    rate: '2.25',
    months: '12',
    compound: true,
});

const loan = reactive({
    principal: '500000',
    rate: '3.85',
    months: '360',
    method: 'equal_payment',
});

function runDeposit() {
    depositResult.value = calcDeposit({
        principal: deposit.principal,
        annualRatePercent: deposit.rate,
        months: deposit.months,
        compound: deposit.compound,
    });
}

function runLoan() {
    loanResult.value = calcLoan({
        principal: loan.principal,
        annualRatePercent: loan.rate,
        months: loan.months,
        method: loan.method,
    });
}
</script>