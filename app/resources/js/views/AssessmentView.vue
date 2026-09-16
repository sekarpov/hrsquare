<script setup lang="ts">
import {
    ref,
    reactive,
    computed,
    onMounted,
    watch,
    onUnmounted,
    nextTick,
} from "vue";
import { useRoute, onBeforeRouteLeave } from "vue-router";
import { useAuth } from "../stores/auth";
import { useToast } from "primevue/usetoast";
import { useConfirm } from "primevue/useconfirm";
import Button from "primevue/button";
import Textarea from "primevue/textarea";
import Select from "primevue/select";
import Tag from "primevue/tag";
import Message from "primevue/message";
import Skeleton from "primevue/skeleton";
import ProgressBar from "primevue/progressbar";
import PageHeader from "../components/PageHeader.vue";
import RequestState from "../components/RequestState.vue";
import { formatDate } from "../config/presentation";
import AssessmentCriterion from "../components/AssessmentCriterion.vue";
import AssessmentSummary from "../components/AssessmentSummary.vue";
import NineBoxMatrix from "../components/NineBoxMatrix.vue";
import { assessmentsApi } from "../api/assessments";
import { candidatesApi } from "../api/candidates";
import { fieldErrors, errorMessage, errorStatus } from "../api/http";
import type {
    Assessment,
    AssessmentInput,
    Methodology,
    CriterionKey,
    Candidate,
    FieldErrors,
} from "../types";
const route = useRoute(),
    auth = useAuth(),
    toast = useToast(),
    confirm = useConfirm(),
    assessment = ref<Assessment | null>(null),
    candidate = ref<Candidate | null>(null),
    methodology = ref<Methodology | null>(null),
    preview = ref<Assessment | null>(null),
    form = reactive<AssessmentInput>({}),
    errors = ref<FieldErrors>({}),
    failure = ref(""),
    saving = ref(false),
    loading = ref(true),
    previewing = ref(false),
    activeCriterion = ref<CriterionKey>("taskScale"),
    showAll = ref(false),
    showMissing = ref(false),
    savedSnapshot = ref(""),
    status = ref(0),
    previewFailure = ref(""),
    confirming = ref(false);
const canEdit = computed(
    () =>
        assessment.value?.status === "DRAFT" &&
        assessment.value.evaluatorId === auth.user?.id,
);
const criteria = computed(
    () =>
        Object.entries(methodology.value?.criteria ?? {}) as [
            CriterionKey,
            Methodology["criteria"][CriterionKey],
        ][],
);
const calibration = [
    { label: "Нет сигнала", value: "NONE" },
    { label: "Оценка согласована", value: "ALIGNED" },
    { label: "Нужна калибровка", value: "NEEDS_CALIBRATION" },
];
const risks = [
    { label: "Не выявлен", value: "NONE" },
    { label: "Достижение результата", value: "DELIVERY" },
    { label: "Обучаемость", value: "LEARNING" },
    { label: "Адаптивность", value: "ADAPTABILITY" },
    { label: "Личная ответственность", value: "OWNERSHIP" },
    { label: "Мотивация", value: "MOTIVATION" },
];
let timer: ReturnType<typeof setTimeout>,
    version = 0;
