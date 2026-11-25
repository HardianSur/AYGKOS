export type User = {
    id: string;
    name: string;
    email: string;
    avatar?: string;
    whatsapp_number: string | null;
    whatsapp_verified_at: string | null;
    email_verified_at: string | null;
    role: string | null;
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
};

export type Auth = {
    user: User;
};
