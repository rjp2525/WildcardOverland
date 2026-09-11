import { Config } from "../../vendor/tightenco/ziggy";

export interface AuthUser {
    id: number;
    name: string;
    email: string;
}

export type PageProps<
    T extends Record<string, unknown> = Record<string, unknown>
> = T & {
    auth: {
        user: AuthUser | null;
    };
    flash: {
        success: string | null;
        error: string | null;
    };
    ziggy: Config & { location: string };
};
