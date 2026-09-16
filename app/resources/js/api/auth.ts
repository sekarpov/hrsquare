import { http } from "./http";
import type { User } from "../types";
export const authApi = {
    async me() {
        return (await http.get<{ data: User }>("/me")).data.data;
    },
    async login(login: string, password: string) {
        await http.get("/csrf");
        return (await http.post<{ data: User }>("/login", { login, password }))
            .data.data;
    },
    async changePassword(data: {
        currentPassword?: string;
        password: string;
        passwordConfirmation: string;
    }) {
        return (await http.put<{ data: User }>("/me/password", data)).data.data;
    },
    async logout() {
        await http.post("/logout");
    },
};
