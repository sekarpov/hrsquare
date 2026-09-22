<script setup lang="ts">
import { onMounted, ref } from "vue";
import Select from "primevue/select";
import Button from "primevue/button";
import CityForm from "./CityForm.vue";
import { citiesApi } from "../api/cities";
import { errorMessage } from "../api/http";
import type { City } from "../types";

defineProps<{
    inputId: string;
    disabled?: boolean;
    invalid?: boolean;
    describedby?: string;
}>();
const model = defineModel<string | null>();
const cities = ref<City[]>([]),
    loading = ref(false),
    failure = ref(""),
    adding = ref(false);
async function load() {
    loading.value = true;
    failure.value = "";
    try {
        cities.value = await citiesApi.list();
    } catch (error) {
        failure.value = errorMessage(error);
    } finally {
        loading.value = false;
    }
}
function added(city: City) {
    cities.value = [...cities.value, city].sort((a, b) =>
        a.name.localeCompare(b.name, "ru"),
    );
    model.value = city.name;
}
onMounted(load);
</script>
<template>
    <Select
        :input-id="inputId"
        v-model="model"
        :options="cities"
        option-label="name"
        option-value="name"
        placeholder="Выберите город"
        filter
        reset-filter-on-hide
        filter-placeholder="Поиск города"
        :filter-input-props="{ 'aria-label': 'Поиск города' }"
        empty-filter-message="Город не найден"
        empty-message="Нет городов"
        show-clear
        :loading="loading"
        :disabled="disabled || loading || !!failure"
        :invalid="invalid"
        :aria-describedby="describedby"
    />
    <small v-if="failure" role="alert" class="error">{{ failure }}</small>
    <Button
        v-if="failure"
        type="button"
        label="Повторить загрузку городов"
        text
        size="small"
        :disabled="disabled || loading"
        @click="load"
    />
    <Button
        type="button"
        label="Добавить город"
        icon="pi pi-plus"
        text
        size="small"
        :disabled="disabled"
        @click="adding = true"
    />
    <CityForm v-model:visible="adding" @saved="added" />
</template>
