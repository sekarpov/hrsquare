<script setup lang="ts">
import { ref, reactive, computed, onMounted, watch, onUnmounted } from "vue";
import { useRoute } from "vue-router";
import { useAuth } from "../stores/auth";
import { useToast } from "primevue/usetoast";
import { useConfirm } from "primevue/useconfirm";
import Button from "primevue/button";
import Card from "primevue/card";
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
        <Message
            severity="info"
            class="assessment-methodology-note tw:mb-6 tw:rounded-xl"
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
        <div
            class="assessment-layout tw:grid tw:max-w-[1400px] tw:grid-cols-1 tw:items-start tw:gap-6 tw:min-[1051px]:grid-cols-[minmax(0,1fr)_320px]"
        >
            <div class="criteria-column">
                <div
                    v-for="group in ['RESULT', 'POTENTIAL'] as const"
                    :key="group"
                >
                    <div class="tw:mb-5 tw:mt-3 tw:space-y-2">
                        <span
                            class="tw:text-xs tw:font-semibold tw:tracking-widest tw:text-indigo-600"
                            >{{ group }}</span
                        >
                        <h2
                            class="tw:text-xl tw:font-semibold tw:text-slate-900"
                        >
                            {{
                                group === "RESULT"
                                    ? "Достигнутые результаты"
                                    : "Потенциал развития"
                            }}
                        </h2>
                        <p class="tw:text-sm tw:text-slate-500">
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
                        class="potential-evidence-note tw:mb-6 tw:rounded-xl"
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
                    <div
                        class="tw:mb-8 tw:mt-4 tw:flex tw:flex-wrap tw:items-center tw:gap-3 tw:rounded-xl tw:bg-indigo-50 tw:px-5 tw:py-4 tw:text-sm tw:font-medium tw:text-indigo-700"
                    >
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
                <section class="conclusion">
                    <Card
                        class="tw:rounded-2xl tw:border tw:border-solid tw:border-slate-200 tw:shadow-sm"
                        :pt="{ body: { class: 'tw:p-5 tw:sm:p-6' } }"
                    >
                        <template #content>
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
                        </template>
                    </Card>
                </section>
            </div>
            <aside
                class="assessment-sidebar tw:row-start-1 tw:p-0 tw:min-[1051px]:row-auto tw:min-[1051px]:sticky tw:top-6"
            >
                <Card
                    class="tw:rounded-2xl tw:border tw:border-solid tw:border-slate-200 tw:shadow-sm"
                    :pt="{ body: { class: 'tw:p-5 tw:sm:p-6' } }"
                >
                    <template #content>
                        <div class="section-header">
                            <h2>Итог оценки</h2>
                            <i
                                v-if="previewing"
                                class="pi pi-spin pi-spinner"
                            />
                        </div>
                        <AssessmentSummary :assessment="preview" compact />
                        <a href="#assessment-matrix" class="back-link"
                            >Посмотреть матрицу 9-Box ↓</a
                        >
                        <p class="methodology-note">
                            Расчёт выполняется сервером. Пороги уровней
                            временные:
                            {{ methodology.medium_threshold }} /
                            {{ methodology.high_threshold }}.
                        </p>
                        <div class="assessor">
                            <small>АВТОР ОЦЕНКИ</small
                            ><strong>{{ assessment.evaluator.fullName }}</strong
                            ><span>{{
                                new Date(
                                    assessment.completedAt ??
                                        assessment.createdAt,
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
                    </template>
                </Card>
            </aside>
        </div>
        <section
            id="assessment-matrix"
            class="assessment-matrix tw:mt-6 tw:min-w-0 tw:max-w-[1400px] tw:scroll-mt-6"
            aria-labelledby="assessment-matrix-title"
        >
            <Card
                class="tw:rounded-2xl tw:border tw:border-solid tw:border-slate-200 tw:shadow-sm"
                :pt="{ body: { class: 'tw:p-4 tw:sm:p-6' } }"
            >
                <template #content>
                    <div class="section-header">
                        <h2 id="assessment-matrix-title">Итоговый 9-Box</h2>
                    </div>
                    <NineBoxMatrix
                        :selected-cell="preview?.nineBoxCell"
                        :result-level="preview?.resultLevel"
                        :potential-level="preview?.potentialLevel"
                    />
                </template>
            </Card></section></template
    ><Message v-else severity="error">{{
        failure || "Оценка недоступна"
    }}</Message>
</template>
