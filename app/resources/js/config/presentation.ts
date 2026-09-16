import type { CandidateStatus, Level, UserRole } from "../types";
export const candidateStatuses: { label: string; value: CandidateStatus }[] = [
    { label: "Активный", value: "ACTIVE" },
    { label: "Нанят", value: "HIRED" },
    { label: "Отклонён", value: "REJECTED" },
];
export const levelLabels: Record<Level, string> = {
    LOW: "Низкий",
    MEDIUM: "Средний",
    HIGH: "Высокий",
};
export const formatDate = (value?: string | null) =>
    value
        ? new Date(value).toLocaleString("ru-RU", {
              dateStyle: "medium",
              timeStyle: "short",
          })
        : "—";

export const roleLabels: Record<UserRole, string> = {
    ADMIN: "Администратор",
    RECRUITER: "Рекрутер",
    MANAGER: "Менеджер",
};
