<script setup lang="ts">
defineProps<{ title: string; description?: string; count?: number }>();
</script>
<template>
    <header class="page-header">
        <div class="page-heading">
            <div class="page-title-row">
                <h1>{{ title }}</h1>
                <span v-if="count !== undefined" class="count">{{ count }}</span
                ><slot name="badge" />
            </div>
            <p v-if="description">{{ description }}</p>
        </div>
        <div v-if="$slots.default" class="header-actions"><slot /></div>
    </header>
</template>
