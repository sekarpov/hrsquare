import { http } from "./http";
import type { User, UserInput, Filters, Page } from "../types";
export const usersApi = {
    async list(params: Filters) {
        return (await http.get<Page<User>>("/users", { params })).data;
    },
    async options(role: "managers" | "recruiters", search = "") {
        return (
            await http.get<Page<User>>(`/users/${role}`, {
                params: { search, perPage: 100 },
            })
        ).data.data;
    },
    async save(data: UserInput, id?: number) {
        return (
            await (id
                ? http.put<{ data: User }>(`/users/${id}`, data)
                : http.post<{ data: User }>("/users", data))
        ).data.data;
    },
    async remove(id: number) {
        await http.delete(`/users/${id}`);
    },
};
