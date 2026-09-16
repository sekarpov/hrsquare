<script setup lang="ts">
import { computed, reactive, ref } from "vue";
import { useRouter } from "vue-router";
import { useToast } from "primevue/usetoast";
import Password from "primevue/password";
import Button from "primevue/button";
import Message from "primevue/message";
import PageHeader from "../components/PageHeader.vue";
import { authApi } from "../api/auth";
import { errorMessage, fieldErrors } from "../api/http";
import { useAuth } from "../stores/auth";
import type { FieldErrors } from "../types";
const auth = useAuth(),
    router = useRouter(),
    toast = useToast();
const required = computed(() => !!auth.user?.mustChangePassword);
const form = reactive({
    currentPassword: "",
    password: "",
    passwordConfirmation: "",
});
const errors = ref<FieldErrors>({}),
    failure = ref(""),
    saving = ref(false);
async function save() {
    saving.value = true;
    errors.value = {};
    failure.value = "";
    const firstLogin = required.value;
    try {
        auth.user = await authApi.changePassword(form);
        Object.assign(form, {
            currentPassword: "",
            password: "",
            passwordConfirmation: "",
        });
        toast.add({
            severity: "success",
            summary: "Пароль изменён",
            life: 3500,
        });
        if (firstLogin) await router.push("/candidates");
    } catch (e) {
        errors.value = fieldErrors(e);
        failure.value = errorMessage(e);
    } finally {
        saving.value = false;
    }
}
</script>
<template>
    <PageHeader
        :title="required ? 'Задайте личный пароль' : 'Пароль и безопасность'"
        :description="`${auth.user?.fullName} · ${auth.user?.login}`"
    />
    <section class="panel password-settings">
        <Message v-if="required" severity="info"
            >Перед началом работы замените временный пароль на личный. После
            сохранения откроется список кандидатов.</Message
        >
        <p v-else class="muted">
            Для смены пароля укажите текущий. Другие сеансы вашей учётной записи
            завершатся.
        </p>
        <form @submit.prevent="save">
            <Message v-if="failure" severity="error">{{ failure }}</Message>
            <div v-if="!required" class="field">
                <label for="current-password">Текущий пароль *</label>
                <Password
                    input-id="current-password"
                    v-model="form.currentPassword"
                    autocomplete="current-password"
                    toggle-mask
                    :feedback="false"
                    :disabled="saving"
                    required
                    :invalid="!!errors.currentPassword"
                    :input-props="{
                        'aria-describedby': 'current-password-error',
                    }"
                />
                <small id="current-password-error" class="error">{{
                    errors.currentPassword?.join(" ")
                }}</small>
            </div>
            <div class="field">
                <label for="new-password">Новый пароль *</label>
                <Password
                    input-id="new-password"
                    v-model="form.password"
                    autocomplete="new-password"
                    toggle-mask
                    :feedback="false"
                    :disabled="saving"
                    required
                    :invalid="!!errors.password"
                    :input-props="{
                        minlength: 8,
                        maxlength: 255,
                        'aria-describedby':
                            'new-password-help new-password-error',
                    }"
                />
                <small id="new-password-help"
                    >Не менее 8 символов. Пароль должен отличаться от временного
                    и текущего.</small
                >
                <small id="new-password-error" class="error">{{
                    errors.password?.join(" ")
                }}</small>
            </div>
            <div class="field">
                <label for="confirm-password">Повторите новый пароль *</label>
                <Password
                    input-id="confirm-password"
                    v-model="form.passwordConfirmation"
                    autocomplete="new-password"
                    toggle-mask
                    :feedback="false"
                    :disabled="saving"
                    required
                    :invalid="
                        !!errors.passwordConfirmation || !!errors.password
                    "
                    :input-props="{
                        'aria-describedby': 'confirm-password-error',
                    }"
                />
                <small id="confirm-password-error" class="error">{{
                    errors.passwordConfirmation?.join(" ")
                }}</small>
            </div>
            <div class="form-actions">
                <Button
                    type="submit"
                    :label="
                        required ? 'Сохранить и продолжить' : 'Изменить пароль'
                    "
                    :loading="saving"
                    icon="pi pi-check"
                />
            </div>
        </form>
    </section>
</template>
<style scoped>
.password-settings {
    max-width: 560px;
    padding: var(--space-6);
}
.password-settings .field {
    margin: var(--space-5) 0;
}
</style>
