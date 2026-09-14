import type { PageProps as InertiaPageProps } from "@inertiajs/core";
import { Config } from "../../vendor/tightenco/ziggy";

export interface AuthUser {
    id: number;
    name: string;
    email: string;
}

/** Shared from AppServiceProvider, so it is on every page including errors. */
export interface SiteMeta {
    social: Record<string, string>;
    email: string | null;
    since: number;
    /** Present only when a Notes shortcut has been configured. */
    notesShortcut?: { name?: string; install?: string };
}

/**
 * Intersected with Inertia's own PageProps rather than standing alone, so
 * this always satisfies the constraint on usePage<T>. Declaring the shape
 * from scratch means every member Inertia adds to that type breaks the build
 * the next time the package resolves to a newer version.
 */
export type PageProps<
    T extends Record<string, unknown> = Record<string, unknown>
> = InertiaPageProps & T & {
    auth: {
        user: AuthUser | null;
    };
    flash: {
        success: string | null;
        error: string | null;
    };
    site: SiteMeta;
    /** Null for everybody but a signed-in admin. */
    moderation: { pending: number } | null;
    ziggy: Config & { location: string };
};
