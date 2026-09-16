<script setup lang="ts">
import { ref, reactive, onMounted, onUnmounted, watch } from "vue";
import { useRouter } from "vue-router";
import Button from "primevue/button";
import InputText from "primevue/inputtext";
import Select from "primevue/select";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import Tag from "primevue/tag";
import Paginator from "primevue/paginator";
import Skeleton from "primevue/skeleton";
import Message from "primevue/message";
import CandidateForm from "../components/CandidateForm.vue";
import UserMultiSelect from "../components/UserMultiSelect.vue";
import { usersApi } from "../api/users";
import { candidatesApi } from "../api/candidates";
import { errorMessage, fieldErrors } from "../api/http";
import { useAuth } from "../stores/auth";
import { useConfirm } from "primevue/useconfirm";
import { useToast } from "primevue/usetoast";
import type { Candidate, CandidateFilters, User } from "../types";
const auth = useAuth(),
    router = useRouter(),
    confirm = useConfirm(),
    toast = useToast();
const candidates = ref<Candidate[]>([]),
    loading = ref(true),
    failure = ref(""),
    total = ref(0),
    page = ref(1),
    perPage = ref(20),
    more = ref(false),
    dialog = ref(false),
    editing = ref<Candidate | null>(null);
const filters = reactive<CandidateFilters>({
    search: "",
    status: null,
    city: "",
    company: "",
    resultLevel: null,
    potentialLevel: null,
    nineBoxCell: null,
    position: "",
    division: "",
    project: "",
    managerId: null,
    recruiterId: null,
    sort: "createdAt",
    direction: "desc",
});
const managers = ref<User[]>([]),
    recruiters = ref<User[]>([]);
let timer: ReturnType<typeof setTimeout>;
let version = 0;
const levels = ["LOW", "MEDIUM", "HIGH"],
    cells = ["M1", "S1", "B1", "M2", "S2", "B2", "M3", "S3", "B3"],
    statuses = [
        { label: "Активный", value: "ACTIVE" },
        { label: "Нанят", value: "HIRED" },
        { label: "Отклонён", value: "REJECTED" },
    ];
const statusLabel = (s: string) =>
    statuses.find((x) => x.value === s)?.label ?? s;
const date = (s: string | null | undefined) =>
    s ? new Date(s).toLocaleDateString("ru-RU") : "—";
