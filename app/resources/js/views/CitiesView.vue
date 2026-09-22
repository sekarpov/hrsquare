<script setup lang="ts">
import { computed, onMounted, ref } from "vue";
import Button from "primevue/button";
import InputText from "primevue/inputtext";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import PageHeader from "../components/PageHeader.vue";
import RequestState from "../components/RequestState.vue";
import CityForm from "../components/CityForm.vue";
import { citiesApi } from "../api/cities";
import { errorMessage } from "../api/http";
import type { City } from "../types";

const cities = ref<City[]>([]),
    loading = ref(true),
    failure = ref(""),
    search = ref(""),
    adding = ref(false);
const filtered = computed(() =>
    cities.value.filter((city) =>
        city.name
            .toLocaleLowerCase("ru")
            .includes(search.value.trim().toLocaleLowerCase("ru")),
    ),
);
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
function added() {
    search.value = "";
    void load();
}
onMounted(load);
</script>
<template>
    <PageHeader
        title="Города"
        :count="cities.length"
        description="Справочник городов для карточек кандидатов."
    >
        <Button
            label="Добавить город"
            icon="pi pi-plus"
            @click="adding = true"
        />
    </PageHeader>
    <div class="panel filters filter-row">
        <InputText
            v-model="search"
            placeholder="Поиск города"
            aria-label="Поиск города"
        />
    </div>
    <RequestState v-if="failure" :message="failure" @retry="load" />
    <div v-else class="panel table-panel">
        <DataTable
            :value="filtered"
            :loading="loading"
            data-key="id"
            paginator
            :rows="20"
            :rows-per-page-options="[20, 50, 100]"
        >
            <template #empty>Города не найдены.</template>
            <Column field="name" header="Город" sortable />
        </DataTable>
    </div>
    <CityForm v-model:visible="adding" @saved="added" />
</template>
