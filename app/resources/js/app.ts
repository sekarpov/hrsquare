import { createApp } from "vue";
import { createPinia } from "pinia";
import PrimeVue from "primevue/config";
import { HRSquareTheme } from "./config/theme";
import ToastService from "primevue/toastservice";
import ConfirmationService from "primevue/confirmationservice";
import Tooltip from "primevue/tooltip";
import { router } from "./router";
import App from "./App.vue";
import "primeicons/primeicons.css";
import "../css/app.css";
createApp(App)
    .use(createPinia())
    .use(PrimeVue, {
        theme: {
            preset: HRSquareTheme,
            options: { darkModeSelector: ".app-dark" },
        },
        locale: {
            emptyMessage: "Нет результатов",
            emptySearchMessage: "Ничего не найдено",
            accept: "Да",
            reject: "Отмена",
            choose: "Выбрать",
            searchMessage: "Найдено: {0}",
            selectionMessage: "Выбрано: {0}",
            emptySelectionMessage: "Ничего не выбрано",
            emptyFilterMessage: "Ничего не найдено",
        },
    })
    .use(ToastService)
    .use(ConfirmationService)
    .directive("tooltip", Tooltip)
    .use(router)
    .mount("#app");
