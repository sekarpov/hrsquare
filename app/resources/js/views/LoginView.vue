<script setup lang="ts">
import { ref } from "vue";
import { useRouter } from "vue-router";
import { useAuth } from "../stores/auth";
import { fieldErrors } from "../api/http";
import InputText from "primevue/inputtext";
import Password from "primevue/password";
import Button from "primevue/button";
import Message from "primevue/message";
const login = ref(""),
    password = ref(""),
    loading = ref(false),
    error = ref(""),
    auth = useAuth(),
    router = useRouter();
async function submit() {
    loading.value = true;
    error.value = "";
    try {
        await auth.login(login.value, password.value);
        await router.push("/candidates");
    } catch (e) {
        error.value =
            Object.values(fieldErrors(e)).flat().join(" ") ||
            "Не удалось войти. Проверьте соединение и попробуйте ещё раз.";
    } finally {
        loading.value = false;
    }
}
</script>
<template>
    <main class="login-page">
        <div class="login-card">
            <div class="brand">
                HR<span>Square</span><span class="brand-dot">.</span>
            </div>
            <h1>Оценка кандидатов</h1>
            <p>Войдите в рабочее пространство HR и менеджеров.</p>
            <form @submit.prevent="submit">
                <Message v-if="error" severity="error">{{ error }}</Message
                ><label class="field"
                    >Логин<InputText
                        v-model="login"
                        autocomplete="username"
                        :disabled="loading"
                        autofocus
                        required
                /></label>
                <div class="field">
                    <label for="password">Пароль</label
                    ><Password
                        v-model="password"
                        input-id="password"
                        :disabled="loading"
                        :feedback="false"
                        toggle-mask
                        autocomplete="current-password"
                        required
                    />
                </div>
                <Button
                    type="submit"
                    label="Войти"
                    icon="pi pi-arrow-right"
                    icon-pos="right"
                    :loading="loading"
                    class="login-submit"
                />
            </form>
            <small class="login-note"
                >Используйте учётную запись, выданную вашей компанией.</small
            >
        </div>
        <div class="login-footer">HRSquare · Оценка кандидатов</div>
    </main>
</template>
