<template>
    <Head :title="team.name" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-semibold">{{ team.name }}</h1>
                    <p class="mt-1 text-sm text-muted-foreground">
                        {{ team.members.length }} members
                    </p>
                </div>
            </div>

            <!-- Recent Check-ins -->
            <Card>
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <div>
                            <CardTitle>Recent Check-ins</CardTitle>
                            <CardDescription>Recently completed check-ins for this team</CardDescription>
                        </div>
                        <Button 
                            variant="outline" 
                            size="sm"
                            @click="viewAllCheckIns"
                        >
                            View All Check-ins
                        </Button>
                    </div>
                </CardHeader>
                <CardContent>
                    <div v-if="recentCheckIns.length > 0" class="divide-y">
                        <CheckInListItem
                            v-for="checkIn in recentCheckIns"
                            :key="checkIn.id"
                            :check-in="checkIn"
                            :can-edit="canEdit"
                            :format-date="formatDate"
                        />
                    </div>
                    <div v-else class="text-center py-8">
                        <p class="text-sm text-muted-foreground">No completed check-ins yet</p>
                    </div>
                </CardContent>
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
                            <div class="flex flex-col flex-1">
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
                            <Button 
                                v-if="isAdmin && user.id !== currentUser.id"
                                variant="outline" 
                                size="sm"
                                @click="viewUserDetails(user.id)"
                            >
                                View Details
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template> 

<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type SharedData, type TeamWithMembers, type CheckIn } from '@/types';
import { Head, usePage, router } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import CheckInListItem from '@/components/CheckInListItem.vue';
import { getInitials } from '@/composables/useInitials';
import { computed } from 'vue';
import { hasAdminPermissions } from '@/lib/utils';

const props = defineProps<{
    team: TeamWithMembers;
    recentCheckIns: CheckIn[];
}>();

const page = usePage<SharedData>();
const currentUser = computed(() => page.props.auth.user);

const isAdmin = computed(() => hasAdminPermissions(currentUser.value.user_type));

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

const formatDate = (date: string | null) => {
    if (!date) return '';
    return new Date(date).toLocaleDateString();
};

const canEdit = (checkIn: CheckIn) => {
    // TODO This should check user permissions - for now, allow admin users to edit
    return isAdmin.value;
};

const viewUserDetails = (userId: number) => {
    router.visit(route('organization.members.show', { member: userId }));
};

const viewAllCheckIns = () => {
    router.visit(route('check-ins.index', { team_id: props.team.id }));
};

const viewCheckIn = (checkInId: number) => {
    router.visit(route('check-ins.show', { checkIn: checkInId }));
};
</script>