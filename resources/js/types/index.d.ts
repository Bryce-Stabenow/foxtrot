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

export type BreadcrumbItemType = BreadcrumbItem;
