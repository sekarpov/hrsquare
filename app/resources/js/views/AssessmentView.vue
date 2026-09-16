<script setup lang="ts">
import { ref, reactive, computed, onMounted, watch, onUnmounted } from "vue";
import { useRoute } from "vue-router";
import { useAuth } from "../stores/auth";
import { useToast } from "primevue/usetoast";
import { useConfirm } from "primevue/useconfirm";
import Button from "primevue/button";
import Textarea from "primevue/textarea";
import Select from "primevue/select";
import Tag from "primevue/tag";
import Message from "primevue/message";
import Skeleton from "primevue/skeleton";
import AssessmentCriterion from "../components/AssessmentCriterion.vue";
import AssessmentSummary from "../components/AssessmentSummary.vue";
import NineBoxMatrix from "../components/NineBoxMatrix.vue";
import { assessmentsApi } from "../api/assessments";
import { candidatesApi } from "../api/candidates";
import { fieldErrors, errorMessage } from "../api/http";
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
    previewing = ref(false);
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
onMounted(async () => {
    try {
        methodology.value = await assessmentsApi.methodology();
        assessment.value = await assessmentsApi.get(Number(route.params.id));
        candidate.value = await candidatesApi.get(assessment.value.candidateId);
        fill(assessment.value);
        ready = true;
    } catch (e) {
        failure.value = errorMessage(e);
    } finally {
        loading.value = false;
    }
});
watch(
    () => criteria.value.map(([key]) => form[`${key}Score`]),
    () => {
        if (!ready || !canEdit.value) return;
        const request = ++version;
        clearTimeout(timer);
        previewing.value = true;
        timer = setTimeout(async () => {
            try {
                const data = await assessmentsApi.preview(form);
                if (request === version) preview.value = data;
            } catch {
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
        previewing.value = false;
        toast.add({
            severity: "success",
            summary: complete ? "Оценка завершена" : "Черновик сохранён",
            life: 3000,
        });
    } catch (e) {
        errors.value = fieldErrors(e);
        failure.value = errorMessage(e);
    } finally {
        saving.value = false;
    }
}
function complete() {
    confirm.require({
        header: "Завершить оценку?",
        message:
            "После завершения оценка станет неизменяемой и текущим результатом кандидата. Для исправлений потребуется новая оценка.",
        acceptLabel: "Завершить",
        rejectLabel: "Продолжить работу",
        accept: () => void persist(true),
    });
}
</script>
<template>
    <Skeleton v-if="loading" height="400px" /><template
        v-else-if="assessment && candidate && methodology"
        ><div class="breadcrumbs">
            <RouterLink to="/candidates">Кандидаты</RouterLink
            ><i class="pi pi-angle-right" /><RouterLink
                :to="`/candidates/${candidate.id}`"
                >{{ candidate.fullName }}</RouterLink
            ><i class="pi pi-angle-right" />Оценка #{{ assessment.id }}
        </div>
        <div class="page-header">
            <div>
                <h1>Оценка кандидата</h1>
                <p>{{ candidate.fullName }} · {{ candidate.position }}</p>
            </div>
            <Tag
                :value="
                    assessment.status === 'DRAFT' ? 'Черновик' : 'Завершена'
                "
                :severity="assessment.status === 'DRAFT' ? 'warn' : 'success'"
            />
        </div>
        <Message severity="info" class="assessment-methodology-note"
            >Оценка строится на конкретных фактах из предыдущего опыта
            кандидата. Для каждого критерия зафиксируйте пример
            поведения/результата и выберите уровень 1–4.</Message
        >
        <Message v-if="failure" severity="error">{{ failure }}</Message
        ><Message v-if="!canEdit" severity="info">{{
            assessment.status === "COMPLETED"
                ? "Завершённая оценка доступна только для чтения. История сохраняется без изменений."
                : "Черновик может редактировать только его автор."
        }}</Message>
        <div class="assessment-layout">
            <div class="criteria-column">
                <div
                    v-for="group in ['RESULT', 'POTENTIAL'] as const"
                    :key="group"
                >
                    <div class="criteria-heading">
                        <span>{{ group }}</span>
                        <h2>
                            {{
                                group === "RESULT"
                                    ? "Достигнутые результаты"
                                    : "Потенциал развития"
                            }}
                        </h2>
                        <p>
                            {{
                                group === "RESULT"
                                    ? "Что кандидат уже доказал предыдущим опытом?"
                                    : "Как кандидат действовал в новых, изменяющихся и не заданных заранее ситуациях?"
                            }}
                        </p>
                    </div>
                    <Message
                        v-if="group === 'POTENTIAL'"
                        severity="secondary"
                        class="potential-evidence-note"
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
                        :criterion="criterion"
                        :criterion-key="key"
                        :number="index + 1 + (group === 'POTENTIAL' ? 3 : 0)"
                        v-model:score="form[`${key}Score`]"
                        v-model:evidence="form[`${key}Evidence`]"
                        :editable="canEdit"
                        :score-errors="errors[`${key}Score`]"
                        :evidence-errors="errors[`${key}Evidence`]"
                    />
                    <div class="dimension-summary">
                        {{ group }}
                        <strong>{{
                            (group === "RESULT"
                                ? preview?.resultAverage
                                : preview?.potentialAverage
                            )?.toFixed(2) ?? "—"
                        }}</strong>
                        / 4<span>{{
                            (group === "RESULT"
                                ? preview?.resultLevel
                                : preview?.potentialLevel) ??
                            "Заполните три критерия"
                        }}</span>
                    </div>
                </div>
                <section class="panel conclusion">
                    <h2>Выводы по интервью</h2>
                    <div class="form-grid">
                        <label class="field"
                            >Калибровочный сигнал<Select
                                v-model="form.calibrationSignal"
                                :options="calibration"
                                option-label="label"
                                option-value="value"
                                :disabled="!canEdit"
                            /><small
                                class="error"
                                v-for="error in errors.calibrationSignal"
                                >{{ error }}</small
                            ></label
                        ><label class="field"
                            >Главный риск<Select
                                v-model="form.mainRisk"
                                :options="risks"
                                option-label="label"
                                option-value="value"
                                :disabled="!canEdit"
                            /><small
                                class="error"
                                v-for="error in errors.mainRisk"
                                >{{ error }}</small
                            ></label
                        ><label class="field span-2"
                            >Итоговый комментарий<Textarea
                                v-model="form.finalComment"
                                rows="4"
                                auto-resize
                                :disabled="!canEdit"
                                placeholder="Рекомендация и что важно учесть при принятии решения"
                            /><small
                                class="error"
                                v-for="error in errors.finalComment"
                                >{{ error }}</small
                            ></label
                        >
                    </div>
                </section>
            </div>
            <aside class="panel assessment-sidebar">
                <div class="section-header">
                    <h2>Итог оценки</h2>
                    <i v-if="previewing" class="pi pi-spin pi-spinner" />
                </div>
                <AssessmentSummary :assessment="preview" compact />
                <a href="#assessment-matrix" class="back-link"
                    >Посмотреть матрицу 9-Box ↓</a
                >
                <p class="methodology-note">
                    Расчёт выполняется сервером. Пороги уровней временные:
                    {{ methodology.medium_threshold }} /
                    {{ methodology.high_threshold }}.
                </p>
                <div class="assessor">
                    <small>АВТОР ОЦЕНКИ</small
                    ><strong>{{ assessment.evaluator.fullName }}</strong
                    ><span>{{
                        new Date(
                            assessment.completedAt ?? assessment.createdAt,
                        ).toLocaleString("ru-RU")
                    }}</span>
                </div>
                <div v-if="canEdit" class="assessment-actions">
                    <Button
                        label="Сохранить черновик"
                        icon="pi pi-save"
                        severity="secondary"
                        :loading="saving"
                        @click="persist()"
                    /><Button
                        label="Завершить оценку"
                        icon="pi pi-check"
                        :disabled="saving"
                        @click="complete"
                    />
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
            class="panel assessment-matrix"
            aria-labelledby="assessment-matrix-title"
        >
            <div class="section-header">
                <h2 id="assessment-matrix-title">Итоговый 9-Box</h2>
            </div>
            <NineBoxMatrix
                :selected-cell="preview?.nineBoxCell"
                :result-level="preview?.resultLevel"
                :potential-level="preview?.potentialLevel"
            /></section></template
    ><Message v-else severity="error">{{
        failure || "Оценка недоступна"
    }}</Message>
</template>

<style scoped>
.assessment-matrix {
    margin-top: 24px;
    max-width: 1400px;
    min-width: 0;
    padding: 24px;
    scroll-margin-top: 24px;
}
@media (max-width: 600px) {
    .assessment-matrix {
        padding: 16px;
    }
}
.assessment-methodology-note {
    margin-bottom: 20px;
}
.potential-evidence-note {
    margin-bottom: 20px;
}
</style>
