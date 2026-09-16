<script setup lang="ts">
import { ref, onMounted } from "vue";
import { useRoute, useRouter } from "vue-router";
import { useAuth } from "../stores/auth";
import Button from "primevue/button";
import Tag from "primevue/tag";
import Skeleton from "primevue/skeleton";
import Message from "primevue/message";
import Paginator from "primevue/paginator";
import AssessmentSummary from "../components/AssessmentSummary.vue";
import CandidateForm from "../components/CandidateForm.vue";
import { candidatesApi } from "../api/candidates";
import { assessmentsApi } from "../api/assessments";
import { errorMessage } from "../api/http";
import type { Candidate, Assessment } from "../types";
const route = useRoute(),
    router = useRouter(),
    auth = useAuth(),
    candidate = ref<Candidate | null>(null),
    history = ref<Assessment[]>([]),
    total = ref(0),
    page = ref(1),
    loading = ref(true),
    creating = ref(false),
    failure = ref(""),
    editing = ref(false);
const date = (s: string | null) =>
    s
        ? new Date(s).toLocaleString("ru-RU", {
              dateStyle: "medium",
              timeStyle: "short",
          })
        : "Черновик";
async function loadHistory() {
    if (!candidate.value) return;
    const data = await assessmentsApi.list(candidate.value.id, page.value);
    history.value = data.data;
    total.value = data.meta.total;
}
async function load() {
    try {
        candidate.value = await candidatesApi.get(Number(route.params.id));
        await loadHistory();
    } catch (e) {
        failure.value = errorMessage(e);
    } finally {
        loading.value = false;
    }
}
onMounted(load);
async function create() {
    creating.value = true;
    try {
        const a = await assessmentsApi.create(candidate.value!.id);
        await router.push(`/assessments/${a.id}`);
    } catch (e) {
        failure.value = errorMessage(e);
    } finally {
        creating.value = false;
    }
}
</script>
<template>
    <div class="breadcrumbs">
        <RouterLink to="/candidates">Кандидаты</RouterLink
        ><i class="pi pi-angle-right" />Карточка кандидата
    </div>
    <Skeleton v-if="loading" height="260px" /><Message
        v-else-if="failure"
        severity="error"
        >{{ failure }}</Message
    ><template v-else-if="candidate"
        ><div class="page-header">
            <div>
                <h1>{{ candidate.fullName }}</h1>
                <p>
                    {{ candidate.position }}
                    <span class="dot-separator">·</span>
                    {{ candidate.city ?? "Город не указан" }}
                </p>
            </div>
            <div class="header-actions">
                <Button
                    v-if="auth.isRecruiter"
                    label="Редактировать"
                    icon="pi pi-pencil"
                    severity="secondary"
                    @click="editing = true"
                /><Button
                    label="Новая оценка"
                    icon="pi pi-plus"
                    :loading="creating"
                    @click="create"
                />
            </div>
        </div>
        <div class="detail-grid">
            <section class="panel candidate-profile">
                <h2>Информация</h2>
                <dl>
                    <template
                        v-for="field in [
                            { key: 'company', label: 'Компания' },
                            { key: 'division', label: 'Дивизион' },
                            { key: 'project', label: 'Проект' },
                        ] as const"
                        :key="field.key"
                        ><dt>{{ field.label }}</dt>
                        <dd>{{ candidate[field.key] || "—" }}</dd></template
                    >
                    <dt>Статус</dt>
                    <dd>
                        <Tag
                            :value="
                                {
                                    ACTIVE: 'Активный',
                                    HIRED: 'Нанят',
                                    REJECTED: 'Отклонён',
                                }[candidate.status]
                            "
                            :severity="
                                candidate.status === 'HIRED'
                                    ? 'success'
                                    : 'secondary'
                            "
                        />
                    </dd>
                </dl>
                <h3>Менеджеры</h3>
                <div
                    v-for="u in candidate.hiringManagers"
                    :key="u.id"
                    class="person"
                >
                    <span class="user-avatar small-avatar">{{
                        u.fullName.charAt(0)
                    }}</span
                    >{{ u.fullName }}
                </div>
                <h3>Рекрутеры</h3>
                <div
                    v-for="u in candidate.recruiters"
                    :key="u.id"
                    class="person"
                >
                    <span class="user-avatar small-avatar">{{
                        u.fullName.charAt(0)
                    }}</span
                    >{{ u.fullName }}
                </div>
                <p v-if="!candidate.recruiters.length" class="muted">
                    Не назначены
                </p>
            </section>
            <section class="panel current-assessment">
                <div class="section-header">
                    <h2>Текущая оценка</h2>
                    <Tag value="Последняя завершённая" severity="secondary" />
                </div>
                <AssessmentSummary :assessment="candidate.currentAssessment" />
                <div v-if="candidate.currentAssessment" class="current-caption">
                    {{ candidate.currentAssessment.evaluator.fullName }} ·
                    {{ date(candidate.currentAssessment.completedAt) }}
                    <RouterLink
                        :to="`/assessments/${candidate.currentAssessment.id}`"
                        >Посмотреть оценку →</RouterLink
                    >
                </div>
                <p v-else class="muted">
                    Завершённых оценок пока нет. Создайте первую оценку после
                    интервью.
                </p>
            </section>
        </div>
        <section class="panel history">
            <div class="section-header">
                <div>
                    <h2>История оценок</h2>
                    <p class="muted">
                        Каждое интервью сохраняется отдельно. Черновики не
                        меняют текущий 9-Box.
                    </p>
                </div>
                <span class="count">{{ total }}</span>
            </div>
            <div v-if="!history.length" class="empty-state">
                <i class="pi pi-clock" />
                <h3>История начнётся с первой оценки</h3>
            </div>
            <RouterLink
                v-for="a in history"
                :key="a.id"
                :to="`/assessments/${a.id}`"
                class="history-row"
                ><span class="user-avatar">{{
                    a.evaluator.fullName.charAt(0)
                }}</span>
                <div class="history-person">
                    <strong>{{ a.evaluator.fullName }}</strong
                    ><small
                        >{{ date(a.completedAt) }} · Оценка #{{ a.id }}</small
                    >
                </div>
                <Tag
                    :value="a.status === 'DRAFT' ? 'Черновик' : 'Завершена'"
                    :severity="
                        a.status === 'DRAFT' ? 'warn' : 'success'
                    " /><span
                    v-if="a.id === candidate.currentAssessment?.id"
                    class="chip"
                    >Текущая</span
                >
                <div class="history-dimensions">
                    <span
                        >RESULT
                        <b>{{ a.resultAverage?.toFixed(2) ?? "—" }}</b></span
                    ><span
                        >POTENTIAL
                        <b>{{ a.potentialAverage?.toFixed(2) ?? "—" }}</b></span
                    >
                </div>
                <span class="box-badge" :class="`box-${a.nineBoxCell?.[0]}`">{{
                    a.nineBoxCell ?? "—"
                }}</span
                ><i class="pi pi-angle-right" /></RouterLink
            ><Paginator
                v-if="total > 20"
                :first="(page - 1) * 20"
                :rows="20"
                :total-records="total"
                @page="
                    page = $event.page + 1;
                    loadHistory().catch(() => {});
                "
            />
        </section>
        <CandidateForm
            v-model:visible="editing"
            :candidate="candidate"
            @saved="candidate = $event"
    /></template>
</template>
