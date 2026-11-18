import axios from 'axios';

export async function ensureCsrf() {
    await axios.get('/sanctum/csrf-cookie');
}

export async function fetchMe() {
    const { data } = await axios.get('/api/me');
    return data;
}

export async function updateProfile(payload) {
    const { data } = await axios.patch('/api/me', payload);
    return data;
}

export async function updateAvatar(file) {
    const formData = new FormData();
    formData.append('avatar', file);
    const { data } = await axios.post('/api/me/avatar', formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
    });
    return data;
}

export async function updatePassword(payload) {
    const { data } = await axios.put('/api/me/password', payload);
    return data;
}

export async function login(payload) {
    await ensureCsrf();
    const { data } = await axios.post('/login', payload);
    return data;
}

export async function register(payload) {
    await ensureCsrf();
    const { data } = await axios.post('/register', payload);
    return data;
}

export async function logout() {
    await ensureCsrf();
    const { data } = await axios.post('/logout');
    return data;
}

export async function resendVerification(email) {
    await ensureCsrf();
    const { data } = await axios.post('/email/verification-notification', { email });
    return data;
}

export async function getCategories(type) {
    const { data } = await axios.get('/api/categories', { params: { type } });
    return data.data;
}

export async function createCategory(payload) {
    const { data } = await axios.post('/api/categories', payload);
    return data;
}

export async function getTransactions(params = {}) {
    const { data } = await axios.get('/api/transactions', { params });
    return data;
}

function parseFilenameFromDisposition(disposition) {
    if (!disposition) {
        return null;
    }
    const utf8 = /filename\*=UTF-8''([^;]+)/i.exec(disposition);
    if (utf8?.[1]) {
        try {
            return decodeURIComponent(utf8[1]);
        } catch {
            return utf8[1];
        }
    }
    const plain = /filename="?([^";]+)"?/i.exec(disposition);
    return plain?.[1] ?? null;
}

export async function exportTransactionsExcel(params = {}) {
    const response = await axios.get('/api/transactions/export', {
        params,
        responseType: 'blob',
    });

    const contentType = response.headers['content-type'] || '';
    if (response.status >= 400 || contentType.includes('application/json')) {
        const text = await response.data.text();
        let body = { message: '导出失败' };
        try {
            body = JSON.parse(text);
        } catch {
            body.message = text || body.message;
        }
        throw Object.assign(new Error(body.message || '导出失败'), { response: { data: body } });
    }

    const filename =
        parseFilenameFromDisposition(response.headers['content-disposition']) || '记账明细.xlsx';
    const url = URL.createObjectURL(response.data);
    const link = document.createElement('a');
    link.href = url;
    link.download = filename;
    link.rel = 'noopener';
    document.body.appendChild(link);
    link.click();
    link.remove();
    URL.revokeObjectURL(url);
}

function appendTransactionFields(formData, payload) {
    Object.entries(payload).forEach(([key, value]) => {
        if (value === null || value === undefined || key === 'files' || key === 'remove_attachment_paths') {
            return;
        }
        formData.append(key, value);
    });
}

export async function createTransaction(payload, files = []) {
    if (files?.length) {
        const formData = new FormData();
        appendTransactionFields(formData, payload);
        files.forEach((file, index) => {
            formData.append(`attachments[${index}]`, file);
        });
        const { data } = await axios.post('/api/transactions', formData, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });
        return data;
    }

    const { data } = await axios.post('/api/transactions', payload);
    return data;
}

export async function getTransaction(id) {
    const { data } = await axios.get(`/api/transactions/${id}`);
    return data;
}

export async function updateTransaction(id, payload, files = [], removePaths = []) {
    if (files?.length || removePaths?.length) {
        const formData = new FormData();
        appendTransactionFields(formData, payload);
        if (payload.occurred_at) {
            formData.set('occurred_at', payload.occurred_at);
        }
        files.forEach((file, index) => {
            formData.append(`attachments[${index}]`, file);
        });
        removePaths.forEach((path, index) => {
            formData.append(`remove_attachment_paths[${index}]`, path);
        });
        const { data } = await axios.patch(`/api/transactions/${id}`, formData, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });
        return data;
    }

    const { data } = await axios.patch(`/api/transactions/${id}`, payload);
    return data;
}

export async function deleteTransaction(id) {
    const { data } = await axios.delete(`/api/transactions/${id}`);
    return data;
}

export async function getStats(params) {
    const { data } = await axios.get('/api/stats', { params });
    return data;
}

export async function getStatsOverview(params) {
    const { data } = await axios.get('/api/stats', { params: { ...params, overview: 1 } });
    return data;
}

export async function getBookkeepingHabits() {
    const { data } = await axios.get('/api/stats', { params: { habits: 1 } });
    return data;
}

export async function getMonthReport(anchor) {
    const params = { month_report: 1 };
    if (anchor) {
        params.anchor = anchor;
    }
    const { data } = await axios.get('/api/stats', { params });
    return data;
}

export async function getBillsOverview(year) {
    const { data } = await axios.get('/api/bills/overview', { params: { year } });
    return data;
}

export async function getAccounts() {
    const { data } = await axios.get('/api/accounts');
    return data.data;
}

export async function getBudget(year, month) {
    const { data } = await axios.get('/api/budget', { params: { year, month } });
    return data;
}

export async function updateBudget(payload) {
    const { data } = await axios.put('/api/budget', payload);
    return data;
}

export async function getBookSchedules() {
    const { data } = await axios.get('/api/book/schedules');
    return data;
}

export async function updateBookSchedules(payload) {
    const { data } = await axios.put('/api/book/schedules', payload);
    return data;
}