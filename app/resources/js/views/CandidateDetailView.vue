<script setup lang="ts">
import { ref, onMounted, nextTick } from "vue";
import { useRoute, useRouter } from "vue-router";
import { useAuth } from "../stores/auth";
import Button from "primevue/button";
import Tag from "primevue/tag";
import Skeleton from "primevue/skeleton";
import Message from "primevue/message";
import Paginator from "primevue/paginator";
import PageHeader from "../components/PageHeader.vue";
import EmptyState from "../components/EmptyState.vue";
import RequestState from "../components/RequestState.vue";
import CandidateStatus from "../components/CandidateStatus.vue";
import AssessmentSummary from "../components/AssessmentSummary.vue";
import CandidateForm from "../components/CandidateForm.vue";
import { nineBoxMetadata } from "../config/nineBox";
import { formatDate } from "../config/presentation";
import { candidatesApi } from "../api/candidates";
import { assessmentsApi } from "../api/assessments";
import { errorMessage, errorStatus } from "../api/http";
import type { Candidate, Assessment } from "../types";
const route = useRoute(),
    router = useRouter(),
    auth = useAuth();
const candidate = ref<Candidate | null>(null),
    history = ref<Assessment[]>([]),
    total = ref(0),
    page = ref(1),
    loading = ref(true),
    creating = ref(false),
    failure = ref(""),
    status = ref(0),
    historyFailure = ref(""),
    historyLoading = ref(false),
    actionFailure = ref(""),
    editing = ref(false);
