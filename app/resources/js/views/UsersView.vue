<script setup lang="ts">
import { ref, reactive, onMounted, watch, onUnmounted } from "vue";
import Button from "primevue/button";
import InputText from "primevue/inputtext";
import Password from "primevue/password";
import Select from "primevue/select";
import ToggleSwitch from "primevue/toggleswitch";
import Dialog from "primevue/dialog";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import Tag from "primevue/tag";
import Paginator from "primevue/paginator";
import Message from "primevue/message";
import Skeleton from "primevue/skeleton";
import PageHeader from "../components/PageHeader.vue";
import EmptyState from "../components/EmptyState.vue";
import RequestState from "../components/RequestState.vue";
import { usersApi } from "../api/users";
import { fieldErrors, errorMessage } from "../api/http";
import { useToast } from "primevue/usetoast";
import { useConfirm } from "primevue/useconfirm";
import type { User, UserInput, FieldErrors } from "../types";
const users = ref<User[]>([]),
    total = ref(0),
    page = ref(1),
    search = ref(""),
    role = ref<string | null>(null),
    loading = ref(false),
    dialog = ref(false),
    editing = ref<User | null>(null),
    errors = ref<FieldErrors>({}),
    failure = ref(""),
    formFailure = ref(""),
    saving = ref(false),
    toast = useToast(),
    confirm = useConfirm();
