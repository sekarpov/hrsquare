<script setup lang="ts">
import { computed } from "vue";
import BorderBeam from "./ui/BorderBeam.vue";
import { nineBoxMetadata } from "../config/nineBox";
import type { Level, Cell } from "../types";
const props = withDefaults(
    defineProps<{
        resultLevel?: Level | null;
        potentialLevel?: Level | null;
        selectedCell?: Cell | null;
        interactive?: boolean;
    }>(),
    { interactive: false },
);
const emit = defineEmits<{ select: [cell: Cell] }>();
const cells: Cell[] = ["M1", "S1", "B1", "M2", "S2", "B2", "M3", "S3", "B3"];
const potentialLevels = ["HIGH", "MEDIUM", "LOW"];
const resultLevels = ["LOW", "MEDIUM", "HIGH"];
const selected = computed(() => props.selectedCell);
</script>
<template>
    <div class="matrix-wrap">
        <div
            class="matrix-scroll"
            tabindex="0"
            role="region"
            aria-label="Матрица 9-Box, горизонтальная прокрутка"
        >
            <div class="matrix-frame">
                <div class="result-axis">RESULT / РЕЗУЛЬТАТИВНОСТЬ →</div>
                <div class="axis-levels">
                    <span v-for="level in resultLevels" :key="level">{{
                        level
                    }}</span>
                </div>
                <div class="potential-axis">POTENTIAL ↑</div>
                <div class="potential-levels">
                    <span v-for="level in potentialLevels" :key="level">{{
                        level
                    }}</span>
                </div>
                <div class="matrix" role="group" aria-label="Матрица 9-Box">
                    <button
                        v-for="(cell, i) in cells"
                        :key="cell"
                        type="button"
                        :disabled="!interactive"
                        :aria-pressed="selected === cell"
                        :aria-label="`${selected === cell ? 'Текущая оценка. ' : ''}${cell}, ${nineBoxMetadata[cell].title}, RESULT ${resultLevels[i % 3]}, POTENTIAL ${potentialLevels[Math.floor(i / 3)]}. ${nineBoxMetadata[cell].description}${nineBoxMetadata[cell].recommendation ? ' ' + nineBoxMetadata[cell].recommendation : ''}`"
                        class="matrix-cell"
                        :class="[
                            { selected: selected === cell },
                            `cell-${cell}`,
                            `row-${cell[1]}`,
                        ]"
                        @click="emit('select', cell)"
                    >
                        <BorderBeam
                            v-if="selected === cell"
                            :size="220"
                            :border-width="3"
                            :offset="5"
                            :duration="4"
                        />
                        <div class="matrix-cell-header">
                            <strong class="matrix-code">{{ cell }}</strong>
                            <span
                                v-if="selected === cell"
                                class="matrix-selected-label"
                            >
                                <i class="pi pi-star-fill" aria-hidden="true" />
                                Текущая оценка
                            </span>
                        </div>
                        <span class="matrix-title">{{
                            nineBoxMetadata[cell].title
                        }}</span>
                        <span class="matrix-description">{{
                            nineBoxMetadata[cell].description
                        }}</span>
                        <span
                            v-if="nineBoxMetadata[cell].recommendation"
                            class="matrix-recommendation"
                            >{{ nineBoxMetadata[cell].recommendation }}</span
                        >
                    </button>
                </div>
            </div>
        </div>
        <div class="matrix-notes">
            <div>
                <strong>RESULT — доказанная результативность.</strong>
                <p>
                    HIGH: сильный результат · MEDIUM: цель достигнута · LOW:
                    результат не подтверждён.
                </p>
            </div>
            <div>
                <strong>POTENTIAL — прогноз.</strong>
                <p>
                    Строится на конкретных фактах: Обучаемость, Адаптивность,
                    Инициативность.
                </p>
            </div>
        </div>
    </div>
</template>

