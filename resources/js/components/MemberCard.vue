<template>
  <Card class="overflow-hidden">
    <CardHeader>
      <div class="flex items-center gap-3">
        <Avatar class="size-10">
          <AvatarImage :src="member.avatar_url" :alt="member.name" />
          <AvatarFallback>
            {{ getInitials(member.name) }}
          </AvatarFallback>
        </Avatar>
        <div class="flex-1 min-w-0">
          <CardTitle class="text-base truncate">{{ member.name }}</CardTitle>
          <CardDescription class="truncate">{{ member.email }}</CardDescription>
        </div>
        <Badge 
          :variant="member.user_type === 'admin' ? 'default' : 'secondary'"
          class="text-xs"
        >
          {{ member.user_type }}
        </Badge>
      </div>
    </CardHeader>
    <CardContent>
      <div class="space-y-3">
        <div>
          <h4 class="text-sm font-medium mb-2">Team Assignments</h4>
          <div v-if="member.teams.length > 0" class="flex flex-wrap gap-1">
            <Badge 
              v-for="team in member.teams" 
              :key="team.id"
              variant="outline"
              class="text-xs"
            >
              {{ team.name }}
            </Badge>
          </div>
          <p v-else class="text-sm text-muted-foreground">No team assignments</p>
        </div>
        
        <div class="flex gap-2">
          <Button 
            variant="outline" 
            size="sm" 
            class="flex-1"
            @click="openAssignModal"
          >
            Manage Teams
          </Button>
        </div>
      </div>
    </CardContent>

    <!-- Team Assignment Modal -->
    <Dialog :open="showAssignModal" @update:open="showAssignModal = $event">
      <DialogContent class="sm:max-w-md">
        <DialogHeader>
          <DialogTitle>Manage Teams for {{ member.name }}</DialogTitle>
          <DialogDescription>
            Assign or remove team memberships for this user.
          </DialogDescription>
        </DialogHeader>
        
        <div class="space-y-4">
          <div>
            <h4 class="text-sm font-medium mb-2">Current Teams</h4>
            <div v-if="member.teams.length > 0" class="space-y-2">
              <div 
                v-for="team in member.teams" 
                :key="team.id"
                class="flex items-center justify-between p-2 border rounded-md"
              >
                <span class="text-sm">{{ team.name }}</span>
                <Button 
                  variant="destructive" 
                  size="sm"
                  @click="removeFromTeam(team.id)"
                  :disabled="isLoading"
                >
                  Remove
                </Button>
              </div>
            </div>
            <p v-else class="text-sm text-muted-foreground">No team assignments</p>
          </div>

          <div>
            <h4 class="text-sm font-medium mb-2">Available Teams</h4>
            <div class="space-y-2">
              <div 
                v-for="team in availableTeams" 
                :key="team.id"
                class="flex items-center justify-between p-2 border rounded-md"
              >
                <span class="text-sm">{{ team.name }}</span>
                <Button 
                  variant="default" 
                  size="sm"
                  @click="assignToTeam(team.id)"
                  :disabled="isLoading"
                >
                  Add
                </Button>
              </div>
            </div>
          </div>
        </div>

        <DialogFooter>
          <Button variant="outline" @click="showAssignModal = false">
            Close
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </Card>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import Badge from './ui/badge/Badge.vue';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { getInitials } from '@/composables/useInitials';
import { router } from '@inertiajs/vue3';

interface Member {
  id: number;
  name: string;
  email: string;
  avatar_url: string;
  user_type: 'admin' | 'member';
  teams: Array<{
    id: number;
    name: string;
  }>;
}

interface Team {
  id: number;
  name: string;
}

interface CurrentUser {
  id: number;
  name: string;
  email: string;
  user_type: 'admin' | 'member';
}

const props = defineProps<{
  member: Member;
  teams: Team[];
  currentUser: CurrentUser;
}>();

const showAssignModal = ref(false);
const isLoading = ref(false);

const availableTeams = computed(() => {
  const memberTeamIds = props.member.teams.map(team => team.id);
  return props.teams.filter(team => !memberTeamIds.includes(team.id));
});

const openAssignModal = () => {
  showAssignModal.value = true;
};

const assignToTeam = async (teamId: number) => {
  isLoading.value = true;
  
  try {
    await router.post(route('organization.members.assign-to-team', {
      member: props.member.id,
      team: teamId
    }));
    
    // The page will be refreshed automatically by Inertia
    showAssignModal.value = false;
  } catch (error) {
    console.error('Failed to assign member to team:', error);
  } finally {
    isLoading.value = false;
  }
};

const removeFromTeam = async (teamId: number) => {
  isLoading.value = true;
  
  try {
    await router.delete(route('organization.members.remove-from-team', {
      member: props.member.id,
      team: teamId
    }));
    
    // The page will be refreshed automatically by Inertia
    showAssignModal.value = false;
  } catch (error) {
    console.error('Failed to remove member from team:', error);
  } finally {
    isLoading.value = false;
  }
};
</script> 