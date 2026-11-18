export function formatMoney(value, currency = 'CNY') {
    const amount = Number(value ?? 0);
    try {
        return new Intl.NumberFormat('zh-CN', {
            style: 'currency',
            currency,
            minimumFractionDigits: 2,
        }).format(amount);
    } catch {
        return `¥${amount.toFixed(2)}`;
    }
}