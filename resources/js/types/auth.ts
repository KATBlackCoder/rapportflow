export type User = {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    employee?: {
        id: number;
        position: string;
        department: string | null;
    } | null;
    [key: string]: unknown;
};

export type Auth = {
    user: User | null;
};
