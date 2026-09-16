<script setup lang="ts">
withDefaults(
    defineProps<{ title: string; description?: string; icon?: string }>(),
    { icon: "pi pi-inbox" },
);
</script>
<template>
    <div class="empty-state">
        <i :class="icon" aria-hidden="true" />
        <h2>{{ title }}</h2>
        <p v-if="description">{{ description }}</p>
        <div v-if="$slots.default" class="empty-actions"><slot /></div>
    </div>
</template>
