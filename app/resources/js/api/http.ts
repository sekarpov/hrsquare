import axios from "axios";
import type { FieldErrors } from "../types";
export const http = axios.create({
    baseURL: "/api",
    headers: {
        Accept: "application/json",
        "X-Requested-With": "XMLHttpRequest",
    },
    withCredentials: true,
    withXSRFToken: true,
});
http.interceptors.response.use(
    (r) => r,
    (error) => {
        const status = error.response?.status;
        if ([401, 403, 404, 419, 429, 500, 502, 503].includes(status))
            window.dispatchEvent(
                new CustomEvent("api:error", {
                    detail: {
                        status,
                        message:
                            error.response?.data?.message ??
                            "Сервис недоступен. Попробуйте позже.",
                    },
                }),
            );
        if (!error.response)
            window.dispatchEvent(
                new CustomEvent("api:error", {
                    detail: {
                        status: 0,
                        message: "Нет соединения с сервером.",
                    },
                }),
            );
        return Promise.reject(error);
    },
);
export function fieldErrors(error: unknown): FieldErrors {
    return axios.isAxiosError(error)
        ? (error.response?.data?.errors ?? {})
        : {};
}
export function errorMessage(error: unknown): string {
    return axios.isAxiosError(error)
        ? (error.response?.data?.message ?? "Не удалось сохранить изменения.")
        : "Не удалось сохранить изменения.";
}
