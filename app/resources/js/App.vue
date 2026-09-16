<script setup lang="ts">
import { onMounted, onUnmounted, ref, defineAsyncComponent } from "vue";
import { useRoute, useRouter } from "vue-router";
import { useAuth } from "./stores/auth";
import { useToast } from "primevue/usetoast";
import Toast from "primevue/toast";
import ConfirmDialog from "primevue/confirmdialog";
const Drawer = defineAsyncComponent(() => import("primevue/drawer"));
import Button from "primevue/button";
import WorkspaceNavigation from "./components/WorkspaceNavigation.vue";
const route = useRoute(),
    router = useRouter(),
    auth = useAuth(),
    toast = useToast(),
    menu = ref(false);
async function logout() {
    try {
        await auth.logout();
        menu.value = false;
        await router.push("/login");
    } catch {
        toast.add({
            severity: "error",
            summary: "Не удалось выйти",
            detail: "Проверьте соединение и попробуйте ещё раз.",
            life: 5000,
        });
    }
}
function apiError(event: Event) {
    const { status, message } = (event as CustomEvent).detail;
    if (status === 401) {
        const wasAuthenticated = !!auth.user;
        auth.user = null;
        if (wasAuthenticated && route.path !== "/login")
            void router.push("/login");
    } else if (status === 419 || status === 429) {
        toast.add({
            severity: "warn",
            summary: "Запрос не выполнен",
            detail: message,
            life: 5000,
        });
    }
}
onMounted(() => window.addEventListener("api:error", apiError));
onUnmounted(() => window.removeEventListener("api:error", apiError));
</script>
<template>
    <Toast /><ConfirmDialog :style="{ width: '480px' }" />
    <div v-if="route.meta.public" class="public-layout"><RouterView /></div>
    <div v-else class="workspace">
        <a class="skip-link" href="#main-content">Перейти к содержимому</a>
        <aside class="sidebar"><WorkspaceNavigation @logout="logout" /></aside>
        <Drawer
            v-if="menu"
            v-model:visible="menu"
            header="Навигация"
            :pt="{ content: { class: 'drawer-navigation' } }"
        >
            <WorkspaceNavigation @navigate="menu = false" @logout="logout" />
        </Drawer>
        <main id="main-content" class="content" tabindex="-1">
            <div class="mobile-bar">
                <Button
                    icon="pi pi-bars"
                    text
                    aria-label="Открыть меню"
                    :aria-expanded="menu"
                    @click="menu = true"
                /><b>HRSquare</b>
            </div>
            <div class="page-content"><RouterView :key="route.path" /></div>
        </main>
    </div>
</template>
<style>
.drawer-navigation {
    display: flex;
    flex-direction: column;
    gap: var(--space-8);
}
.drawer-navigation nav {
    display: flex;
    flex-direction: column;
    gap: var(--space-2);
}
.drawer-navigation nav a {
    display: flex;
    gap: var(--space-3);
    padding: var(--space-3);
    border-radius: var(--radius-md);
    color: var(--text-secondary);
}
.drawer-navigation nav a.active {
    background: var(--primary-soft);
    color: var(--primary);
}
</style>
