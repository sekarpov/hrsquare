import { http } from "./http";
import type { Candidate, CandidateInput, Filters, Page } from "../types";
export const candidatesApi = {
    async list(params: Filters) {
        return (await http.get<Page<Candidate>>("/candidates", { params }))
            .data;
    },
    async get(id: number) {
        return (await http.get<{ data: Candidate }>(`/candidates/${id}`)).data
            .data;
    },
    async save(data: CandidateInput, id?: number) {
        return (
            await (id
                ? http.put<{ data: Candidate }>(`/candidates/${id}`, data)
                : http.post<{ data: Candidate }>("/candidates", data))
        ).data.data;
    },
    async remove(id: number) {
        await http.delete(`/candidates/${id}`);
    },
};