const form = reactive<UserInput>({
    fullName: "",
    login: "",
    password: "",
    role: "MANAGER",
    isActive: true,
});
const roles = [
    { label: "Рекрутер", value: "RECRUITER" },
    { label: "Менеджер", value: "MANAGER" },
];
let timer: ReturnType<typeof setTimeout>;
let loadVersion = 0;
async function load() {
    const version = ++loadVersion;
    loading.value = true;
    failure.value = "";
    try {
        const data = await usersApi.list({
            search: search.value,
            role: role.value,
            page: page.value,
        });
        if (version !== loadVersion) return;
        users.value = data.data;
        total.value = data.meta.total;
    } catch (e) {
        if (version === loadVersion) failure.value = errorMessage(e);
    } finally {
        if (version === loadVersion) loading.value = false;
    }
}
onMounted(load);
watch([search, role], () => {
    clearTimeout(timer);
    timer = setTimeout(() => {
        page.value = 1;
        void load();
    }, 300);
});
onUnmounted(() => {
    clearTimeout(timer);
    loadVersion++;
});
function edit(user: User | null) {
    editing.value = user;
    Object.assign(
        form,
        {
            fullName: "",
            login: "",
            password: "",
            role: "MANAGER",
            isActive: true,
        },
        user ?? {},
        { password: "" },
    );
    errors.value = {};
    failure.value = "";
    formFailure.value = "";
    dialog.value = true;
}
async function save() {
    saving.value = true;
    errors.value = {};
    formFailure.value = "";
    try {
        await usersApi.save(form, editing.value?.id);
        dialog.value = false;
        await load();
        toast.add({
            severity: "success",
            summary: "Пользователь сохранён",
            life: 2500,
        });
    } catch (e) {
        errors.value = fieldErrors(e);
        formFailure.value = errorMessage(e);
    } finally {
        saving.value = false;
    }
}
function remove(user: User) {
    confirm.require({
        header: "Удалить пользователя?",
        message: `${user.fullName}. Если есть связи с кандидатами, вместо удаления отключите учётную запись.`,
        acceptProps: { severity: "danger" },
        acceptLabel: "Удалить",
        rejectLabel: "Отмена",
        rejectProps: { severity: "secondary" },
        accept: async () => {
            try {
                await usersApi.remove(user.id);
                await load();
            } catch (e) {
                toast.add({
                    severity: "error",
                    summary: "Удаление не выполнено",
                    detail:
                        Object.values(fieldErrors(e)).flat().join(" ") ||
                        errorMessage(e),
                    life: 5000,
                });
            }
        },
    });
}
</script>
<template>
    <PageHeader
        title="Пользователи"
        :count="total"
        description="Учётные записи и доступ к рабочему пространству."
        ><Button
            label="Добавить пользователя"
            icon="pi pi-plus"
            @click="edit(null)"
    /></PageHeader>
    <div class="panel filters filter-row">
        <InputText
            v-model="search"
            placeholder="Поиск по имени или логину"
            aria-label="Поиск пользователей"
        /><Select
            v-model="role"
            :options="roles"
            option-label="label"
            option-value="value"
            placeholder="Роль"
            aria-label="Роль пользователя"
            show-clear
        />
    </div>
    <RequestState v-if="failure" :message="failure" @retry="load" />
    <div v-else class="panel table-panel">
        <div
            v-if="loading"
            class="skeleton-list"
            role="status"
            aria-label="Загрузка пользователей"
        >
            <div v-for="n in 5" :key="n" class="skeleton-row">
                <Skeleton width="30%" height="24px" /><Skeleton
                    width="20%"
                /><Skeleton width="15%" />
            </div>
        </div>
        <DataTable
            v-else
            :value="users"
            :loading="loading"
            scrollable
            :table-style="{ minWidth: '700px' }"
            ><template #empty
                ><EmptyState
                    title="Пользователи не найдены"
                    description="Попробуйте изменить поисковый запрос или роль."
                    icon="pi pi-users"
                    ><Button
                        label="Сбросить фильтры"
                        severity="secondary"
                        @click="
                            search = '';
                            role = null;
                        " /></EmptyState></template
            ><Column field="fullName" header="ФИО" /><Column
                field="login"
                header="Логин" /><Column header="Роль"
                ><template #body="{ data }"
                    ><Tag
                        :value="
                            data.role === 'RECRUITER' ? 'Рекрутер' : 'Менеджер'
                        "
                        severity="secondary" /></template></Column
            ><Column header="Доступ"
                ><template #body="{ data }"
                    ><Tag
                        :value="data.isActive ? 'Активен' : 'Отключён'"
                        :severity="
                            data.isActive ? 'success' : 'secondary'
                        " /></template></Column
            ><Column header=""
                ><template #body="{ data }"
                    ><Button
                        icon="pi pi-pencil"
                        text
                        aria-label="Редактировать пользователя"
                        @click="edit(data)" /><Button
                        icon="pi pi-trash"
                        text
                        severity="danger"
                        aria-label="Удалить пользователя"
                        @click="remove(data)" /></template></Column></DataTable
        ><Paginator
            :rows="20"
            :first="(page - 1) * 20"
            :total-records="total"
            @page="
                page = $event.page + 1;
                load();
            "
        />
    </div>
    <Dialog
        v-model:visible="dialog"
        modal
        :header="editing ? 'Редактировать пользователя' : 'Новый пользователь'"
        :style="{ width: '540px' }"
        :breakpoints="{ '600px': '95vw' }"
        :closable="!saving"
        :close-on-escape="!saving"
        ><p class="dialog-description">
            Роль определяет доступ к кандидатам и управлению пользователями.
        </p>
        <form id="user-form" @submit.prevent="save" class="user-form">
            <Message v-if="formFailure" severity="error">{{
                formFailure
            }}</Message
            ><label class="field"
                >ФИО *<InputText
                    required
                    :disabled="saving"
                    v-model="form.fullName"
                    :invalid="!!errors.fullName"
                /><small class="error" v-for="e in errors.fullName">{{
                    e
                }}</small></label
            ><label class="field"
                >Логин *<InputText
                    required
                    :disabled="saving"
                    v-model="form.login"
                    autocomplete="off"
                    :invalid="!!errors.login"
                /><small class="error" v-for="e in errors.login">{{
                    e
                }}</small></label
            >
            <div class="field">
                <label for="user-password">{{
                    editing
                        ? "Новый пароль (оставьте пустым, чтобы сохранить)"
                        : "Пароль *"
                }}</label
                ><Password
                    v-model="form.password"
                    input-id="user-password"
                    toggle-mask
                    :feedback="false"
                    :disabled="saving"
                    :required="!editing"
                    autocomplete="new-password"
                    :invalid="!!errors.password"
                /><small class="error" v-for="e in errors.password">{{
                    e
                }}</small>
            </div>
            <div class="field">
                <label for="user-role">Роль *</label
                ><Select
                    input-id="user-role"
                    :disabled="saving"
                    v-model="form.role"
                    :options="roles"
                    option-label="label"
                    option-value="value"
                /><small class="error" v-for="e in errors.role">{{ e }}</small>
            </div>
            <label class="toggle-field" for="user-active"
                ><ToggleSwitch
                    input-id="user-active"
                    v-model="form.isActive"
                    :disabled="saving"
                />Активная учётная запись</label
            ><small class="error" v-for="e in errors.isActive">{{ e }}</small>
        </form>
        <template #footer>
            <div class="form-actions">
                <Button
                    label="Отмена"
                    severity="secondary"
                    text
                    :disabled="saving"
                    @click="dialog = false"
                /><Button
                    label="Сохранить"
                    type="submit"
                    form="user-form"
                    :loading="saving"
                />
            </div>
        </template>
    </Dialog>
</template>
