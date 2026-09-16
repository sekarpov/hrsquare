import { createRouter, createWebHistory } from "vue-router";
import { useAuth } from "../stores/auth";
export const router = createRouter({
    history: createWebHistory(),
    routes: [
        {
            path: "/login",
            component: () => import("../views/LoginView.vue"),
            meta: { public: true },
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
            meta: { recruiter: true },
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
    if (to.path === "/login" && auth.user) return "/candidates";
    if (to.meta.recruiter && !auth.isRecruiter) return "/candidates";
});
