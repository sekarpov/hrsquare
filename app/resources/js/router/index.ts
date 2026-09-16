import { createRouter, createWebHistory } from "vue-router";
import { useAuth } from "../stores/auth";
export const router = createRouter({
    history: createWebHistory(),
    scrollBehavior(to, from, saved) {
        if (to.hash) return { el: to.hash, top: 24 };
        return saved ?? { top: 0 };
    },
    routes: [
        {
            path: "/login",
            component: () => import("../views/LoginView.vue"),
            meta: { public: true },
        },
        {
            path: "/account/password",
            component: () => import("../views/PasswordView.vue"),
        },
        { path: "/", redirect: "/candidates" },
        {
            path: "/candidates",
            component: () => import("../views/CandidatesView.vue"),
        },
        {
            path: "/candidates/:id",
            component: () => import("../views/CandidateDetailView.vue"),
        },
        {
            path: "/assessments/:id",
            component: () => import("../views/AssessmentView.vue"),
        },
        {
            path: "/users",
            component: () => import("../views/UsersView.vue"),
            meta: { managesUsers: true },
        },
        {
            path: "/forbidden",
            component: () => import("../views/NotFoundView.vue"),
            meta: { forbidden: true },
        },
        {
            path: "/:pathMatch(.*)*",
            component: () => import("../views/NotFoundView.vue"),
        },
    ],
});
router.beforeEach(async (to) => {
    const auth = useAuth();
    await auth.hydrate();
    if (!to.meta.public && !auth.user) return "/login";
    if (auth.user?.mustChangePassword && to.path !== "/account/password")
        return "/account/password";
    if (to.path === "/login" && auth.user) return "/candidates";
    if (to.meta.managesUsers && !auth.canManageUsers) return "/forbidden";
});
