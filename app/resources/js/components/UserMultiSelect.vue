<script setup lang="ts">
import { ref, onMounted, onUnmounted, watch } from "vue";
import MultiSelect from "primevue/multiselect";
import { usersApi } from "../api/users";
import type { User } from "../types";
const props = defineProps<{
    modelValue: number[];
    role: "managers" | "recruiters";
    selectedUsers?: User[];
    invalid?: boolean;
    inputId?: string;
}>();
const emit = defineEmits<{ "update:modelValue": [value: number[]] }>();
const options = ref<User[]>([]),
    loading = ref(false);
let timer: ReturnType<typeof setTimeout>;
let request = 0;
async function search(value = "") {
    const version = ++request;
    loading.value = true;
    try {
        const users = await usersApi.options(props.role, value);
        if (version === request) {
            const retained = [
                ...(props.selectedUsers ?? []),
                ...options.value.filter((u) => props.modelValue.includes(u.id)),
            ];
            options.value = Array.from(
                new Map([...retained, ...users].map((u) => [u.id, u])).values(),
            );
        }
    } catch {
    } finally {
        if (version === request) loading.value = false;
    }
}
watch(
    () => props.selectedUsers,
    () => void search(),
);
onMounted(() => void search());
onUnmounted(() => clearTimeout(timer));
function filter(event: { value: string }) {
    clearTimeout(timer);
    timer = setTimeout(() => void search(event.value), 250);
}
</script>
<template>
    <MultiSelect
        :input-id="inputId"
        :model-value="modelValue"
        :options="options"
        option-label="fullName"
        option-value="id"
        filter
        display="chip"
        :loading="loading"
        :invalid="invalid"
        placeholder="Начните вводить имя"
        :max-selected-labels="3"
        @filter="filter"
        @update:model-value="emit('update:modelValue', $event)"
    />
</template>
