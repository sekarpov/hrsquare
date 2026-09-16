<script setup lang="ts">
import NineBoxMatrix from "./NineBoxMatrix.vue";
import type { Assessment } from "../types";
defineProps<{ assessment: Assessment | null; compact?: boolean }>();
const levelLabel = (level: string | null | undefined) =>
    ({ LOW: "Низкий", MEDIUM: "Средний", HIGH: "Высокий" })[level ?? ""] ??
    "Нет оценки";
</script>
<template>
    <div class="summary-scores tw:grid tw:grid-cols-3 tw:gap-3">
        <div class="tw:rounded-xl tw:bg-slate-50 tw:p-3 tw:text-center">
            <small
                class="tw:text-[10px] tw:font-semibold tw:tracking-wide tw:text-slate-500"
                >RESULT</small
            ><strong
                class="tw:text-2xl tw:font-semibold tw:tabular-nums tw:text-slate-900"
                >{{ assessment?.resultAverage?.toFixed(2) ?? "—" }}</strong
            ><span class="tw:text-[10px] tw:text-slate-500">{{
                levelLabel(assessment?.resultLevel)
            }}</span>
        </div>
        <div class="tw:rounded-xl tw:bg-slate-50 tw:p-3 tw:text-center">
            <small
                class="tw:text-[10px] tw:font-semibold tw:tracking-wide tw:text-slate-500"
                >POTENTIAL</small
            ><strong
                class="tw:text-2xl tw:font-semibold tw:tabular-nums tw:text-slate-900"
                >{{ assessment?.potentialAverage?.toFixed(2) ?? "—" }}</strong
            ><span class="tw:text-[10px] tw:text-slate-500">{{
                levelLabel(assessment?.potentialLevel)
            }}</span>
        </div>
        <div class="tw:rounded-xl tw:bg-slate-50 tw:p-3 tw:text-center">
            <small
                class="tw:text-[10px] tw:font-semibold tw:tracking-wide tw:text-slate-500"
                >9-BOX</small
            ><strong
                class="box-code tw:text-2xl tw:font-semibold tw:text-indigo-600"
                >{{ assessment?.nineBoxCell ?? "—" }}</strong
            ><span class="tw:text-[10px] tw:text-slate-500"
                >Результат оценки</span
            >
        </div>
    </div>
    <NineBoxMatrix
        v-if="!compact"
        :selected-cell="assessment?.nineBoxCell"
        :result-level="assessment?.resultLevel"
        :potential-level="assessment?.potentialLevel"
    />
</template>
