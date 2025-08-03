<template>
  <Head title="Organization Members" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-semibold">Organization Members</h1>
          <p class="text-muted-foreground">{{ organization.name }} • {{ members.length }} members</p>
        </div>
      </div>

      <!-- Team Sections -->
      <div class="space-y-12">
        <!-- Members grouped by team -->
        <div v-for="teamGroup in organizedMembers" :key="teamGroup.teamId" class="space-y-4">
          <div class="flex items-center gap-2">
            <h2 class="text-lg font-medium">{{ teamGroup.teamName }}</h2>
            <Badge variant="secondary" class="text-xs">{{ teamGroup.members.length }} members</Badge>
          </div>
          <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
            <MemberCard 
              v-for="member in teamGroup.members" 
              :key="member.id" 
              :member="member"
              :teams="teams"
              :current-user="currentUser"
            />
          </div>
        </div>

        <!-- Unassigned members section -->
        <div v-if="unassignedMembers.length > 0" class="space-y-4">
          <div class="flex items-center gap-2">
            <h2 class="text-lg font-medium text-muted-foreground">Unassigned Members</h2>
            <Badge variant="outline" class="text-xs">{{ unassignedMembers.length }} members</Badge>
          </div>
          <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
            <MemberCard 
              v-for="member in unassignedMembers" 
              :key="member.id" 
              :member="member"
              :teams="teams"
              :current-user="currentUser"
            />
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import MemberCard from '@/components/MemberCard.vue';
import Badge from '@/components/ui/badge/Badge.vue';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { computed } from 'vue';

// TODO move to types.ts
interface Member {
  id: number;
  name: string;
  email: string;
  avatar_url: string;
  user_type: 'admin' | 'member'; // TODO: use UserType enum
  teams: Array<{
    id: number;
    name: string;
  }>;
}

interface Team {
  id: number;
  name: string;
}

interface Organization {
  id: number;
  name: string;
}

interface TeamGroup {
  teamId: number;
  teamName: string;
  members: Member[];
}

const props = defineProps<{
  members: Member[];
  teams: Team[];
  organization: Organization;
}>();

const page = usePage<SharedData>();
const currentUser = computed(() => page.props.auth.user);

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Organization',
    href: '/dashboard',
  },
  {
    title: 'Members',
    href: '/organization/members',
  },
];

// Group members by their first team (for members in multiple teams, they'll appear in the first team's section)
const organizedMembers = computed(() => {
  const teamGroups: TeamGroup[] = [];
  
  // Create a map to track which members have been assigned to a team group
  const assignedMembers = new Set<number>();
  
  // Group members by their first team
  props.members.forEach(member => {
    if (member.teams.length > 0) {
      const firstTeam = member.teams[0];
      let teamGroup = teamGroups.find(group => group.teamId === firstTeam.id);
      
      if (!teamGroup) {
        teamGroup = {
          teamId: firstTeam.id,
          teamName: firstTeam.name,
          members: []
        };
        teamGroups.push(teamGroup);
      }
      
      teamGroup.members.push(member);
      assignedMembers.add(member.id);
    }
  });
  
  // Sort team groups by team name
  teamGroups.sort((a, b) => a.teamName.localeCompare(b.teamName));
  
  // Sort members within each team by name
  teamGroups.forEach(group => {
    group.members.sort((a, b) => a.name.localeCompare(b.name));
  });
  
  return teamGroups;
});

// Get unassigned members (those not in any team)
const unassignedMembers = computed(() => {
  return props.members
    .filter(member => member.teams.length === 0)
    .sort((a, b) => a.name.localeCompare(b.name));
});
</script> 