<script setup lang="ts">
import Textarea from "primevue/textarea";
import Button from "primevue/button";
import Card from "primevue/card";
import Accordion from "primevue/accordion";
import AccordionPanel from "primevue/accordionpanel";
import AccordionHeader from "primevue/accordionheader";
import AccordionContent from "primevue/accordioncontent";
import RadioButton from "primevue/radiobutton";
import Message from "primevue/message";
import type { Criterion, CriterionKey, Score } from "../types";
const props = defineProps<{
    criterion: Criterion;
    criterionKey: CriterionKey;
    number: number;
    score: Score | null | undefined;
    evidence: string | null | undefined;
    editable: boolean;
    scoreErrors?: string[];
    evidenceErrors?: string[];
}>();
const emit = defineEmits<{
    "update:score": [value: Score | null];
    "update:evidence": [value: string];
}>();
const scores: Score[] = [1, 2, 3, 4];
</script>
<template>
    <article class="criterion-card tw:mb-6">
        <Card
            class="tw:rounded-2xl tw:border tw:border-solid tw:border-slate-200 tw:shadow-sm"
            :pt="{ body: { class: 'tw:p-5 tw:sm:p-6' } }"
        >
            <template #title>
                <div class="tw:flex tw:items-start tw:gap-3">
                    <span
                        class="tw:flex tw:size-9 tw:shrink-0 tw:items-center tw:justify-center tw:rounded-xl tw:bg-indigo-50 tw:text-sm tw:font-bold tw:text-indigo-600"
                        >{{ number }}</span
                    >
                    <h3
                        class="tw:pt-1.5 tw:text-base tw:font-semibold tw:leading-snug tw:text-slate-900"
                    >
                        {{ criterion.title }}
                    </h3>
                </div>
            </template>
            <template #content>
                <div class="tw:space-y-5">
                    <div
                        class="interview-prompt tw:rounded-xl tw:bg-slate-50 tw:p-4"
                    >
                        <small
                            class="tw:text-xs tw:font-semibold tw:tracking-wide tw:text-slate-500"
                            >ОСНОВНОЙ ВОПРОС</small
                        >
                        <p
                            class="tw:mt-2 tw:text-sm tw:leading-relaxed tw:text-slate-700"
                        >
                            {{ criterion.question }}
                        </p>
                    </div>
                    <Accordion :value="['clarification']" multiple>
                        <AccordionPanel value="clarification">
                            <AccordionHeader
                                ><span
                                    class="tw:text-xs tw:font-semibold tw:tracking-wide tw:text-slate-600"
                                    >ЧТО УТОЧНЯЕМ</span
                                ></AccordionHeader
                            >
                            <AccordionContent>
                                <ul
                                    class="tw:m-0 tw:list-disc tw:space-y-2 tw:pl-5 tw:text-sm tw:leading-relaxed tw:text-slate-600"
                                >
                                    <li
                                        v-for="item in criterion.clarification"
                                        :key="item"
                                    >
                                        {{ item }}
                                    </li>
                                </ul>
                            </AccordionContent>
                        </AccordionPanel>
                    </Accordion>
                    <Message
                        severity="success"
                        :closable="false"
                        class="criterion-strong tw:rounded-xl"
                        :pt="{ content: { class: 'tw:items-start tw:p-4' } }"
                    >
                        <div>
                            <small
                                class="tw:text-xs tw:font-semibold tw:tracking-wide tw:text-emerald-700"
                                >ФОРМАТ ОТВЕТА</small
                            >
                            <p
                                class="tw:mt-2 tw:text-sm tw:font-normal tw:leading-relaxed tw:text-slate-700"
                            >
                                {{ criterion.strongAnswer }}
                            </p>
                        </div>
                    </Message>
                    <div class="tw:space-y-2">
                        <label
                            class="tw:block tw:text-sm tw:font-semibold tw:text-slate-700"
                            :for="`${criterionKey}-evidence`"
                            >Факты / комментарий</label
                        >
                        <Textarea
                            :id="`${criterionKey}-evidence`"
                            :model-value="evidence ?? ''"
                            @update:model-value="
                                emit('update:evidence', $event ?? '')
                            "
                            :disabled="!editable"
                            rows="4"
                            auto-resize
                            :placeholder="criterion.placeholder"
                            :invalid="!!evidenceErrors?.length"
                            class="criterion-evidence tw:w-full tw:min-h-28 tw:rounded-xl tw:text-sm tw:leading-relaxed"
                        />
                        <small
                            v-for="error in evidenceErrors"
                            :key="error"
                            class="error"
                            >{{ error }}</small
                        >
                    </div>
                    <fieldset class="tw:m-0 tw:min-w-0 tw:border-0 tw:p-0">
                        <legend
                            class="tw:mb-3 tw:text-xs tw:font-semibold tw:tracking-wide tw:text-slate-500"
                        >
                            ШКАЛА 1–4
                        </legend>
                        <div
                            class="criterion-scores tw:grid tw:grid-cols-1 tw:gap-3 tw:sm:grid-cols-2"
                        >
                            <label
                                v-for="value in scores"
                                :key="value"
                                :for="`${criterionKey}-score-${value}`"
                                class="tw:flex tw:items-start tw:gap-3 tw:rounded-xl tw:border tw:border-solid tw:p-4 tw:transition-colors tw:focus-within:ring-2 tw:focus-within:ring-indigo-300"
                                :class="[
                                    score === value
                                        ? 'tw:border-indigo-500 tw:bg-indigo-50'
                                        : 'tw:border-slate-200 tw:bg-white',
                                    editable
                                        ? 'tw:cursor-pointer tw:hover:border-indigo-400'
                                        : 'tw:cursor-default',
                                ]"
                            >
                                <RadioButton
                                    :input-id="`${criterionKey}-score-${value}`"
                                    :name="`${criterionKey}-score`"
                                    :value="value"
                                    :model-value="score"
                                    :disabled="!editable"
                                    @update:model-value="
                                        emit('update:score', $event)
                                    "
                                />
                                <div class="tw:min-w-0 tw:flex-1">
                                    <strong
                                        class="tw:block tw:text-base tw:font-semibold tw:text-slate-900"
                                        >{{ value }}</strong
                                    >
                                    <span
                                        class="tw:mt-1 tw:block tw:text-sm tw:leading-relaxed tw:text-slate-600"
                                        >{{
                                            criterion.anchors[value - 1]
                                        }}</span
                                    >
                                </div>
                            </label>
                        </div>
                    </fieldset>
                    <div
                        class="tw:flex tw:flex-wrap tw:items-center tw:justify-between tw:gap-2 tw:text-xs tw:text-slate-500"
                    >
                        <span>{{
                            score == null
                                ? "Критерий не оценён"
                                : `Выбран уровень ${score} / 4`
                        }}</span>
                        <Button
                            v-if="editable && score != null"
                            icon="pi pi-times"
                            label="Очистить оценку"
                            text
                            size="small"
                            @click="emit('update:score', null)"
                        />
                    </div>
                    <small
                        v-for="error in scoreErrors"
                        :key="error"
                        class="error"
                        >{{ error }}</small
                    >
                </div>
            </template>
        </Card>
    </article>
</template>
