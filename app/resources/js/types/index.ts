export type Score = 1 | 2 | 3 | 4;
export type UserRole = "ADMIN" | "RECRUITER" | "MANAGER";
export type Level = "LOW" | "MEDIUM" | "HIGH";
export type Cell = "M1" | "S1" | "B1" | "M2" | "S2" | "B2" | "M3" | "S3" | "B3";
export type CalibrationSignal = "NONE" | "ALIGNED" | "NEEDS_CALIBRATION";
export type MainRisk =
    | "NONE"
    | "DELIVERY"
    | "LEARNING"
    | "ADAPTABILITY"
    | "OWNERSHIP"
    | "MOTIVATION";
export type CandidateStatus = "ACTIVE" | "HIRED" | "REJECTED";
export interface User {
    id: number;
    fullName: string;
    login: string;
    role: UserRole;
    isActive: boolean;
    mustChangePassword: boolean;
    createdAt: string;
}
export interface UserInput {
    fullName: string;
    login: string;
    password: string;
    role: UserRole;
    isActive: boolean;
}
export type CriterionKey =
    | "taskScale"
    | "resultImpact"
    | "personalContribution"
    | "learningAgility"
    | "adaptability"
    | "initiative";
export type AssessmentInput = Partial<
    Record<`${CriterionKey}Score`, Score | null> &
        Record<`${CriterionKey}Evidence`, string | null>
> & {
    calibrationSignal?: CalibrationSignal;
    mainRisk?: MainRisk;
    finalComment?: string | null;
};
export type Assessment = AssessmentInput & {
    id: number;
    candidateId: number;
    evaluatorId: number;
    evaluator: User;
    status: "DRAFT" | "COMPLETED";
    resultAverage: number | null;
    resultLevel: Level | null;
    potentialAverage: number | null;
    potentialLevel: Level | null;
    nineBoxCell: Cell | null;
    completedAt: string | null;
    createdAt: string;
    updatedAt: string;
};
export interface Candidate {
    id: number;
    fullName: string;
    position: string;
    city: string | null;
    company: string | null;
    division: string | null;
    project: string | null;
    status: CandidateStatus;
    createdBy: number;
    hiringManagers: User[];
    recruiters: User[];
    currentAssessment: Assessment | null;
    createdAt: string;
    updatedAt: string;
}
export interface CandidateInput {
    fullName: string;
    position: string;
    city: string | null;
    company: string | null;
    division: string | null;
    project: string | null;
    status: CandidateStatus;
    hiringManagerIds: number[];
    recruiterIds: number[];
}
export interface Page<T> {
    data: T[];
    meta: {
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    links: unknown;
}
export interface Criterion {
    group: "RESULT" | "POTENTIAL";
    title: string;
    question: string;
    clarification: string[];
    strongAnswer: string;
    placeholder: string;
    anchors: string[];
}
export interface Methodology {
    score_min: number;
    score_max: number;
    medium_threshold: number;
    high_threshold: number;
    criteria: Record<CriterionKey, Criterion>;
}
export type FieldErrors = Record<string, string[]>;
export type Filters = Record<string, string | number | null | undefined>;

export interface CandidateFilters extends Filters {
    search: string | null;
    position: string | null;
    city: string | null;
    company: string | null;
    division: string | null;
    project: string | null;
    status: CandidateStatus | null;
    resultLevel: Level | null;
    potentialLevel: Level | null;
    nineBoxCell: Cell | null;
    managerId: number | null;
    recruiterId: number | null;
    sort: string;
    direction: string;
}
