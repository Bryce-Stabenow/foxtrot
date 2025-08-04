import type { PageProps } from '@inertiajs/core';
import type { LucideIcon } from 'lucide-vue-next';
import type { Config } from 'ziggy-js';
import { UserType, InvitationStatus } from './enums';

export interface Auth {
    user: User;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavItem {
    title: string;
    href: string;
    icon?: LucideIcon;
    isActive?: boolean;
}

export interface SharedData extends PageProps {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    ziggy: Config & { location: string };
    sidebarOpen: boolean;
}

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    user_type: UserType;
    created_at: string;
    updated_at: string;
}

export interface Member {
    id: number;
    name: string;
    email: string;
    avatar_url: string;
    user_type: UserType;
    created_at: string;
    teams: Array<{
        id: number;
        name: string;
    }>;
}

export interface Team {
    id: number;
    name: string;
}

export interface TeamWithMembers {
    id: number;
    name: string;
    members: Array<{
        id: number;
        name: string;
        email: string;
        avatar_url: string;
    }>;
}

export interface CurrentUser {
    id: number;
    name: string;
    email: string;
    user_type: UserType;
}

export interface Invitation {
    id: number;
    email: string;
    token: string;
    expires_at: string;
    organization: {
        name: string;
    };
    invited_by: {
        name: string;
    };
}

export interface InvitationWithStatus {
    id: number;
    email: string;
    status: InvitationStatus;
    created_at: string;
    expires_at: string;
    invited_by: {
        name: string;
    };
    organization: {
        name: string;
    };
}

export interface Organization {
    id: number;
    name: string;
}

export interface TeamGroup {
    teamId: number;
    teamName: string;
    members: Member[];
}

export interface CheckIn {
    id: number;
    title: string;
    description: string;
    status: string;
    scheduled_date: string;
    completed_at: string | null;
    created_at: string;
    notes: string | null;
    is_overdue: boolean;
    team_id: number;
    assigned_user_id: number;
    assigned_user: {
        name: string;
    };
    team: {
        name: string;
    };
    created_by: {
        name: string;
    };
}

export interface Stats {
    total: number;
    pending: number;
    in_progress: number;
    completed: number;
    overdue: number;
    completion_rate: number;
}

export interface Filters {
    status: string;
    team_id: string;
    search: string;
    sort_by: string;
    sort_direction: string;
}

export type BreadcrumbItemType = BreadcrumbItem;
