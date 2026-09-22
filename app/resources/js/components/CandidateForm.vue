<script setup lang="ts">
import { reactive, ref, watch } from "vue";
import Dialog from "primevue/dialog";
import InputText from "primevue/inputtext";
import Select from "primevue/select";
import Button from "primevue/button";
import Message from "primevue/message";
import UserMultiSelect from "./UserMultiSelect.vue";
import CitySelect from "./CitySelect.vue";
import { candidatesApi } from "../api/candidates";
import { fieldErrors, errorMessage } from "../api/http";
import { candidateStatuses } from "../config/presentation";
import { useAuth } from "../stores/auth";
import { useToast } from "primevue/usetoast";
import type { Candidate, CandidateInput, FieldErrors } from "../types";
const props = defineProps<{ visible: boolean; candidate?: Candidate | null }>(),
    emit = defineEmits<{
        "update:visible": [value: boolean];
        saved: [candidate: Candidate];
    }>();
const auth = useAuth();
const blank = (): CandidateInput => ({
    fullName: "",
    position: "",
    city: "",
    company: "",
    division: "",
    project: "",
    status: "ACTIVE",
    hiringManagerIds: auth.user?.role === "MANAGER" ? [auth.user.id] : [],
    recruiterIds: [],
});
const form = reactive<CandidateInput>(blank()),
    errors = ref<FieldErrors>({}),
    saving = ref(false),
    message = ref(""),
    toast = useToast();
const fields = [
    { key: "fullName", label: "ФИО *" },
    { key: "position", label: "Должность *" },
    { key: "city", label: "Город" },
    { key: "company", label: "Компания" },
    { key: "division", label: "Дивизион" },
    { key: "project", label: "Проект" },
] as const;

watch(
    () => props.visible,
    (visible) => {
        if (!visible) return;
        errors.value = {};
        message.value = "";
        Object.assign(
            form,
            blank(),
            props.candidate
                ? {
                      ...props.candidate,
                      hiringManagerIds: props.candidate.hiringManagers.map(
                          (u) => u.id,
                      ),
                      recruiterIds: props.candidate.recruiters.map((u) => u.id),
                  }
                : {},
        );
    },
);
async function save() {
    saving.value = true;
    errors.value = {};
    message.value = "";
    try {
        const candidate = await candidatesApi.save(form, props.candidate?.id);
        emit("saved", candidate);
        emit("update:visible", false);
        toast.add({
            severity: "success",
            summary: props.candidate
                ? "Изменения сохранены"
                : "Кандидат создан",
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
        :header="candidate ? 'Редактировать кандидата' : 'Новый кандидат'"
        :style="{ width: '720px' }"
        :breakpoints="{ '768px': '95vw' }"
        :closable="!saving"
        :close-on-escape="!saving"
        @update:visible="emit('update:visible', $event)"
    >
        <p class="dialog-description">
            Основные данные и участники найма. Поля со звёздочкой обязательны.
        </p>
        <form id="candidate-form" @submit.prevent="save">
            <Message v-if="message" severity="error">{{ message }}</Message>
            <fieldset
                v-for="(section, index) in [
                    {
                        title: 'Основная информация',
                        fields: fields.slice(0, 3),
                    },
                    { title: 'Организация', fields: fields.slice(3) },
                ]"
                :key="section.title"
                class="form-section"
            >
                <legend>{{ section.title }}</legend>
                <div class="form-grid">
                    <div
                        v-for="field in section.fields"
                        :key="field.key"
                        class="field"
                        :class="{ 'span-2': field.key === 'fullName' }"
                    >
                        <label :for="`candidate-${field.key}`">{{
                            field.label
                        }}</label>
                        <CitySelect
                            v-if="field.key === 'city'"
                            input-id="candidate-city"
                            v-model="form.city"
                            :disabled="saving"
                            :invalid="!!errors.city"
                            :describedby="
                                errors.city ? 'candidate-city-error' : undefined
                            "
                        />
                        <InputText
                            v-else
                            :id="`candidate-${field.key}`"
                            v-model="form[field.key]"
                            :invalid="!!errors[field.key]"
                            :required="
                                field.key === 'fullName' ||
                                field.key === 'position'
                            "
                            :autofocus="field.key === 'fullName'"
                            :disabled="saving"
                            :aria-describedby="
                                errors[field.key]
                                    ? `candidate-${field.key}-error`
                                    : undefined
                            "
                        />
                        <small
                            v-if="errors[field.key]"
                            :id="`candidate-${field.key}-error`"
                            class="error"
                            >{{ errors[field.key].join(" ") }}</small
                        >
                    </div>
                </div>
            </fieldset>
            <fieldset class="form-section">
                <legend>Участники найма</legend>
                <div class="form-grid">
                    <div class="field">
                        <label for="candidate-managers">Менеджеры *</label
                        ><UserMultiSelect
                            input-id="candidate-managers"
                            v-model="form.hiringManagerIds"
                            role="managers"
                            :selected-users="
                                candidate?.hiringManagers ??
                                (auth.user?.role === 'MANAGER'
                                    ? [auth.user]
                                    : [])
                            "
                            :disabled="saving"
                            :invalid="
                                Object.keys(errors).some((k) =>
                                    k.startsWith('hiringManagerIds'),
                                )
                            "
                        /><small
                            >Один или несколько нанимающих менеджеров.</small
                        ><small
                            v-for="(messages, key) in errors"
                            :key="key"
                            v-show="key.startsWith('hiringManagerIds')"
                            class="error"
                            >{{ messages.join(" ") }}</small
                        >
                    </div>
                    <div class="field">
                        <label for="candidate-recruiters">Рекрутеры</label
                        ><UserMultiSelect
                            input-id="candidate-recruiters"
                            v-model="form.recruiterIds"
                            role="recruiters"
                            :selected-users="candidate?.recruiters"
                            :disabled="saving"
                            :invalid="
                                Object.keys(errors).some((k) =>
                                    k.startsWith('recruiterIds'),
                                )
                            "
                        /><small>Сотрудники, сопровождающие кандидата.</small
                        ><small
                            v-for="(messages, key) in errors"
                            :key="key"
                            v-show="key.startsWith('recruiterIds')"
                            class="error"
                            >{{ messages.join(" ") }}</small
                        >
                    </div>
                </div>
            </fieldset>
            <fieldset class="form-section">
                <legend>Статус</legend>
                <div class="field">
                    <label for="candidate-status">Статус кандидата</label
                    ><Select
                        input-id="candidate-status"
                        v-model="form.status"
                        :options="candidateStatuses"
                        option-label="label"
                        option-value="value"
                        :disabled="saving"
                        :invalid="!!errors.status"
                    /><small v-if="errors.status" class="error">{{
                        errors.status.join(" ")
                    }}</small>
                </div>
            </fieldset>
        </form>
        <template #footer>
            <div class="form-actions">
                <Button
                    label="Отмена"
                    severity="secondary"
                    :disabled="saving"
                    @click="emit('update:visible', false)"
                /><Button
                    type="submit"
                    form="candidate-form"
                    :label="
                        candidate ? 'Сохранить изменения' : 'Добавить кандидата'
                    "
                    :loading="saving"
                />
            </div>
        </template>
    </Dialog>
</template>
