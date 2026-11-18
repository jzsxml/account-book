import { createRouter, createWebHistory } from 'vue-router';
import { fetchMe } from './api/http';
import MobileLayout from './layouts/MobileLayout.vue';

const routes = [
    {
        path: '/login',
        name: 'login',
        component: () => import('./views/LoginView.vue'),
        meta: { guest: true },
    },
    {
        path: '/register',
        name: 'register',
        component: () => import('./views/RegisterView.vue'),
        meta: { guest: true },
    },
    {
        path: '/verify-email',
        name: 'verify-email',
        component: () => import('./views/VerifyEmailView.vue'),
        meta: { guest: true },
    },
    {
        path: '/',
        component: MobileLayout,
        meta: { auth: true },
        children: [
            { path: '', name: 'home', component: () => import('./views/HomeView.vue') },
            { path: 'stats', name: 'stats', component: () => import('./views/StatsView.vue') },
            { path: 'stats/report', name: 'stats-report', component: () => import('./views/MonthReportView.vue') },
            { path: 'bills', name: 'bills', component: () => import('./views/BillsView.vue') },
            { path: 'add', name: 'add', component: () => import('./views/AddView.vue') },
            { path: 'transactions', name: 'transactions', component: () => import('./views/TransactionsView.vue') },
            {
                path: 'transactions/:id/edit',
                name: 'transaction-edit',
                component: () => import('./views/EditTransactionView.vue'),
            },
            { path: 'tools', name: 'tools', component: () => import('./views/ToolsView.vue') },
            { path: 'settings', name: 'settings', component: () => import('./views/SettingsView.vue') },
        ],
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

router.beforeEach(async (to) => {
    if (!to.meta.auth && !to.meta.guest) {
        return true;
    }

    let user = null;
    try {
        user = await fetchMe();
    } catch {
        user = null;
    }

    if (to.meta.auth && !user) {
        return { name: 'login', query: { redirect: to.fullPath } };
    }

    if (to.meta.guest && user) {
        return { name: 'home' };
    }

    return true;
});

export default router;