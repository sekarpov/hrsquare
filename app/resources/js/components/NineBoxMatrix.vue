<script setup lang="ts">
import { computed } from "vue";
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
const labels = [
    "Высокий потенциал",
    "Высокий потенциал",
    "Высокий потенциал",
    "Средний потенциал",
    "Средний потенциал",
    "Средний потенциал",
    "Низкий потенциал",
    "Низкий потенциал",
    "Низкий потенциал",
];
const selected = computed(() => props.selectedCell);
</script>
<template>
    <div class="matrix-wrap">
        <div class="potential-axis">POTENTIAL ↑</div>
        <div class="matrix-content">
            <div class="matrix" role="group" aria-label="Матрица 9-Box">
                <button
                    v-for="(cell, i) in cells"
                    :key="cell"
                    type="button"
                    :disabled="!interactive"
                    :aria-pressed="selected === cell"
                    :aria-label="`${cell}, ${labels[i]}, результат ${['низкий', 'средний', 'высокий'][i % 3]}`"
                    class="matrix-cell"
                    :class="[
                        { selected: selected === cell },
                        `cell-${cell}`,
                        `row-${cell[1]}`,
                    ]"
                    @click="emit('select', cell)"
                >
                    <strong>{{ cell }}</strong
                    ><i v-if="selected === cell" class="pi pi-check-circle" />
                </button>
            </div>
            <div class="axis-levels">
                <span>LOW</span><span>MEDIUM</span><span>HIGH</span>
            </div>
            <div class="result-axis">RESULT →</div>
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
.matrix-cell {
    background: var(--nine-box-neutral);
    color: var(--nine-box-foreground);
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
    border-color: var(--nine-box-foreground);
    box-shadow: 0 0 0 3px rgb(20 43 122 / 25%);
}
.matrix-cell:enabled:hover {
    filter: brightness(1.04);
}
</style>
