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
import AssessmentSummary from "../components/AssessmentSummary.vue";
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
                                    ? "Что кандидат уже сделал и какой вклад внёс."
                                    : "Как кандидат учится, адаптируется и действует самостоятельно."
                            }}
                        </p>
                    </div>
                    <article
                        v-for="([key, criterion], index) in criteria.filter(
                            ([, c]) => c.group === group,
                        )"
                        :key="key"
                        class="panel criterion-card"
                    >
                        <div class="criterion-title">
                            <span class="criterion-number"
                                >0{{ index + 1 }}</span
                            >
                            <h3>{{ criterion.title }}</h3>
                        </div>
                        <div class="interview-prompt">
                            <small>ВОПРОС КАНДИДАТУ</small>
                            <p>{{ criterion.question }}</p>
                            <small>ЧТО УТОЧНИТЬ</small>
                            <p class="muted">{{ criterion.probe }}</p>
                        </div>
                        <label class="field"
                            >Факты интервью<Textarea
                                v-model="form[`${key}Evidence`]"
                                :disabled="!canEdit"
                                rows="3"
                                auto-resize
                                placeholder="Конкретные действия, контекст и подтверждённый результат"
                                :invalid="!!errors[`${key}Evidence`]"
                            /><small
                                v-for="error in errors[`${key}Evidence`]"
                                class="error"
                                >{{ error }}</small
                            ></label
                        >
                        <div class="score-label">Оценка</div>
                        <div
                            class="score-buttons"
                            role="group"
                            :aria-label="`Оценка: ${criterion.title}`"
                        >
                            <button
                                v-for="score in [0, 1, 2, 3]"
                                :key="score"
                                type="button"
                                :disabled="!canEdit"
                                :aria-pressed="form[`${key}Score`] === score"
                                :class="{
                                    active: form[`${key}Score`] === score,
                                }"
                                @click="form[`${key}Score`] = score"
                            >
                                {{ score }}</button
                            ><Button
                                v-if="canEdit && form[`${key}Score`] != null"
                                icon="pi pi-times"
                                text
                                aria-label="Очистить балл"
                                @click="form[`${key}Score`] = null"
                            />
                        </div>
                        <p class="score-anchor">
                            {{
                                form[`${key}Score`] != null
                                    ? criterion.anchors[form[`${key}Score`]!]
                                    : "Выберите балл на основе фактов интервью"
                            }}
                        </p>
                        <small
                            class="error"
                            v-for="error in errors[`${key}Score`]"
                            >{{ error }}</small
                        >
                    </article>
                    <div class="dimension-summary">
                        {{ group }}
                        <strong>{{
                            (group === "RESULT"
                                ? preview?.resultAverage
                                : preview?.potentialAverage
                            )?.toFixed(2) ?? "—"
                        }}</strong
                        ><span>{{
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
                <AssessmentSummary :assessment="preview" />
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
        </div></template
    ><Message v-else severity="error">{{
        failure || "Оценка недоступна"
    }}</Message>
</template>
