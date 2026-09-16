import { http } from "./http";
import type { Assessment, AssessmentInput, Methodology, Page } from "../types";
export const assessmentsApi = {
    async list(candidateId: number, page = 1) {
        return (
            await http.get<Page<Assessment>>(
                `/candidates/${candidateId}/assessments`,
                { params: { page } },
            )
        ).data;
    },
    async create(candidateId: number) {
        return (
            await http.post<{ data: Assessment }>(
                `/candidates/${candidateId}/assessments`,
                {},
            )
        ).data.data;
    },
    async get(id: number) {
        return (await http.get<{ data: Assessment }>(`/assessments/${id}`)).data
            .data;
    },
    async save(id: number, data: AssessmentInput) {
        return (
            await http.put<{ data: Assessment }>(`/assessments/${id}`, data)
        ).data.data;
    },
    async complete(id: number, data: AssessmentInput) {
        return (
            await http.post<{ data: Assessment }>(
                `/assessments/${id}/complete`,
                data,
            )
        ).data.data;
    },
    async preview(data: AssessmentInput) {
        return (
            await http.post<{ data: Assessment }>("/assessment-preview", data)
        ).data.data;
    },
    async methodology() {
        return (
            await http.get<{ data: Methodology }>("/assessment-methodology")
        ).data.data;
    },
};
