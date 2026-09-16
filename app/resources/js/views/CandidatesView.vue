<script setup lang="ts">
import { computed, ref, reactive, onMounted, onUnmounted, watch } from "vue";
import { useRouter } from "vue-router";
import Button from "primevue/button";
import InputText from "primevue/inputtext";
import Select from "primevue/select";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import Paginator from "primevue/paginator";
import Skeleton from "primevue/skeleton";
import CandidateForm from "../components/CandidateForm.vue";
import PageHeader from "../components/PageHeader.vue";
import EmptyState from "../components/EmptyState.vue";
import CandidateStatus from "../components/CandidateStatus.vue";
import RequestState from "../components/RequestState.vue";
import { usersApi } from "../api/users";
import { candidatesApi } from "../api/candidates";
import { errorMessage, fieldErrors } from "../api/http";
import { candidateStatuses } from "../config/presentation";
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
    statuses = candidateStatuses;
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
        acceptProps: { severity: "danger" },
        acceptLabel: "Удалить",
        rejectLabel: "Отмена",
        rejectProps: { severity: "secondary" },
        accept: async () => {
            try {
                await candidatesApi.remove(c.id);
                await load();
                toast.add({
                    severity: "success",
                    summary: "Кандидат удалён",
                    life: 2500,
                });
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
const filterLabels: Record<string, string> = {
    search: "Поиск",
    status: "Статус",
    city: "Город",
    company: "Компания",
    resultLevel: "RESULT",
    potentialLevel: "POTENTIAL",
    nineBoxCell: "9-Box",
    position: "Должность",
    division: "Дивизион",
    project: "Проект",
    managerId: "Менеджер",
    recruiterId: "Рекрутер",
};
const activeFilters = computed(() =>
    Object.entries(filterLabels)
        .filter(([key]) => filters[key] != null && filters[key] !== "")
        .map(([key, label]) => ({
            key,
            label,
            value:
                key === "status"
                    ? statusLabel(String(filters[key]))
                    : key === "managerId"
                      ? (managers.value.find((u) => u.id === filters[key])
                            ?.fullName ?? filters[key])
                      : key === "recruiterId"
                        ? (recruiters.value.find((u) => u.id === filters[key])
                              ?.fullName ?? filters[key])
                        : filters[key],
        })),
);
const advancedCount = computed(
    () =>
        activeFilters.value.filter((f) =>
            [
                "city",
                "company",
                "division",
                "project",
                "position",
                "managerId",
                "recruiterId",
            ].includes(f.key),
        ).length,
);
</script>
<template>
    <PageHeader
        title="Кандидаты"
        :count="total"
        :description="
            auth.isRecruiter
                ? 'Кандидаты и результаты интервью в одном пространстве.'
                : 'Кандидаты, назначенные вам для оценки.'
        "
    >
        <Button
            label="Добавить кандидата"
            icon="pi pi-plus"
            @click="edit(null)"
        />
    </PageHeader>
    <section class="panel filters" aria-label="Фильтры кандидатов">
        <div class="filter-row">
            <div class="search-field">
                <i class="pi pi-search" aria-hidden="true" /><InputText
                    v-model="filters.search"
                    placeholder="Поиск по ФИО кандидата..."
                    aria-label="Поиск кандидатов"
                />
            </div>
        </div>
        <div class="filter-row primary-filters">
            <Select
                v-model="filters.status"
                :options="statuses"
                option-label="label"
                option-value="value"
                placeholder="Статус"
                aria-label="Статус"
                show-clear
            />
            <Select
                v-model="filters.resultLevel"
                :options="levels"
                placeholder="RESULT"
                aria-label="Уровень RESULT"
                show-clear
            />
            <Select
                v-model="filters.potentialLevel"
                :options="levels"
                placeholder="POTENTIAL"
                aria-label="Уровень POTENTIAL"
                show-clear
            />
            <Select
                v-model="filters.nineBoxCell"
                :options="cells"
                placeholder="9-Box"
                aria-label="Ячейка 9-Box"
                show-clear
            />
            <Button
                :label="`Ещё фильтры${advancedCount ? ' (' + advancedCount + ')' : ''}`"
                icon="pi pi-sliders-h"
                severity="secondary"
                text
                :aria-expanded="more"
                aria-controls="advanced-filters"
                @click="more = !more"
            />
        </div>
        <div v-if="more" id="advanced-filters" class="advanced-filters">
            <label
                v-for="field in [
                    { key: 'city', label: 'Город' },
                    { key: 'company', label: 'Компания' },
                    { key: 'division', label: 'Дивизион' },
                    { key: 'project', label: 'Проект' },
                    { key: 'position', label: 'Должность' },
                ] as const"
                :key="field.key"
                class="field"
                :for="`filter-${field.key}`"
                >{{ field.label
                }}<InputText
                    :id="`filter-${field.key}`"
                    v-model="filters[field.key]"
            /></label>
            <div class="field">
                <label for="filter-manager">Менеджер</label
                ><Select
                    input-id="filter-manager"
                    v-model="filters.managerId"
                    :options="managers"
                    option-label="fullName"
                    option-value="id"
                    filter
                    show-clear
                    placeholder="Все менеджеры"
                />
            </div>
            <div class="field">
                <label for="filter-recruiter">Рекрутер</label
                ><Select
                    input-id="filter-recruiter"
                    v-model="filters.recruiterId"
                    :options="recruiters"
                    option-label="fullName"
                    option-value="id"
                    filter
                    show-clear
                    placeholder="Все рекрутеры"
                />
            </div>
        </div>
        <div v-if="activeFilters.length" class="active-filters">
            <button
                v-for="filter in activeFilters"
                :key="filter.key"
                type="button"
                class="filter-chip"
                :aria-label="`Убрать фильтр ${filter.label}: ${filter.value}`"
                @click="filters[filter.key] = null"
            >
                {{ filter.label }}: {{ filter.value
                }}<i class="pi pi-times" aria-hidden="true" />
            </button>
            <Button
                label="Сбросить всё"
                severity="secondary"
                text
                size="small"
                @click="reset"
            />
        </div>
    </section>
    <div class="results-meta" aria-live="polite">
        <span>{{ loading ? "Ищем кандидатов…" : `Найдено: ${total}` }}</span
        ><span>Текущая оценка — последняя завершённая</span>
    </div>
    <RequestState v-if="failure" :message="failure" @retry="load" />
    <section v-else class="panel table-panel" :aria-busy="loading">
        <div
            v-if="loading"
            class="skeleton-list"
            role="status"
            aria-label="Загрузка кандидатов"
        >
            <div v-for="n in 6" :key="n" class="skeleton-row">
                <Skeleton width="24%" height="32px" /><Skeleton
                    width="20%"
                /><Skeleton width="15%" /><Skeleton width="10%" />
            </div>
        </div>
        <EmptyState
            v-else-if="!candidates.length"
            :title="
                activeFilters.length
                    ? 'Ничего не найдено'
                    : 'Кандидатов пока нет'
            "
            :description="
                activeFilters.length
                    ? 'Попробуйте изменить или сбросить фильтры.'
                    : 'Добавьте первого кандидата, чтобы начать работу с оценками.'
            "
            icon="pi pi-users"
        >
            <Button
                v-if="activeFilters.length"
                label="Сбросить фильтры"
                severity="secondary"
                @click="reset"
            /><Button
                v-else
                label="Добавить кандидата"
                icon="pi pi-plus"
                @click="edit(null)"
            />
        </EmptyState>
        <DataTable
            v-else
            :value="candidates"
            lazy
            scrollable
            :table-style="{ minWidth: '1260px' }"
            data-key="id"
            :sort-field="filters.sort"
            :sort-order="filters.direction === 'asc' ? 1 : -1"
            @sort="sort"
        >
            <Column field="fullName" header="Кандидат" sortable frozen
                ><template #body="{ data }"
                    ><div class="table-primary">
                        <RouterLink
                            :to="`/candidates/${data.id}`"
                            class="candidate-name"
                            >{{ data.fullName }}</RouterLink
                        ><span class="table-secondary">{{
                            data.position
                        }}</span>
                    </div></template
                ></Column
            >
            <Column field="company" header="Организация" sortable
                ><template #body="{ data }"
                    ><div class="table-stack">
                        <span>{{ data.company || "Компания не указана" }}</span
                        ><span class="table-secondary"
                            >Дивизион: {{ data.division || "—" }}</span
                        ><span class="table-secondary"
                            >Проект: {{ data.project || "—" }}</span
                        >
                    </div></template
                ></Column
            >
            <Column field="city" header="Город" sortable
                ><template #body="{ data }">{{
                    data.city || "—"
                }}</template></Column
            >
            <Column header="Участники найма"
                ><template #body="{ data }"
                    ><div class="table-stack">
                        <small class="muted">Менеджеры</small>
                        <div class="people-chips">
                            <span
                                v-for="u in data.hiringManagers"
                                :key="u.id"
                                class="chip"
                                >{{ u.fullName }}</span
                            ><span v-if="!data.hiringManagers.length">—</span>
                        </div>
                        <small class="muted">Рекрутеры</small>
                        <div class="people-chips">
                            <span
                                v-for="u in data.recruiters"
                                :key="u.id"
                                class="chip"
                                >{{ u.fullName }}</span
                            ><span v-if="!data.recruiters.length">—</span>
                        </div>
                    </div></template
                ></Column
            >
            <Column field="status" header="Статус"
                ><template #body="{ data }"
                    ><CandidateStatus :status="data.status" /></template
            ></Column>
            <Column header="Оценка"
                ><template #body="{ data }"
                    ><div class="table-stack">
                        <span class="level"
                            >RESULT ·
                            {{
                                data.currentAssessment?.resultLevel ?? "—"
                            }}</span
                        ><span class="level"
                            >POTENTIAL ·
                            {{
                                data.currentAssessment?.potentialLevel ?? "—"
                            }}</span
                        ><small class="muted">{{
                            date(data.currentAssessment?.completedAt)
                        }}</small>
                    </div></template
                ></Column
            >
            <Column header="9-Box"
                ><template #body="{ data }"
                    ><RouterLink
                        v-if="data.currentAssessment"
                        :to="`/candidates/${data.id}#current-assessment`"
                        class="box-badge"
                        :aria-label="`Текущая оценка ${data.fullName}: ${data.currentAssessment.nineBoxCell}`"
                        >{{ data.currentAssessment.nineBoxCell }}</RouterLink
                    ><span v-else class="muted">—</span></template
                ></Column
            >
            <Column header="Действия"
                ><template #body="{ data }"
                    ><div class="row-actions">
                        <Button
                            icon="pi pi-arrow-up-right"
                            text
                            severity="secondary"
                            aria-label="Открыть кандидата"
                            @click="router.push(`/candidates/${data.id}`)"
                        /><Button
                            v-if="auth.isRecruiter"
                            icon="pi pi-pencil"
                            text
                            severity="secondary"
                            aria-label="Редактировать кандидата"
                            @click="edit(data)"
                        /><Button
                            v-if="auth.isRecruiter"
                            icon="pi pi-trash"
                            text
                            severity="danger"
                            aria-label="Удалить кандидата"
                            @click="remove(data)"
                        /></div></template
            ></Column>
        </DataTable>
        <Paginator
            v-if="total > 0"
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
