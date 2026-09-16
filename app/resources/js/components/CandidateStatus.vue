<script setup lang="ts">
import Tag from "primevue/tag";
import type { CandidateStatus } from "../types";
import { candidateStatuses } from "../config/presentation";
defineProps<{ status: CandidateStatus }>();
</script>
<template>
    <Tag
        :value="candidateStatuses.find((s) => s.value === status)?.label"
        :severity="
            status === 'HIRED'
                ? 'success'
                : status === 'REJECTED'
                  ? 'secondary'
                  : 'info'
        "
    />
</template>
