import Aura from "@primeuix/themes/aura";
import { definePreset } from "@primeuix/themes";

// Shared CSS tokens are defined in resources/css/app.css.
export const HRSquareTheme = definePreset(Aura, {
    semantic: {
        primary: {
            50: "{blue.50}",
            100: "{blue.100}",
            200: "{blue.200}",
            300: "{blue.300}",
            400: "{blue.400}",
            500: "var(--primary)",
            600: "var(--primary-hover)",
            700: "#244391",
            800: "#203a78",
            900: "#1c315f",
            950: "#172444",
        },
        transitionDuration: "0.15s",
        focusRing: {
            width: "2px",
            style: "solid",
            color: "var(--primary)",
            offset: "2px",
        },
        formField: {
            paddingX: "0.75rem",
            paddingY: "0.65rem",
            borderRadius: "var(--radius-sm)",
            focusRing: {
                width: "2px",
                style: "solid",
                color: "var(--focus-ring)",
                offset: "1px",
                shadow: "none",
            },
        },
        colorScheme: {
            light: {
                primary: {
                    color: "var(--primary)",
                    contrastColor: "#ffffff",
                    hoverColor: "var(--primary-hover)",
                    activeColor: "#244391",
                },
                text: {
                    color: "var(--text-primary)",
                    mutedColor: "var(--text-secondary)",
                },
                formField: {
                    borderColor: "var(--border-strong)",
                    placeholderColor: "var(--text-muted)",
                    shadow: "none",
                },
                content: { borderColor: "var(--border)" },
            },
        },
    },
    components: {
        button: {
            root: {
                label: { fontWeight: "600" },
                borderRadius: "var(--radius-sm)",
            },
        },
        card: {
            root: {
                borderRadius: "var(--radius-lg)",
                shadow: "var(--shadow-sm)",
            },
            body: { padding: "1.5rem" },
        },
        dialog: {
            root: { borderRadius: "var(--radius-lg)" },
            header: { padding: "1.5rem" },
            content: { padding: "0 1.5rem 1.5rem" },
        },
        tag: {
            root: {
                fontSize: "0.75rem",
                fontWeight: "600",
                borderRadius: "var(--radius-sm)",
            },
        },
        datatable: {
            headerCell: { padding: "0.85rem 1rem" },
            bodyCell: { padding: "1rem" },
        },
    },
});
