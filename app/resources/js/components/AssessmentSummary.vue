<script setup lang="ts">
import NineBoxMatrix from "./NineBoxMatrix.vue";
import type { Assessment } from "../types";
defineProps<{ assessment: Assessment | null; compact?: boolean }>();
const levelLabel = (level: string | null | undefined) =>
    ({ LOW: "Низкий", MEDIUM: "Средний", HIGH: "Высокий" })[level ?? ""] ??
    "Нет оценки";
</script>
<template>
    <div class="summary-scores">
        <div>
            <small>RESULT</small
            ><strong>{{ assessment?.resultAverage?.toFixed(2) ?? "—" }}</strong
            ><span>{{ levelLabel(assessment?.resultLevel) }}</span>
        </div>
        <div>
            <small>POTENTIAL</small
            ><strong>{{
                assessment?.potentialAverage?.toFixed(2) ?? "—"
            }}</strong
            ><span>{{ levelLabel(assessment?.potentialLevel) }}</span>
        </div>
        <div>
            <small>9-BOX</small
            ><strong class="box-code">{{
                assessment?.nineBoxCell ?? "—"
            }}</strong
            ><span>Результат оценки</span>
        </div>
    </div>
    <NineBoxMatrix
        v-if="!compact"
        :selected-cell="assessment?.nineBoxCell"
        :result-level="assessment?.resultLevel"
        :potential-level="assessment?.potentialLevel"
    />
</template>
