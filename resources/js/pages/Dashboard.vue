<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, usePage, Link } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { getInitials } from '@/composables/useInitials';
import { computed } from 'vue';

interface Team {
    id: number;
    name: string;
    members: Array<{
        id: number;
        name: string;
        email: string;
        avatar_url: string;
    }>;
}

defineProps<{
    teams: Team[];
}>();

const page = usePage<SharedData>();
const currentUser = computed(() => page.props.auth.user);

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <h1 class="text-2xl font-semibold">My Teams</h1>
            
            <div class="grid gap-4 md:grid-cols-2">
                <Link 
                    v-for="team in teams" 
                    :key="team.id" 
                    :href="route('teams.show', team.id)"
                >
                    <Card class="overflow-hidden transition-all duration-200 hover:scale-[1.02] hover:shadow-lg min-h-full">
                        <CardHeader>
                            <CardTitle>{{ team.name }}</CardTitle>
                            <CardDescription>{{ team.members.length }} members</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="flex flex-wrap gap-2">
                                <div 
                                    v-for="user in team.members" 
                                    :key="user.id" 
                                    class="flex items-center gap-2 rounded-full px-3 py-1"
                                    :class="user.id === currentUser.id ? 'bg-primary text-primary-foreground font-medium shadow-sm' : 'bg-muted'"
                                >
                                    <Avatar class="size-6">
                                        <AvatarImage :src="user.avatar_url" :alt="user.name" />
                                        <AvatarFallback class="text-xs">
                                            {{ getInitials(user.name) }}
                                        </AvatarFallback>
                                    </Avatar>
                                    <span class="text-sm">{{ user.id === currentUser.id ? 'You' : user.name }}</span>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </Link>
            </div>
        </div>
    </AppLayout>
</template>
