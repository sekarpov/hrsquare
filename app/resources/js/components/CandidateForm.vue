<script setup lang="ts">
import { reactive, ref, watch } from "vue";
import Dialog from "primevue/dialog";
import InputText from "primevue/inputtext";
import Select from "primevue/select";
import Button from "primevue/button";
import Message from "primevue/message";
import UserMultiSelect from "./UserMultiSelect.vue";
import { candidatesApi } from "../api/candidates";
import { fieldErrors, errorMessage } from "../api/http";
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
const statuses = [
    { label: "Активный", value: "ACTIVE" },
    { label: "Нанят", value: "HIRED" },
    { label: "Отклонён", value: "REJECTED" },
];
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
    try {
        const candidate = await candidatesApi.save(form, props.candidate?.id);
        emit("saved", candidate);
        emit("update:visible", false);
        toast.add({
            severity: "success",
            summary: "Кандидат сохранён",
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
        @update:visible="emit('update:visible', $event)"
        ><form @submit.prevent="save" class="form-grid">
            <Message v-if="message" severity="error" class="span-2">{{
                message
            }}</Message
            ><label v-for="field in fields" :key="field.key" class="field"
                >{{ field.label
                }}<InputText
                    v-model="form[field.key]"
                    :invalid="!!errors[field.key]"
                    :autofocus="field.key === 'fullName'"
                /><small class="error" v-for="error in errors[field.key]">{{
                    error
                }}</small></label
            ><label class="field"
                >Статус<Select
                    v-model="form.status"
                    :options="statuses"
                    option-label="label"
                    option-value="value"
            /></label>
            <div />
            <label class="field span-2"
                >Менеджеры *<UserMultiSelect
                    v-model="form.hiringManagerIds"
                    role="managers"
                    :selected-users="
                        candidate?.hiringManagers ??
                        (auth.user?.role === 'MANAGER' ? [auth.user] : [])
                    "
                    :invalid="
                        Object.keys(errors).some((k) =>
                            k.startsWith('hiringManagerIds'),
                        )
                    "
                /><small class="error" v-for="(messages, key) in errors"
                    ><template v-if="key.startsWith('hiringManagerIds')">{{
                        messages.join(" ")
                    }}</template></small
                ></label
            ><label class="field span-2"
                >Рекрутеры<UserMultiSelect
                    v-model="form.recruiterIds"
                    role="recruiters"
                    :selected-users="candidate?.recruiters"
                    :invalid="
                        Object.keys(errors).some((k) =>
                            k.startsWith('recruiterIds'),
                        )
                    "
                /><small class="error" v-for="(messages, key) in errors"
                    ><template v-if="key.startsWith('recruiterIds')">{{
                        messages.join(" ")
                    }}</template></small
                ></label
            >
            <div class="form-actions span-2">
                <Button
                    label="Отмена"
                    severity="secondary"
                    text
                    @click="emit('update:visible', false)"
                /><Button type="submit" label="Сохранить" :loading="saving" />
            </div></form
    ></Dialog>
</template>