let ready = false;
function fill(a: Assessment) {
    for (const [key] of criteria.value) {
        form[`${key}Score`] = a[`${key}Score`] ?? null;
        form[`${key}Evidence`] = a[`${key}Evidence`] ?? "";
    }
    form.calibrationSignal = a.calibrationSignal;
    form.mainRisk = a.mainRisk;
    form.finalComment = a.finalComment ?? "";
    preview.value = a;
}
async function load() {
    loading.value = true;
    failure.value = "";
    ready = false;
    try {
        methodology.value = await assessmentsApi.methodology();
        assessment.value = await assessmentsApi.get(Number(route.params.id));
        candidate.value = await candidatesApi.get(assessment.value.candidateId);
        fill(assessment.value);
        savedSnapshot.value = JSON.stringify(form);
        showAll.value = assessment.value.status === "COMPLETED";
        activeCriterion.value =
            criteria.value.find(([key]) => form[`${key}Score`] == null)?.[0] ??
            "taskScale";
        ready = true;
    } catch (e) {
        failure.value = errorMessage(e);
        status.value = errorStatus(e);
    } finally {
        loading.value = false;
    }
}
onMounted(load);
const missingCriteria = computed(() =>
    criteria.value.filter(([key]) => form[`${key}Score`] == null),
);
const filledCount = computed(
    () => criteria.value.length - missingCriteria.value.length,
);
const dirty = computed(
    () => canEdit.value && savedSnapshot.value !== JSON.stringify(form),
);
const activeIndex = computed(() =>
    criteria.value.findIndex(([key]) => key === activeCriterion.value),
);
const criterionNames: Record<CriterionKey, string> = {
    taskScale: "Масштаб задач",
    resultImpact: "Result / Impact",
    personalContribution: "Личный вклад",
    learningAgility: "Обучаемость",
    adaptability: "Адаптивность",
    initiative: "Инициативность",
};
async function goToCriterion(key: CriterionKey) {
    activeCriterion.value = key;
    await nextTick();
    const el = document.getElementById(`criterion-${key}`);
    el?.scrollIntoView({ block: "start" });
    el?.focus({ preventScroll: true });
}
async function goToSummary() {
    await nextTick();
    document
        .getElementById("assessment-summary")
        ?.scrollIntoView({ block: "start" });
}
function preventUnload(event: BeforeUnloadEvent) {
    if (dirty.value) {
        event.preventDefault();
        event.returnValue = "";
    }
}
onMounted(() => window.addEventListener("beforeunload", preventUnload));
onUnmounted(() => window.removeEventListener("beforeunload", preventUnload));
onBeforeRouteLeave(() => {
    if (!dirty.value) return true;
    return new Promise<boolean>((resolve) =>
        confirm.require({
            header: "Остались несохранённые изменения",
            message:
                "Сохраните черновик, чтобы продолжить оценку позже. При выходе последние изменения будут потеряны.",
            acceptLabel: "Выйти без сохранения",
            rejectLabel: "Продолжить оценку",
            acceptProps: { severity: "secondary" },
            accept: () => resolve(true),
            reject: () => resolve(false),
            onHide: () => resolve(false),
        }),
    );
});
watch(
    () => criteria.value.map(([key]) => form[`${key}Score`]),
    () => {
        if (!ready || !canEdit.value) return;
        const request = ++version;
        clearTimeout(timer);
        previewing.value = true;
        previewFailure.value = "";
        timer = setTimeout(async () => {
            try {
                const data = await assessmentsApi.preview(form);
                if (request === version) preview.value = data;
            } catch (e) {
                if (request === version) {
                    preview.value = null;
                    previewFailure.value =
                        "Не удалось обновить предварительный результат. Он будет рассчитан при сохранении.";
                }
            } finally {
                if (request === version) previewing.value = false;
            }
        }, 250);
    },
);
onUnmounted(() => {
    clearTimeout(timer);
    version++;
});
async function persist(complete = false) {
    saving.value = true;
    failure.value = "";
    errors.value = {};
    clearTimeout(timer);
    version++;
    try {
        assessment.value = complete
            ? await assessmentsApi.complete(assessment.value!.id, form)
            : await assessmentsApi.save(assessment.value!.id, form);
        preview.value = assessment.value;
        savedSnapshot.value = JSON.stringify(form);
        previewing.value = false;
        toast.add({
            severity: "success",
            summary: complete ? "Оценка завершена" : "Черновик сохранён",
            life: 3000,
        });
    } catch (e) {
        errors.value = fieldErrors(e);
        failure.value = errorMessage(e);
        const invalid = criteria.value.find(
            ([key]) =>
                errors.value[`${key}Score`]?.length ||
                errors.value[`${key}Evidence`]?.length,
        );
        if (invalid) await goToCriterion(invalid[0]);
    } finally {
        previewing.value = false;
        saving.value = false;
    }
}
async function complete() {
    showMissing.value = true;
    if (missingCriteria.value.length) {
        await goToCriterion(missingCriteria.value[0][0]);
        return;
    }
    confirming.value = true;
    failure.value = "";
    clearTimeout(timer);
    version++;
    try {
        const result = await assessmentsApi.preview(form);
        preview.value = result;
        confirm.require({
            header: "Завершить оценку?",
            message: `После завершения оценку нельзя будет изменить. Она сохранится в истории кандидата.\n\nRESULT: ${result.resultAverage?.toFixed(2)} / 4 · ${result.resultLevel}\nPOTENTIAL: ${result.potentialAverage?.toFixed(2)} / 4 · ${result.potentialLevel}\n9-Box: ${result.nineBoxCell}`,
            acceptLabel: "Завершить оценку",
            rejectLabel: "Продолжить работу",
            rejectProps: { severity: "secondary" },
            accept: () => void persist(true),
        });
    } catch (e) {
        failure.value = errorMessage(e);
    } finally {
        confirming.value = false;
        previewing.value = false;
    }
}
</script>
<template>
    <div v-if="loading" role="status" aria-label="Загрузка оценки">
        <Skeleton height="80px" class="tw:mb-6" /><Skeleton height="400px" />
    </div>
    <template v-else-if="assessment && candidate && methodology">
        <nav class="breadcrumbs" aria-label="Навигационная цепочка">
            <RouterLink to="/candidates">Кандидаты</RouterLink
            ><i class="pi pi-angle-right" aria-hidden="true" /><RouterLink
                :to="`/candidates/${candidate.id}`"
                >{{ candidate.fullName }}</RouterLink
            ><i class="pi pi-angle-right" aria-hidden="true" /><span
                >Оценка №{{ assessment.id }}</span
            >
        </nav>
        <PageHeader
            title="Оценка кандидата"
            :description="`${candidate.fullName} · ${candidate.position}`"
            ><Tag
                :value="
                    assessment.status === 'DRAFT' ? 'Черновик' : 'Завершена'
                "
                :severity="assessment.status === 'DRAFT' ? 'warn' : 'success'"
        /></PageHeader>
        <Message severity="info" :closable="false"
            >Оценка строится на фактах из опыта кандидата. Для каждого критерия
            зафиксируйте пример и выберите уровень 1–4.</Message
        >
        <Message v-if="!canEdit" severity="secondary">{{
            assessment.status === "COMPLETED"
                ? "Завершённая оценка доступна только для чтения. Для нового интервью создайте отдельную оценку."
                : "Этот черновик может редактировать только его автор."
        }}</Message>
        <Message v-if="failure" severity="error">{{ failure }}</Message>
        <section class="assessment-progress" aria-label="Прогресс оценки">
            <div class="progress-heading">
                <strong aria-live="polite"
                    >Оценено {{ filledCount }} из 6 критериев</strong
                ><Button
                    :label="
                        showAll ? 'По одному критерию' : 'Показать все критерии'
                    "
                    severity="secondary"
                    text
                    size="small"
                    @click="showAll = !showAll"
                />
            </div>
            <ProgressBar
                :value="(filledCount / 6) * 100"
                :show-value="false"
                :style="{ height: '6px' }"
                aria-label="Прогресс заполнения"
            />
            <nav class="criterion-nav" aria-label="Критерии оценки">
                <button
                    v-for="([key, criterion], index) in criteria"
                    :key="key"
                    type="button"
                    :class="{
                        active: key === activeCriterion && !showAll,
                        done: form[`${key}Score`] != null,
                    }"
                    :aria-current="
                        key === activeCriterion && !showAll ? 'step' : undefined
                    "
                    :aria-label="`${index + 1}. ${criterionNames[key]}. ${form[`${key}Score`] == null ? 'Не оценён' : 'Уровень ' + form[`${key}Score`]}`"
                    @click="goToCriterion(key)"
                >
                    <span class="nav-number"
                        ><i
                            v-if="form[`${key}Score`] != null"
                            class="pi pi-check"
                            aria-hidden="true"
                        /><template v-else>{{ index + 1 }}</template></span
                    ><span
                        ><small>{{ criterion.group }}</small
                        ><br />{{ criterionNames[key] }}</span
                    >
                </button>
            </nav>
        </section>
        <Message
            v-if="showMissing && missingCriteria.length"
            severity="warn"
            class="missing-criteria"
            ><strong>Осталось оценить: {{ missingCriteria.length }}</strong>
            <ul>
                <li v-for="[key] in missingCriteria" :key="key">
                    <button type="button" @click="goToCriterion(key)">
                        {{ criterionNames[key] }}
                    </button>
                </li>
            </ul></Message
        >
        <div class="assessment-layout">
            <div class="criteria-column">
                <section
                    v-for="group in ['RESULT', 'POTENTIAL'] as const"
                    :key="group"
                    v-show="
                        showAll ||
                        methodology.criteria[activeCriterion].group === group
                    "
                >
                    <div class="criteria-heading">
                        <span>{{ group }}</span>
                        <h2>
                            {{
                                group === "RESULT"
                                    ? "Доказанная результативность"
                                    : "Потенциал развития"
                            }}
                        </h2>
                        <p>
                            {{
                                group === "RESULT"
                                    ? "Что кандидат уже доказал предыдущим опытом?"
                                    : "Как кандидат действовал в новых и изменяющихся ситуациях?"
                            }}
                        </p>
                    </div>
                    <Message
                        v-if="group === 'POTENTIAL'"
                        severity="secondary"
                        size="small"
                        >Potential оценивается по конкретным фактам поведения
                        кандидата. Стаж, возраст, количество проектов или общее
                        впечатление сами по себе не являются подтверждением
                        Potential.</Message
                    >
                    <AssessmentCriterion
                        v-for="([key, criterion], index) in criteria.filter(
                            ([, c]) => c.group === group,
                        )"
                        :key="key"
                        v-show="showAll || activeCriterion === key"
                        :id="`criterion-${key}`"
                        tabindex="-1"
                        :criterion="criterion"
                        :criterion-key="key"
                        :number="index + 1 + (group === 'POTENTIAL' ? 3 : 0)"
                        v-model:score="form[`${key}Score`]"
                        v-model:evidence="form[`${key}Evidence`]"
                        :editable="canEdit && !saving && !confirming"
                        :score-errors="errors[`${key}Score`]"
                        :evidence-errors="errors[`${key}Evidence`]"
                    />
                </section>
                <div v-if="!showAll" class="criterion-navigation">
                    <Button
                        label="Предыдущий"
                        icon="pi pi-arrow-left"
                        severity="secondary"
                        :disabled="activeIndex <= 0"
                        @click="goToCriterion(criteria[activeIndex - 1][0])"
                    /><Button
                        v-if="activeIndex < 5"
                        label="Следующий критерий"
                        icon="pi pi-arrow-right"
                        icon-pos="right"
                        @click="goToCriterion(criteria[activeIndex + 1][0])"
                    /><Button
                        v-else
                        label="К итогам оценки"
                        icon="pi pi-arrow-down"
                        severity="secondary"
                        @click="goToSummary"
                    />
                </div>
                <section class="panel conclusion tw:p-6">
                    <h2>Выводы по интервью</h2>
                    <div class="form-grid">
                        <div class="field">
                            <label for="calibration">Калибровочный сигнал</label
                            ><Select
                                input-id="calibration"
                                v-model="form.calibrationSignal"
                                :options="calibration"
                                option-label="label"
                                option-value="value"
                                :disabled="!canEdit || saving"
                            /><small
                                v-for="error in errors.calibrationSignal"
                                :key="error"
                                class="error"
                                >{{ error }}</small
                            >
                        </div>
                        <div class="field">
                            <label for="risk">Главный риск</label
                            ><Select
                                input-id="risk"
                                v-model="form.mainRisk"
                                :options="risks"
                                option-label="label"
                                option-value="value"
                                :disabled="!canEdit || saving"
                            /><small
                                v-for="error in errors.mainRisk"
                                :key="error"
                                class="error"
                                >{{ error }}</small
                            >
                        </div>
                        <label class="field span-2" for="final-comment"
                            >Итоговый комментарий<Textarea
                                id="final-comment"
                                v-model="form.finalComment"
                                rows="4"
                                auto-resize
                                :disabled="!canEdit || saving"
                                placeholder="Рекомендация и что важно учесть при принятии решения"
                            /><small
                                v-for="error in errors.finalComment"
                                :key="error"
                                class="error"
                                >{{ error }}</small
                            ></label
                        >
                    </div>
                </section>
            </div>
            <aside
                id="assessment-summary"
                class="assessment-sidebar panel tw:p-5"
            >
                <div class="section-header">
                    <h2>Итог оценки</h2>
                    <i
                        v-if="previewing"
                        class="pi pi-spin pi-spinner"
                        aria-label="Обновление результата"
                    />
                </div>
                <AssessmentSummary :assessment="preview" compact />
                <Message v-if="previewFailure" severity="warn" size="small">{{
                    previewFailure
                }}</Message>
                <p class="muted">{{ filledCount }} / 6 критериев оценено</p>
                <a href="#assessment-matrix" class="back-link"
                    >Посмотреть матрицу 9-Box ↓</a
                >
                <div v-if="canEdit" class="assessment-actions">
                    <Button
                        label="Сохранить черновик"
                        icon="pi pi-save"
                        severity="secondary"
                        :loading="saving"
                        :disabled="confirming"
                        @click="persist()"
                    /><Button
                        label="Завершить оценку"
                        icon="pi pi-check"
                        :disabled="saving"
                        :loading="confirming"
                        @click="complete"
                    />
                </div>
                <p v-if="canEdit" class="save-state" role="status">
                    {{
                        saving
                            ? "Сохранение…"
                            : dirty
                              ? "Есть несохранённые изменения"
                              : `Сохранено ${formatDate(assessment.updatedAt)}`
                    }}
                </p>
                <div class="assessor">
                    <small>Автор оценки</small
                    ><strong>{{ assessment.evaluator.fullName }}</strong
                    ><span>{{
                        formatDate(
                            assessment.completedAt ?? assessment.createdAt,
                        )
                    }}</span>
                </div>
                <RouterLink
                    :to="`/candidates/${candidate.id}`"
                    class="back-link"
                    >← К карточке кандидата</RouterLink
                >
            </aside>
        </div>
        <section
            id="assessment-matrix"
            class="panel assessment-matrix tw:mt-6 tw:min-w-0 tw:scroll-mt-6 tw:p-4 tw:sm:p-6"
            aria-labelledby="assessment-matrix-title"
        >
            <div class="section-header">
                <h2 id="assessment-matrix-title">Итоговый 9-Box</h2>
                <span v-if="canEdit" class="muted"
                    >Предварительный результат</span
                >
            </div>
            <NineBoxMatrix
                :selected-cell="preview?.nineBoxCell"
                :result-level="preview?.resultLevel"
                :potential-level="preview?.potentialLevel"
            />
        </section>
    </template>
    <RequestState
        v-else
        :message="failure || 'Оценка недоступна.'"
        :status="status"
        @retry="load"
    />
</template>
