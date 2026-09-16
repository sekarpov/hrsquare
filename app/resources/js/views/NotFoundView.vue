<script setup lang="ts">
import { useRoute } from "vue-router";
import EmptyState from "../components/EmptyState.vue";
const route = useRoute();
</script>
<template>
    <section class="panel">
        <EmptyState
            :title="
                route.meta.forbidden ? 'Нет доступа' : 'Страница не найдена'
            "
            :description="
                route.meta.forbidden
                    ? 'У вас нет прав для просмотра этой страницы.'
                    : 'Возможно, ссылка устарела или страница была удалена.'
            "
            :icon="route.meta.forbidden ? 'pi pi-lock' : 'pi pi-compass'"
        >
            <RouterLink to="/candidates" class="text-link"
                >К кандидатам →</RouterLink
            >
        </EmptyState>
    </section>
</template>
