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
                        `cell-${cell[0]}`,
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