let historyVersion = 0;
async function loadHistory() {
    if (!candidate.value) return;
    const version = ++historyVersion;
    historyLoading.value = true;
    historyFailure.value = "";
    try {
        const data = await assessmentsApi.list(candidate.value.id, page.value);
        if (version === historyVersion) {
            history.value = data.data;
            total.value = data.meta.total;
        }
    } catch (e) {
        if (version === historyVersion) historyFailure.value = errorMessage(e);
    } finally {
        if (version === historyVersion) historyLoading.value = false;
    }
}
async function load() {
    loading.value = true;
    failure.value = "";
    try {
        candidate.value = await candidatesApi.get(Number(route.params.id));
        void loadHistory();
    } catch (e) {
        failure.value = errorMessage(e);
        status.value = errorStatus(e);
    } finally {
        loading.value = false;
    }
    await nextTick();
    if (route.hash)
        document.getElementById(route.hash.slice(1))?.scrollIntoView();
}
onMounted(load);
async function create() {
    creating.value = true;
    actionFailure.value = "";
    try {
        const a = await assessmentsApi.create(candidate.value!.id);
        await router.push(`/assessments/${a.id}`);
    } catch (e) {
        actionFailure.value = errorMessage(e);
    } finally {
        creating.value = false;
    }
}
</script>
<template>
    <nav class="breadcrumbs" aria-label="Навигационная цепочка">
        <RouterLink to="/candidates">Кандидаты</RouterLink
        ><i class="pi pi-angle-right" aria-hidden="true" /><span
            >Карточка кандидата</span
        >
    </nav>
    <div v-if="loading" aria-label="Загрузка кандидата" role="status">
        <Skeleton height="70px" class="tw:mb-6" /><Skeleton
            height="180px"
            class="tw:mb-6"
        /><Skeleton height="400px" />
    </div>
    <RequestState
        v-else-if="failure"
        :message="
            status === 403
                ? 'У вас нет доступа к этому кандидату.'
                : status === 404
                  ? 'Кандидат не найден. Возможно, запись была удалена.'
                  : failure
        "
        :status="status"
        @retry="load"
    />
    <template v-else-if="candidate">
        <PageHeader
            :title="candidate.fullName"
            :description="`${candidate.position} · ${candidate.city || 'Город не указан'}`"
        >
            <template #badge
                ><CandidateStatus :status="candidate.status"
            /></template>
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
        </PageHeader>
        <Message v-if="actionFailure" severity="error">{{
            actionFailure
        }}</Message>
        <nav class="detail-nav" aria-label="Разделы кандидата">
            <a href="#overview">Обзор</a
            ><a href="#current-assessment">Текущая оценка</a
            ><a href="#history"
                >История оценок <span class="count">{{ total }}</span></a
            >
        </nav>
        <section
            id="overview"
            class="panel candidate-overview"
            aria-label="Информация о кандидате"
        >
            <dl class="profile-grid">
                <div
                    v-for="field in [
                        { key: 'company', label: 'Компания' },
                        { key: 'division', label: 'Дивизион' },
                        { key: 'project', label: 'Проект' },
                    ] as const"
                    :key="field.key"
                >
                    <dt>{{ field.label }}</dt>
                    <dd>{{ candidate[field.key] || "Не указано" }}</dd>
                </div>
                <div>
                    <dt>Менеджеры</dt>
                    <dd class="people-chips">
                        <span
                            v-for="u in candidate.hiringManagers"
                            :key="u.id"
                            class="chip"
                            >{{ u.fullName }}</span
                        ><span v-if="!candidate.hiringManagers.length"
                            >Не назначены</span
                        >
                    </dd>
                </div>
                <div>
                    <dt>Рекрутеры</dt>
                    <dd class="people-chips">
                        <span
                            v-for="u in candidate.recruiters"
                            :key="u.id"
                            class="chip"
                            >{{ u.fullName }}</span
                        ><span v-if="!candidate.recruiters.length"
                            >Не назначены</span
                        >
                    </dd>
                </div>
                <div>
                    <dt>Добавлен в HRSquare</dt>
                    <dd>{{ formatDate(candidate.createdAt) }}</dd>
                </div>
            </dl>
        </section>
        <section id="current-assessment" class="panel current-assessment">
            <div class="section-header">
                <div>
                    <h2>Текущая оценка</h2>
                    <p>Последняя завершённая оценка кандидата</p>
                </div>
                <RouterLink
                    v-if="candidate.currentAssessment"
                    :to="`/assessments/${candidate.currentAssessment.id}`"
                    class="text-link"
                    >Открыть оценку →</RouterLink
                >
            </div>
            <template v-if="candidate.currentAssessment"
                ><AssessmentSummary :assessment="candidate.currentAssessment" />
                <div class="current-caption">
                    <span
                        >Оценил:
                        {{
                            candidate.currentAssessment.evaluator.fullName
                        }}</span
                    ><span>{{
                        formatDate(candidate.currentAssessment.completedAt)
                    }}</span>
                </div></template
            >
            <EmptyState
                v-else
                title="Завершённых оценок пока нет"
                description="Создайте оценку после интервью. Черновики доступны в истории и не меняют текущий 9-Box."
                icon="pi pi-th-large"
                ><Button
                    label="Начать оценку"
                    icon="pi pi-plus"
                    :loading="creating"
                    @click="create"
            /></EmptyState>
        </section>
        <section id="history" class="panel history">
            <div class="section-header">
                <div>
                    <h2>История оценок</h2>
                    <p>Результаты интервью и незавершённые черновики.</p>
                </div>
                <span class="count">{{ total }}</span>
            </div>
            <div
                v-if="historyLoading"
                role="status"
                aria-label="Загрузка истории"
            >
                <Skeleton
                    v-for="n in 3"
                    :key="n"
                    height="80px"
                    class="tw:mb-3"
                />
            </div>
            <Message v-else-if="historyFailure" severity="error"
                >{{ historyFailure
                }}<Button label="Повторить" text @click="loadHistory"
            /></Message>
            <EmptyState
                v-else-if="!history.length"
                title="История начнётся с первой оценки"
                description="Каждое интервью сохраняется отдельно, чтобы видеть изменения во времени."
                icon="pi pi-clock"
            />
            <template v-else
                ><RouterLink
                    v-for="a in history"
                    :key="a.id"
                    :to="`/assessments/${a.id}`"
                    class="history-row"
                >
                    <span class="box-badge">{{ a.nineBoxCell ?? "—" }}</span>
                    <div class="history-body">
                        <div class="history-heading">
                            <strong>{{
                                a.nineBoxCell
                                    ? nineBoxMetadata[a.nineBoxCell].title
                                    : "Оценка не завершена"
                            }}</strong
                            ><Tag
                                :value="
                                    a.status === 'DRAFT'
                                        ? 'Черновик'
                                        : 'Завершена'
                                "
                                :severity="
                                    a.status === 'DRAFT' ? 'warn' : 'secondary'
                                "
                            /><span
                                v-if="a.id === candidate.currentAssessment?.id"
                                class="chip"
                                >Текущая</span
                            >
                        </div>
                        <p>
                            {{ a.evaluator.fullName }} ·
                            {{ formatDate(a.completedAt ?? a.createdAt) }} · №{{
                                a.id
                            }}
                        </p>
                        <div class="history-dimensions">
                            <span
                                >RESULT
                                <b>{{ a.resultAverage?.toFixed(2) ?? "—" }}</b>
                                / 4 · {{ a.resultLevel ?? "Нет оценки" }}</span
                            ><span
                                >POTENTIAL
                                <b>{{
                                    a.potentialAverage?.toFixed(2) ?? "—"
                                }}</b>
                                / 4 ·
                                {{ a.potentialLevel ?? "Нет оценки" }}</span
                            >
                        </div>
                    </div>
                    <i
                        class="pi pi-arrow-up-right"
                        aria-hidden="true"
                    /> </RouterLink
            ></template>
            <Paginator
                v-if="total > 20"
                :first="(page - 1) * 20"
                :rows="20"
                :total-records="total"
                @page="
                    page = $event.page + 1;
                    loadHistory();
                "
            />
        </section>
        <CandidateForm
            v-model:visible="editing"
            :candidate="candidate"
            @saved="candidate = $event"
        />
    </template>
</template>
