import { createApp } from "vue";
import { createPinia } from "pinia";
import PrimeVue from "primevue/config";
import Aura from "@primeuix/themes/aura";
import { definePreset } from "@primeuix/themes";
const HRSquareTheme = definePreset(Aura, {
    semantic: {
        primary: {
            50: "{indigo.50}",
            100: "{indigo.100}",
            200: "{indigo.200}",
            300: "{indigo.300}",
            400: "{indigo.400}",
            500: "#4765dc",
            600: "{indigo.600}",
            700: "{indigo.700}",
            800: "{indigo.800}",
            900: "{indigo.900}",
            950: "{indigo.950}",
        },
    },
});
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
        },
    })
    .use(ToastService)
    .use(ConfirmationService)
    .directive("tooltip", Tooltip)
    .use(router)
    .mount("#app");
