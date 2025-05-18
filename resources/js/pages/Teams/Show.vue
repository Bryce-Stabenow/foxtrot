<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, usePage } from '@inertiajs/vue3';
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
    team: Team;
}>();

const page = usePage<SharedData>();
const currentUser = computed(() => page.props.auth.user);

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Teams',
        href: '/teams',
    },
    {
        title: 'Team Details',
        href: '#',
    },
];
</script>

<template>
    <Head :title="team.name" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <Card>
                <CardHeader>
                    <CardTitle>{{ team.name }}</CardTitle>
                    <CardDescription>{{ team.members.length }} members</CardDescription>
                </CardHeader>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Team Members</CardTitle>
                    <CardDescription>A list of all members in this team</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="divide-y">
                        <div 
                            v-for="user in team.members" 
                            :key="user.id"
                            class="flex items-center gap-4 py-4 first:pt-0 last:pb-0"
                        >
                            <Avatar class="size-10">
                                <AvatarImage :src="user.avatar_url" :alt="user.name" />
                                <AvatarFallback class="text-sm">
                                    {{ getInitials(user.name) }}
                                </AvatarFallback>
                            </Avatar>
                            <div class="flex flex-col">
                                <div class="flex items-center gap-2">
                                    <span class="font-medium">{{ user.name }}</span>
                                    <span 
                                        v-if="user.id === currentUser.id"
                                        class="rounded-full bg-primary/10 px-2 py-0.5 text-xs font-medium text-primary"
                                    >
                                        You
                                    </span>
                                </div>
                                <span class="text-sm text-muted-foreground">{{ user.email }}</span>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template> 