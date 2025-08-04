<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem, type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { LayoutGrid, Mail, Users, CheckSquare } from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';
import { hasAdminPermissions } from '@/lib/utils';

const page = usePage<SharedData>();

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
        icon: LayoutGrid,
    },
    {
        title: 'Check-ins',
        href: '/check-ins',
        icon: CheckSquare,
    },
];

// Add invitations link for admin users
if (page.props.auth.user?.user_type && hasAdminPermissions(page.props.auth.user.user_type)) {
    mainNavItems.push({
        title: 'Members',
        href: '/organization/members',
        icon: Users,
    });
    mainNavItems.push({
        title: 'Invitations',
        href: '/invitations',
        icon: Mail,
    });
}

const footerNavItems: NavItem[] = [];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="route('dashboard')">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
