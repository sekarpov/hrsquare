import { http } from "./http";
import type { City } from "../types";

export const citiesApi = {
    async list() {
        return (await http.get<{ data: City[] }>("/cities")).data.data;
    },
    async create(name: string) {
        return (await http.post<{ data: City }>("/cities", { name })).data.data;
    },
};
