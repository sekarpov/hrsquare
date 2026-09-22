<script setup lang="ts">
import { ref, watch } from "vue";
import Dialog from "primevue/dialog";
import InputText from "primevue/inputtext";
import Button from "primevue/button";
import Message from "primevue/message";
import { useToast } from "primevue/usetoast";
import { citiesApi } from "../api/cities";
import { errorMessage, fieldErrors } from "../api/http";
import type { City, FieldErrors } from "../types";

const props = defineProps<{ visible: boolean }>();
const emit = defineEmits<{
    "update:visible": [value: boolean];
    saved: [city: City];
}>();
const name = ref(""),
    saving = ref(false),
    message = ref(""),
    errors = ref<FieldErrors>({});
const toast = useToast();
watch(
    () => props.visible,
    (visible) => {
        if (!visible) return;
        name.value = "";
        message.value = "";
        errors.value = {};
    },
);
async function save() {
    saving.value = true;
    message.value = "";
    errors.value = {};
    try {
        const city = await citiesApi.create(name.value.trim());
        emit("saved", city);
        emit("update:visible", false);
        toast.add({
            severity: "success",
            summary: "Город добавлен",
            life: 2500,
        });
    } catch (error) {
        errors.value = fieldErrors(error);
        message.value = errorMessage(error);
    } finally {
        saving.value = false;
    }
}
</script>
<template>
    <Dialog
        :visible="visible"
        modal
        header="Добавить город"
        :style="{ width: '440px' }"
        :breakpoints="{ '768px': '95vw' }"
        :closable="!saving"
        :close-on-escape="!saving"
        @update:visible="emit('update:visible', $event)"
    >
        <p class="dialog-description">
            Новый город будет доступен всем пользователям при выборе города
            кандидата.
        </p>
        <form id="city-form" @submit.prevent="save">
            <Message v-if="message" severity="error">{{ message }}</Message>
            <div class="field">
                <label for="city-name">Название города *</label>
                <InputText
                    id="city-name"
                    v-model="name"
                    required
                    autofocus
                    maxlength="255"
                    :disabled="saving"
                    :invalid="!!errors.name"
                    :aria-describedby="
                        errors.name ? 'city-name-error' : undefined
                    "
                />
                <small v-if="errors.name" id="city-name-error" class="error">{{
                    errors.name.join(" ")
                }}</small>
            </div>
        </form>
        <template #footer>
            <Button
                label="Отмена"
                severity="secondary"
                :disabled="saving"
                @click="emit('update:visible', false)"
            />
            <Button
                type="submit"
                form="city-form"
                label="Добавить город"
                :loading="saving"
            />
        </template>
    </Dialog>
</template>
