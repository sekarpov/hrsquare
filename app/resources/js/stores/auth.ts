import { defineStore } from "pinia";
import { ref, computed } from "vue";
import { authApi } from "../api/auth";
import type { User } from "../types";
export const useAuth = defineStore("auth", () => {
    const user = ref<User | null>(null),
        initialized = ref(false);
    const isRecruiter = computed(() => user.value?.role === "RECRUITER");
    const isAdmin = computed(() => user.value?.role === "ADMIN");
    const canManageUsers = computed(() => isAdmin.value || isRecruiter.value);
    const canManageCandidates = computed(
        () => isAdmin.value || isRecruiter.value,
    );
    async function hydrate() {
        if (initialized.value) return;
        try {
            user.value = await authApi.me();
        } catch {
            user.value = null;
        } finally {
            initialized.value = true;
        }
    }
    async function login(login: string, password: string) {
        user.value = await authApi.login(login, password);
        initialized.value = true;
    }
    async function logout() {
        await authApi.logout();
        user.value = null;
    }
    return {
        user,
        initialized,
        isRecruiter,
        isAdmin,
        canManageUsers,
        canManageCandidates,
        hydrate,
        login,
        logout,
    };
});
