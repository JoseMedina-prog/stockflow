import type { SharedData } from '@/types';
import { usePage } from '@inertiajs/vue3';

export function usePermissions() {
    const page = usePage<SharedData>();

    const permissions = (): string[] => page.props.auth?.permissions ?? [];

    const roles = (): string[] => page.props.auth?.roles ?? [];

    const can = (permission: string): boolean => permissions().includes(permission);

    const canAny = (perms: string[]): boolean => perms.some((p) => can(p));

    const canAll = (perms: string[]): boolean => perms.every((p) => can(p));

    const hasRole = (role: string): boolean => roles().includes(role);

    return { can, canAny, canAll, hasRole, permissions, roles };
}
