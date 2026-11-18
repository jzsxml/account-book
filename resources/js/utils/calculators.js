const MAX_PERIODS = 600;

function round2(n) {
    return Math.round((n + Number.EPSILON) * 100) / 100;
}

/** @param {{ principal: number, annualRatePercent: number, months: number, compound: boolean }} input */
export function calcDeposit(input) {
    const principal = Number(input.principal);
    const annualRate = Number(input.annualRatePercent) / 100;
    const months = Math.min(Math.floor(Number(input.months)), MAX_PERIODS);

    if (!(principal > 0 && annualRate >= 0 && months > 0)) {
        return null;
    }

    const schedule = [];
    const monthlyRate = annualRate / 12;
    let prevBalance = principal;
    let totalInterest = 0;

    for (let period = 1; period <= months; period++) {
        let balance;
        let periodInterest;

        if (input.compound) {
            balance = principal * Math.pow(1 + monthlyRate, period);
            periodInterest = period === 1 ? balance - principal : balance - prevBalance;
        } else {
            periodInterest = principal * monthlyRate;
            totalInterest += periodInterest;
            balance = principal + totalInterest;
        }

        if (input.compound) {
            totalInterest = balance - principal;
        }

        schedule.push({
            period,
            periodInterest: round2(periodInterest),
            cumulativeInterest: round2(input.compound ? balance - principal : totalInterest),
            balance: round2(balance),
        });
        prevBalance = balance;
    }

    const last = schedule[schedule.length - 1];

    return {
        principal,
        interest: last.cumulativeInterest,
        maturity: last.balance,
        schedule,
    };
}

/** @param {{ principal: number, annualRatePercent: number, months: number, method: 'equal_payment' | 'equal_principal' }} input */
export function calcLoan(input) {
    const principal = Number(input.principal);
    const annualRate = Number(input.annualRatePercent) / 100;
    const months = Math.min(Math.floor(Number(input.months)), MAX_PERIODS);

    if (!(principal > 0 && annualRate >= 0 && months > 0)) {
        return null;
    }

    const monthlyRate = annualRate / 12;
    const schedule = [];

    if (input.method === 'equal_principal') {
        const principalPerMonth = principal / months;
        let balance = principal;
        let totalInterest = 0;

        for (let period = 1; period <= months; period++) {
            const interest = balance * monthlyRate;
            const payment = principalPerMonth + interest;
            const principalPaid = principalPerMonth;
            balance = Math.max(0, balance - principalPaid);
            totalInterest += interest;

            schedule.push({
                period,
                payment: round2(payment),
                principal: round2(principalPaid),
                interest: round2(interest),
                remaining: round2(balance),
            });
        }

        return {
            method: 'equal_principal',
            firstPayment: schedule[0].payment,
            lastPayment: schedule[schedule.length - 1].payment,
            totalPayment: round2(principal + totalInterest),
            totalInterest: round2(totalInterest),
            schedule,
        };
    }

    let monthlyPayment;
    if (monthlyRate === 0) {
        monthlyPayment = principal / months;
    } else {
        const factor = Math.pow(1 + monthlyRate, months);
        monthlyPayment = (principal * monthlyRate * factor) / (factor - 1);
    }

    let balance = principal;
    let totalInterest = 0;

    for (let period = 1; period <= months; period++) {
        const interest = balance * monthlyRate;
        let principalPaid = monthlyPayment - interest;
        if (period === months) {
            principalPaid = balance;
        }
        const payment = principalPaid + interest;
        balance = Math.max(0, balance - principalPaid);
        totalInterest += interest;

        schedule.push({
            period,
            payment: round2(payment),
            principal: round2(principalPaid),
            interest: round2(interest),
            remaining: round2(balance),
        });
    }

    const totalPayment = schedule.reduce((sum, row) => sum + row.payment, 0);

    return {
        method: 'equal_payment',
        monthlyPayment: round2(monthlyPayment),
        totalPayment: round2(totalPayment),
        totalInterest: round2(totalInterest),
        schedule,
    };
}