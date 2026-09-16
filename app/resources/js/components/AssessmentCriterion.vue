<script setup lang="ts">
import Textarea from "primevue/textarea";
import Button from "primevue/button";
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
    <article class="panel criterion-card">
        <div class="criterion-title">
            <span class="criterion-number">{{ number }}</span>
            <h3>{{ criterion.title }}</h3>
        </div>
        <div class="interview-prompt">
            <small>ОСНОВНОЙ ВОПРОС</small>
            <p>{{ criterion.question }}</p>
        </div>
        <details class="criterion-clarification" open>
            <summary>ЧТО УТОЧНЯЕМ</summary>
            <ul>
                <li v-for="item in criterion.clarification" :key="item">
                    {{ item }}
                </li>
            </ul>
        </details>
        <div class="criterion-strong">
            <small
                ><i class="pi pi-check" aria-hidden="true" /> СИЛЬНЫЙ
                ОТВЕТ</small
            >
            <p>{{ criterion.strongAnswer }}</p>
        </div>
        <label class="field" :for="`${criterionKey}-evidence`"
            >Факты / комментарий</label
        >
        <Textarea
            :id="`${criterionKey}-evidence`"
            :model-value="evidence ?? ''"
            @update:model-value="emit('update:evidence', $event ?? '')"
            :disabled="!editable"
            rows="3"
            auto-resize
            :placeholder="criterion.placeholder"
            :invalid="!!evidenceErrors?.length"
            class="criterion-evidence"
        />
        <small v-for="error in evidenceErrors" :key="error" class="error">{{
            error
        }}</small>
        <div class="score-label">ШКАЛА 1–4</div>
        <div
            class="criterion-scores"
            role="group"
            :aria-label="`Оценка: ${criterion.title}`"
        >
            <button
                v-for="value in scores"
                :key="value"
                type="button"
                :disabled="!editable"
                :aria-pressed="score === value"
                :class="{ active: score === value }"
                @click="emit('update:score', value)"
            >
                <strong>{{ value }}</strong
                ><span>{{ criterion.anchors[value - 1] }}</span
                ><i
                    v-if="score === value"
                    class="pi pi-check"
                    aria-hidden="true"
                />
            </button>
        </div>
        <div class="criterion-score-footer">
            <span>{{
                score == null
                    ? "Критерий не оценён"
                    : `Выбран уровень ${score} / 4`
            }}</span
            ><Button
                v-if="editable && score != null"
                icon="pi pi-times"
                label="Очистить оценку"
                text
                size="small"
                @click="emit('update:score', null)"
            />
        </div>
        <small v-for="error in scoreErrors" :key="error" class="error">{{
            error
        }}</small>
    </article>
</template>
<style scoped>
.criterion-clarification {
    margin: 14px 0;
    color: var(--muted);
    font-size: 12px;
    line-height: 1.6;
}
.criterion-clarification summary {
    font-size: 10px;
    font-weight: 650;
    letter-spacing: 0.8px;
    cursor: pointer;
}
.criterion-clarification ul {
    padding-left: 18px;
    margin: 8px 0 0;
    display: grid;
    gap: 4px;
}
.criterion-strong {
    background: #f1f8f4;
    border: 1px solid #e2eee6;
    border-radius: 8px;
    padding: 14px 16px;
    margin-bottom: 20px;
}
.criterion-strong small {
    color: #347b56;
    font-weight: 700;
    font-size: 10px;
    letter-spacing: 0.6px;
}
.criterion-strong p {
    margin: 8px 0 0;
    font-size: 12px;
    line-height: 1.6;
    color: #43574a;
}
.criterion-evidence {
    width: 100%;
}
.criterion-scores {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 10px;
}
.criterion-scores button {
    display: flex;
    position: relative;
    align-items: flex-start;
    gap: 12px;
    padding: 14px;
    border: 1px solid var(--border);
    border-radius: 8px;
    background: white;
    color: #43506a;
    text-align: left;
    font-size: 12px;
    line-height: 1.5;
    cursor: pointer;
}
.criterion-scores button strong {
    font-size: 16px;
    color: var(--brand);
}
.criterion-scores button span {
    flex: 1;
}
.criterion-scores button.active {
    border-color: var(--brand);
    background: #f0f3ff;
    box-shadow: 0 0 0 1px var(--brand);
}
.criterion-scores button:enabled:hover {
    border-color: var(--brand);
}
.criterion-scores button:disabled {
    cursor: default;
    opacity: 1;
}
.criterion-scores button i {
    font-size: 11px;
    color: var(--brand);
}
.criterion-score-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 12px;
    font-size: 11px;
    color: var(--muted);
}
@media (max-width: 600px) {
    .criterion-scores {
        grid-template-columns: 1fr;
    }
}
</style>
