export type DashboardStats = {
    myReportsCount?: number;
    pendingCorrectionsCount?: number;
    teamReportsCount?: number;
    supervisedEmployeesCount?: number;
    questionnairesCount?: number;
    employeesCount?: number;
    supervisorsCount?: number;
    totalReportsCount?: number;
};

export type RecentReport = {
    id: number;
    questionnaire_title: string;
    submitted_at: string | null;
    respondent_name?: string;
};

export type PendingCorrection = {
    response_id: number;
    questionnaire_title: string;
    questionnaire_id: number;
    row_identifier: string | null;
    reviewed_at: string | null;
};

export type LastReport = {
    questionnaire_title: string;
    submitted_at: string | null;
} | null;

export type DashboardProps = {
    stats: DashboardStats;
    recentReports: RecentReport[];
    pendingCorrections: PendingCorrection[];
    lastReport: LastReport;
    availableQuestionnairesCount?: number;
    canAccessQuestionnaires: boolean;
    canAccessEmployees: boolean;
    canExportReports: boolean;
};
