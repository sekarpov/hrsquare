<script setup lang="ts">
import Button from "primevue/button";
import EmptyState from "./EmptyState.vue";
withDefaults(
    defineProps<{ message: string; status?: number; retry?: boolean }>(),
    { retry: true },
);
defineEmits<{ retry: [] }>();
</script>
<template>
    <section class="panel" role="alert">
        <EmptyState
            :title="
                status === 403
                    ? 'Нет доступа'
                    : status === 404
                      ? 'Не найдено'
                      : 'Не удалось загрузить данные'
            "
            :description="message"
            :icon="status === 403 ? 'pi pi-lock' : 'pi pi-exclamation-circle'"
        >
            <Button
                v-if="retry && status !== 403 && status !== 404"
                label="Повторить"
                icon="pi pi-refresh"
                @click="$emit('retry')"
            />
            <RouterLink v-else to="/candidates" class="text-link"
                >К кандидатам →</RouterLink
            >
        </EmptyState>
    </section>
</template>