<style scoped>
.matrix-wrap {
    --nine-box-neutral: #d4dbea;
    --nine-box-mint: #3af28c;
    --nine-box-green: #00d563;
    --nine-box-amber: #ffbe00;
    --nine-box-blue: #0964f5;
    --nine-box-light-blue: #c9d9f6;
    --nine-box-foreground: #142b7a;
    --nine-box-on-blue: #ffffff;
}
.matrix-wrap {
    display: block;
    max-width: 100%;
    min-width: 0;
    width: 100%;
    container-type: inline-size;
}
.matrix-scroll {
    overflow-x: auto;
    padding: 9px;
}
.matrix-frame {
    min-width: 640px;
    display: grid;
    grid-template-columns: 24px 56px minmax(0, 1fr);
    grid-template-rows: auto auto auto;
    column-gap: 10px;
    row-gap: 12px;
}
.result-axis {
    text-align: center;
    grid-column: 3;
    margin: 0;
    font-size: 14px;
    letter-spacing: 0.5px;
    font-weight: 700;
    color: var(--muted);
}
.axis-levels {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    text-align: center;
    grid-column: 3;
    margin: 0;
    font-size: 12px;
    font-weight: 600;
    gap: 12px;
    color: var(--muted);
}
.potential-axis {
    writing-mode: vertical-rl;
    transform: rotate(180deg);
    grid-column: 1;
    grid-row: 3;
    align-self: center;
    justify-self: center;
    font-size: 13px;
    font-weight: 700;
    letter-spacing: 0.5px;
    color: var(--muted);
}
.potential-levels {
    grid-column: 2;
    grid-row: 3;
    display: grid;
    grid-template-rows: repeat(3, 1fr);
    gap: 12px;
    align-items: center;
    text-align: right;
    font-size: 11px;
    font-weight: 600;
    color: var(--muted);
}
.matrix {
    display: grid;
    gap: 12px;
    grid-column: 3;
    grid-row: 3;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    grid-auto-rows: 1fr;
}
.matrix-cell {
    display: flex;
    position: relative;
    border: 1px solid transparent;
    border-radius: var(--radius-md);
    opacity: 1;
    cursor: default;
    transition:
        box-shadow 150ms,
        border-color 150ms;
    aspect-ratio: auto;
    min-width: 0;
    min-height: 260px;
    padding: 16px;
    flex-direction: column;
    align-items: flex-start;
    justify-content: flex-start;
    text-align: left;
    gap: 0;
    overflow-wrap: anywhere;
    font-weight: 400;
    background: var(--nine-box-neutral);
    color: var(--nine-box-foreground);
}
.matrix-cell .matrix-code {
    font-size: 12px;
    font-weight: 700;
    line-height: 1.3;
}
.matrix-cell-header {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 6px;
    min-height: 52px;
    width: 100%;
}
.matrix-selected-label {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    justify-self: end;
    color: var(--nine-box-on-blue);
    font-size: 12px;
    font-weight: 600;
    line-height: 1.3;
}
.matrix-selected-label .pi {
    position: static;
    font-size: 14px;
    color: inherit;
}
.matrix-title {
    margin-top: 9px;
    font-size: 14px;
    font-weight: 700;
    line-height: 1.25;
}
.matrix-description {
    margin-top: 12px;
    font-size: 12px;
    line-height: 1.45;
}
.matrix-recommendation {
    margin-top: auto;
    padding-top: 15px;
    font-size: 12px;
    line-height: 1.4;
    font-weight: 600;
    font-style: italic;
}
.matrix-notes {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px;
    margin-top: 18px;
    color: var(--muted);
    font-size: 11px;
    line-height: 1.5;
}
.matrix-notes > div {
    padding: 12px;
    border: 1px solid var(--border);
    border-radius: 8px;
}
.matrix-notes strong {
    font-weight: 600;
}
.matrix-notes p {
    margin: 6px 0 0;
}
@container (max-width: 600px) {
    .matrix-notes {
        grid-template-columns: 1fr;
    }
}
.cell-S1 {
    background: var(--nine-box-mint);
}
.cell-B1 {
    background: var(--nine-box-green);
}
.cell-S2 {
    background: var(--nine-box-amber);
}
.cell-B2 {
    background: var(--nine-box-blue);
    color: var(--nine-box-on-blue);
}
.cell-B3 {
    background: var(--nine-box-light-blue);
}
.matrix-cell.selected {
    transform: none;
    border: 2px solid var(--nine-box-foreground);
    padding: 15px;
    box-shadow:
        inset 0 0 0 2px var(--nine-box-on-blue),
        0 0 0 2px var(--nine-box-on-blue),
        0 0 0 5px var(--nine-box-foreground),
        0 6px 16px rgb(20 43 122 / 18%);
    isolation: isolate;
    z-index: 1;
}
.matrix-cell.selected .matrix-cell-header {
    display: grid;
    grid-template-columns: auto minmax(0, 1fr);
    gap: 10px;
    align-items: center;
    padding: 10px;
    border-radius: var(--radius-sm);
    background: var(--nine-box-foreground);
    color: var(--nine-box-on-blue);
}
.matrix-cell.selected .matrix-code {
    font-size: 22px;
    letter-spacing: -0.5px;
}
.matrix-cell.selected .matrix-selected-label {
    animation: selected-result-reveal 400ms ease-out both;
}
@keyframes selected-result-reveal {
    from {
        opacity: 0;
        transform: translateY(4px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
@media (prefers-reduced-motion: reduce) {
    .matrix-cell.selected .matrix-selected-label {
        animation: none;
    }
}
.matrix-cell:enabled {
    cursor: pointer;
}
.matrix-cell:enabled:hover {
    filter: brightness(1.04);
}
</style>