async function load() {
    const request = ++version;
    loading.value = true;
    failure.value = "";
    try {
        const data = await candidatesApi.list({
            ...filters,
            page: page.value,
            perPage: perPage.value,
        });
        if (request === version) {
            candidates.value = data.data;
            total.value = data.meta.total;
        }
    } catch (e) {
        if (request === version) failure.value = errorMessage(e);
    } finally {
        if (request === version) loading.value = false;
    }
}
watch(
    filters,
    () => {
        clearTimeout(timer);
        timer = setTimeout(() => {
            page.value = 1;
            void load();
        }, 300);
    },
    { deep: true },
);
onMounted(() => {
    void load();
    void usersApi
        .options("managers")
        .then((x) => (managers.value = x))
        .catch(() => {});
    void usersApi
        .options("recruiters")
        .then((x) => (recruiters.value = x))
        .catch(() => {});
});
onUnmounted(() => clearTimeout(timer));
function edit(c: Candidate | null) {
    editing.value = c;
    dialog.value = true;
}
function remove(c: Candidate) {
    confirm.require({
        header: "Удалить кандидата?",
        message: `${c.fullName}. Кандидатов с историей оценок можно только перевести в статус «Отклонён».`,
        icon: "pi pi-exclamation-triangle",
        acceptLabel: "Удалить",
        rejectLabel: "Отмена",
        accept: async () => {
            try {
                await candidatesApi.remove(c.id);
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
function paginate(event: { page: number; rows: number }) {
    page.value = event.page + 1;
    perPage.value = event.rows;
    void load();
}
function sort(event: {
    sortField?: string | ((item: unknown) => string);
    sortOrder?: number | null;
}) {
    if (typeof event.sortField === "string") {
        filters.sort = event.sortField;
        filters.direction = event.sortOrder === 1 ? "asc" : "desc";
    }
}
function reset() {
    for (const key of Object.keys(filters))
        if (!["sort", "direction"].includes(key)) filters[key] = null;
}
</script>
<template>
    <div class="page-header">
        <div>
            <div class="eyebrow">РАБОЧЕЕ ПРОСТРАНСТВО</div>
            <h1>
                Кандидаты <span class="count">{{ total }}</span>
            </h1>
            <p>
                {{
                    auth.isRecruiter
                        ? "Все кандидаты и результаты оценки в одном месте."
                        : "Кандидаты, назначенные вам для оценки."
                }}
            </p>
        </div>
        <Button
            label="Добавить кандидата"
            icon="pi pi-plus"
            @click="edit(null)"
        />
    </div>
    <section class="panel filters">
        <div class="filter-row">
            <div class="search-field">
                <i class="pi pi-search" /><InputText
                    v-model="filters.search"
                    placeholder="Поиск по имени и данным кандидата"
                    aria-label="Поиск кандидатов"
                />
            </div>
            <Select
                v-model="filters.status"
                :options="statuses"
                option-label="label"
                option-value="value"
                placeholder="Статус"
                show-clear
            /><InputText
                v-model="filters.city"
                placeholder="Город"
                aria-label="Город"
            /><InputText
                v-model="filters.company"
                placeholder="Компания"
                aria-label="Компания"
            />
        </div>
        <div class="filter-row secondary-filters">
            <Select
                v-model="filters.resultLevel"
                :options="levels"
                placeholder="RESULT"
                show-clear
            /><Select
                v-model="filters.potentialLevel"
                :options="levels"
                placeholder="POTENTIAL"
                show-clear
            /><Select
                v-model="filters.nineBoxCell"
                :options="cells"
                placeholder="9-Box"
                show-clear
            /><Button
                label="Ещё фильтры"
                icon="pi pi-sliders-h"
                severity="secondary"
                text
                @click="more = !more"
            /><Button
                label="Сбросить"
                severity="secondary"
                text
                @click="reset"
            />
        </div>
        <div v-if="more" class="filter-row advanced-filters">
            <InputText
                v-model="filters.position"
                placeholder="Должность"
            /><InputText
                v-model="filters.division"
                placeholder="Дивизион"
            /><InputText
                v-model="filters.project"
                placeholder="Проект"
            /><Select
                v-model="filters.managerId"
                :options="managers"
                option-label="fullName"
                option-value="id"
                filter
                placeholder="Менеджер"
                show-clear
            /><Select
                v-model="filters.recruiterId"
                :options="recruiters"
                option-label="fullName"
                option-value="id"
                filter
                placeholder="Рекрутер"
                show-clear
            />
        </div>
    </section>
    <Message v-if="failure" severity="error"
        >{{ failure }} <Button label="Повторить" text @click="load"
    /></Message>
    <section class="panel table-panel">
        <div v-if="loading" class="skeleton-list">
            <div v-for="n in 7" :key="n" class="skeleton-row">
                <Skeleton width="24%" height="24px" /><Skeleton
                    width="20%"
                /><Skeleton width="15%" /><Skeleton width="10%" />
            </div>
        </div>
        <DataTable
            v-else
            :value="candidates"
            lazy
            scrollable
            :table-style="{ minWidth: '1700px' }"
            data-key="id"
            @sort="sort"
            ><template #empty
                ><div class="empty-state">
                    <i class="pi pi-users" />
                    <h3>Кандидаты не найдены</h3>
                    <p>
                        Измените фильтры{{
                            auth.isRecruiter
                                ? " или добавьте первого кандидата"
                                : ""
                        }}.
                    </p>
                    <Button
                        label="Сбросить фильтры"
                        severity="secondary"
                        @click="reset"
                    /></div></template
            ><Column field="fullName" header="ФИО" sortable frozen
                ><template #body="{ data }"
                    ><RouterLink
                        :to="`/candidates/${data.id}`"
                        class="candidate-name"
                        >{{ data.fullName }}</RouterLink
                    ></template
                ></Column
            ><Column field="position" header="Должность" sortable /><Column
                field="city"
                header="Город"
                sortable /><Column
                field="company"
                header="Компания"
                sortable /><Column field="division" header="Дивизион" /><Column
                field="project"
                header="Проект" /><Column header="Менеджеры"
                ><template #body="{ data }"
                    ><div class="people-chips">
                        <span
                            v-for="u in data.hiringManagers"
                            :key="u.id"
                            class="chip"
                            v-tooltip="u.fullName"
                            >{{ u.fullName }}</span
                        >
                    </div></template
                ></Column
            ><Column header="Рекрутеры"
                ><template #body="{ data }"
                    ><div class="people-chips">
                        <span
                            v-for="u in data.recruiters"
                            :key="u.id"
                            class="chip"
                            >{{ u.fullName }}</span
                        >
                    </div></template
                ></Column
            ><Column field="status" header="Статус"
                ><template #body="{ data }"
                    ><Tag
                        :value="statusLabel(data.status)"
                        :severity="
                            data.status === 'HIRED'
                                ? 'success'
                                : data.status === 'REJECTED'
                                  ? 'secondary'
                                  : 'info'
                        " /></template></Column
            ><Column header="RESULT" frozen align-frozen="right"
                ><template #body="{ data }"
                    ><span
                        :class="`level level-${data.currentAssessment?.resultLevel}`"
                        >{{ data.currentAssessment?.resultLevel ?? "—" }}</span
                    ></template
                ></Column
            ><Column header="POTENTIAL" frozen align-frozen="right"
                ><template #body="{ data }"
                    ><span
                        :class="`level level-${data.currentAssessment?.potentialLevel}`"
                        >{{
                            data.currentAssessment?.potentialLevel ?? "—"
                        }}</span
                    ></template
                ></Column
            ><Column header="9-Box" frozen align-frozen="right"
                ><template #body="{ data }"
                    ><span
                        class="box-badge"
                        :class="`box-${data.currentAssessment?.nineBoxCell?.[0]}`"
                        >{{ data.currentAssessment?.nineBoxCell ?? "—" }}</span
                    ></template
                ></Column
            ><Column header="Последняя оценка"
                ><template #body="{ data }">{{
                    date(data.currentAssessment?.completedAt)
                }}</template></Column
            ><Column header="" frozen align-frozen="right"
                ><template #body="{ data }"
                    ><div class="row-actions">
                        <Button
                            icon="pi pi-arrow-up-right"
                            text
                            rounded
                            aria-label="Открыть кандидата"
                            @click="router.push(`/candidates/${data.id}`)"
                        /><Button
                            v-if="auth.isRecruiter"
                            icon="pi pi-pencil"
                            text
                            rounded
                            aria-label="Редактировать"
                            @click="edit(data)"
                        /><Button
                            v-if="auth.isRecruiter"
                            icon="pi pi-trash"
                            severity="secondary"
                            text
                            rounded
                            aria-label="Удалить"
                            @click="remove(data)"
                        /></div></template></Column></DataTable
        ><Paginator
            :first="(page - 1) * perPage"
            :rows="perPage"
            :total-records="total"
            :rows-per-page-options="[10, 20, 50]"
            @page="paginate"
        />
    </section>
    <CandidateForm
        v-model:visible="dialog"
        :candidate="editing"
        @saved="load"
    />
</template>
