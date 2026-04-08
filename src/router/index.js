import { createRouter, createWebHistory } from "vue-router";
import Login from "../components/Login.vue";
import Profile from "../components/profile.vue";
import Tracking from "../components/Tracking.vue";
import FollowPage from "../components/FollowPage.vue";
import UnitDashboard from "../components/views/UnitDashboard.vue";

const router = createRouter({
    history: createWebHistory(),
    routes: [
        { path: "/", redirect: "/tracking" },
        { path: "/login", component: Login },
        { path: "/profile", component: Profile, meta: { requiresAuth: true } },
        { path: "/tracking", component: Tracking, meta: { requiresAuth: true } },
        { path: "/follow/:imei", component: FollowPage, meta: { requiresAuth: true } },
        { path: "/dashboard/:imei", component: UnitDashboard, meta: { requiresAuth: true } },
        { name: "reports-viewer", path: "/reports/viewer", component: () => import("../components/views/ReportViewerPage.vue"), meta: { requiresAuth: true } },
        { path: '/reports/drives-stops-logic', name: 'reports-drives-stops-logic', component: () => import('../components/views/DrivesStopsLogicViewer.vue') },
        { path: '/reports/current-position', name: 'reports-current-position', component: () => import('../components/views/CurrentPositionViewer.vue') },
        { path: '/reports/current-position-off', name: 'reports-current-position-off', component: () => import('../components/views/CurrentPositionOffViewer.vue') },
        { path: '/reports/underspeed', name: 'reports-underspeed', component: () => import('../components/views/UnderspeedViewer.vue') },
    ],
});

router.beforeEach((to) => {
    const token = localStorage.getItem("token");
    if (to.meta.requiresAuth && !token) return "/login";
    if (to.path === "/login" && token) return "/profile";
});

export default router;