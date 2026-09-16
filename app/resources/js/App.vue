<script setup lang="ts">
import { onMounted, onUnmounted, ref } from "vue";
import { useRoute, useRouter } from "vue-router";
import { useAuth } from "./stores/auth";
import { useToast } from "primevue/usetoast";
import Toast from "primevue/toast";
import ConfirmDialog from "primevue/confirmdialog";
import Button from "primevue/button";
const route = useRoute(),
    router = useRouter(),
    auth = useAuth(),
    toast = useToast(),
    menu = ref(false);
async function logout() {
    try {
        await auth.logout();
        await router.push("/login");
    } catch {}
}
function apiError(event: Event) {
    const { status, message } = (event as CustomEvent).detail;
    if (status === 401) {
        const wasAuthenticated = !!auth.user;
        auth.user = null;
        if (wasAuthenticated && route.path != "/login")
            void router.push("/login");
        return;
    }
    toast.add({
        severity: status === 419 ? "warn" : "error",
        summary: "Не удалось выполнить запрос",
        detail: message,
        life: 5000,
    });
}
onMounted(() => window.addEventListener("api:error", apiError));
onUnmounted(() => window.removeEventListener("api:error", apiError));
</script>
<template>
    <Toast /><ConfirmDialog />
    <div v-if="route.meta.public" class="public-layout"><RouterView /></div>
    <div v-else class="workspace">
        <aside class="sidebar" :class="{ expanded: menu }">
            <RouterLink to="/candidates" class="brand"
                >HR<span>Square</span
                ><span class="brand-dot">.</span></RouterLink
            ><small class="workspace-label">ОЦЕНКА КАНДИДАТОВ</small>
            <nav>
                <RouterLink to="/candidates" @click="menu = false"
                    ><i class="pi pi-users" />Кандидаты</RouterLink
                ><RouterLink
                    v-if="auth.isRecruiter"
                    to="/users"
                    @click="menu = false"
                    ><i class="pi pi-user-edit" />Пользователи</RouterLink
                >
            </nav>
            <div class="sidebar-bottom">
                <div class="user-avatar">
                    {{ auth.user?.fullName.charAt(0) }}
                </div>
                <div>
                    <strong>{{ auth.user?.fullName }}</strong
                    ><small>{{
                        auth.isRecruiter ? "Рекрутер" : "Менеджер"
                    }}</small>
                </div>
                <Button
                    icon="pi pi-sign-out"
                    text
                    rounded
                    aria-label="Выйти"
                    v-tooltip="'Выйти'"
                    @click="logout"
                />
            </div>
        </aside>
        <main class="content">
            <div class="mobile-bar">
                <Button
                    icon="pi pi-bars"
                    text
                    aria-label="Открыть меню"
                    @click="menu = !menu"
                /><b>HRSquare</b>
            </div>
            <RouterView :key="route.fullPath" />
        </main>
    </div>
</template>
