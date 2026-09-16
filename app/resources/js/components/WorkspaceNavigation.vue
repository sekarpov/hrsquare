<script setup lang="ts">
import { useRoute } from "vue-router";
import Button from "primevue/button";
import { useAuth } from "../stores/auth";
const auth = useAuth(),
    route = useRoute();
defineEmits<{ navigate: []; logout: [] }>();
</script>
<template>
    <div>
        <RouterLink to="/candidates" class="brand" @click="$emit('navigate')"
            >HR<span>Square</span><span class="brand-dot">.</span></RouterLink
        >
        <small class="workspace-label">Оценка кандидатов</small>
    </div>
    <nav aria-label="Основная навигация">
        <RouterLink
            to="/candidates"
            :class="{
                active:
                    route.path.startsWith('/candidates') ||
                    route.path.startsWith('/assessments'),
            }"
            @click="$emit('navigate')"
            ><i class="pi pi-users" aria-hidden="true" />Кандидаты</RouterLink
        >
        <RouterLink
            v-if="auth.isRecruiter"
            to="/users"
            :class="{ active: route.path === '/users' }"
            @click="$emit('navigate')"
            ><i
                class="pi pi-user-edit"
                aria-hidden="true"
            />Пользователи</RouterLink
        >
    </nav>
    <div class="sidebar-bottom">
        <div class="user-avatar" aria-hidden="true">
            {{ auth.user?.fullName.charAt(0) }}
        </div>
        <div class="sidebar-user">
            <strong>{{ auth.user?.fullName }}</strong
            ><small>{{ auth.isRecruiter ? "Рекрутер" : "Менеджер" }}</small>
        </div>
        <Button
            icon="pi pi-sign-out"
            severity="secondary"
            text
            aria-label="Выйти"
            v-tooltip="'Выйти'"
            @click="$emit('logout')"
        />
    </div>
</template>
