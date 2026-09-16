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
export function errorStatus(error: unknown): number {
    return axios.isAxiosError(error) ? (error.response?.status ?? 0) : 0;
}
export function errorMessage(error: unknown): string {
    const status = errorStatus(error);
    if (status === 403) return "У вас нет доступа к этим данным или действию.";
    if (status === 404)
        return "Данные не найдены. Возможно, запись была удалена или ссылка устарела.";
    if (status === 422)
        return "Проверьте отмеченные поля и попробуйте ещё раз.";
    if (status === 419)
        return "Сессия истекла. Обновите страницу и войдите снова.";
    if (status === 429)
        return "Слишком много запросов. Подождите немного и повторите попытку.";
    if (!status)
        return "Не удалось связаться с сервером. Проверьте соединение и попробуйте ещё раз.";
    return "Не удалось выполнить действие. Попробуйте ещё раз.";
}
