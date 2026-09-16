<script setup lang="ts">
import NineBoxMatrix from "./NineBoxMatrix.vue";
import { levelLabels } from "../config/presentation";
import { nineBoxMetadata } from "../config/nineBox";
import type { Assessment } from "../types";
defineProps<{ assessment: Assessment | null; compact?: boolean }>();
</script>
<template>
    <div class="summary-scores">
        <div>
            <small>RESULT</small
            ><strong
                >{{ assessment?.resultAverage?.toFixed(2) ?? "—" }}
                <small v-if="assessment?.resultAverage != null"
                    >/ 4</small
                ></strong
            ><span>{{
                assessment?.resultLevel
                    ? levelLabels[assessment.resultLevel]
                    : "Нет оценки"
            }}</span>
        </div>
        <div>
            <small>POTENTIAL</small
            ><strong
                >{{ assessment?.potentialAverage?.toFixed(2) ?? "—" }}
                <small v-if="assessment?.potentialAverage != null"
                    >/ 4</small
                ></strong
            ><span>{{
                assessment?.potentialLevel
                    ? levelLabels[assessment.potentialLevel]
                    : "Нет оценки"
            }}</span>
        </div>
        <div>
            <small>9-BOX</small
            ><strong class="box-code">{{
                assessment?.nineBoxCell ?? "—"
            }}</strong
            ><span>{{
                assessment?.nineBoxCell ? "Результат оценки" : "Не определён"
            }}</span>
        </div>
    </div>
    <template v-if="!compact">
        <h3 v-if="assessment?.nineBoxCell" class="tw:mb-5 tw:text-base">
            {{ nineBoxMetadata[assessment.nineBoxCell].title }}
        </h3>
        <NineBoxMatrix
            :selected-cell="assessment?.nineBoxCell"
            :result-level="assessment?.resultLevel"
            :potential-level="assessment?.potentialLevel"
        />
    </template>
</template>
