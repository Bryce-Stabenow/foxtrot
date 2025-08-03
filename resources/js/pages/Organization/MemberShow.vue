<template>
  <Head :title="`${member.name} - Organization Member`" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
      <!-- Header -->
      <div class="flex items-start justify-between">
        <div class="flex items-center gap-4">
          <Avatar class="size-16">
            <AvatarImage :src="member.avatar_url" :alt="member.name" />
            <AvatarFallback class="text-lg">
              {{ getInitials(member.name) }}
            </AvatarFallback>
          </Avatar>
          <div>
            <h1 class="text-3xl font-semibold">{{ member.name }}</h1>
            <p class="text-muted-foreground">{{ member.email }}</p>
            <div class="flex items-center gap-2 mt-2">
              <Badge 
                :variant="hasAdminPermissions(member.user_type) ? 'default' : 'secondary'"
                class="text-sm"
              >
                {{ member.user_type }}
              </Badge>
              <span class="text-sm text-muted-foreground">
                Member since {{ formatDate(member.created_at) }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Content Grid -->
      <div class="grid gap-6 lg:grid-cols-3">
        <!-- Member Details -->
        <div class="lg:col-span-2 space-y-6">
          <!-- Profile Information -->
          <Card>
            <CardHeader>
              <CardTitle>Profile Information</CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
              <div class="grid gap-4 md:grid-cols-2">
                <div>
                  <Label class="text-sm font-medium">Full Name</Label>
                  <p class="text-sm text-muted-foreground">{{ member.name }}</p>
                </div>
                <div>
                  <Label class="text-sm font-medium">Email Address</Label>
                  <p class="text-sm text-muted-foreground">{{ member.email }}</p>
                </div>
                <div>
                  <Label class="text-sm font-medium">Role</Label>
                  <div class="flex items-center gap-2">
                    <p class="text-sm text-muted-foreground capitalize">{{ member.user_type }}</p>
                    <Button 
                      v-if="(member.user_type === 'member') || 
                            (member.user_type === 'admin' && currentUser?.user_type === 'owner') ||
                            (member.user_type === 'owner' && currentUser?.user_type === 'owner')"
                      variant="outline" 
                      size="sm"
                      @click="openRoleModal"
                    >
                      <Icon name="edit" class="size-3 mr-1" />
                      Change
                    </Button>
                  </div>
                </div>
                <div>
                  <Label class="text-sm font-medium">Member Since</Label>
                  <p class="text-sm text-muted-foreground">{{ formatDate(member.created_at) }}</p>
                </div>
              </div>
            </CardContent>
          </Card>

          <!-- Team Assignments -->
          <Card>
            <CardHeader>
              <CardTitle>Team Assignments</CardTitle>
              <CardDescription>
                Teams this member is currently assigned to
              </CardDescription>
            </CardHeader>
            <CardContent>
              <div v-if="member.teams.length > 0" class="space-y-3">
                <div 
                  v-for="team in member.teams" 
                  :key="team.id"
                  class="flex items-center justify-between p-3 border rounded-lg"
                >
                  <div class="flex items-center gap-3">
                    <div class="size-2 bg-primary rounded-full"></div>
                    <div>
                      <p class="font-medium">{{ team.name }}</p>
                      <p class="text-sm text-muted-foreground">Team member</p>
                    </div>
                  </div>
                  <Button 
                    variant="outline" 
                    size="sm"
                    @click="removeFromTeam(team.id)"
                    :disabled="isLoading"
                  >
                    Remove
                  </Button>
                </div>
              </div>
              <div v-else class="text-center py-8">
                <div class="size-12 mx-auto mb-4 rounded-full bg-muted flex items-center justify-center">
                  <Icon name="users" class="size-6 text-muted-foreground" />
                </div>
                <h3 class="font-medium mb-2">No team assignments</h3>
                <p class="text-sm text-muted-foreground mb-4">
                  This member is not currently assigned to any teams.
                </p>
                <Button 
                  variant="outline" 
                  @click="openAssignModal"
                >
                  Assign to Team
                </Button>
              </div>
            </CardContent>
          </Card>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
          <!-- Quick Actions -->
          <Card>
            <CardHeader>
              <CardTitle>Quick Actions</CardTitle>
            </CardHeader>
            <CardContent class="space-y-3">
              <Button 
                variant="outline" 
                class="w-full justify-start"
                @click="openAssignModal"
              >
                <Icon name="users" class="size-4 mr-2" />
                Manage Teams
              </Button>
              <Button 
                variant="outline" 
                class="w-full justify-start"
                @click="openRoleModal"
              >
                <Icon name="shield" class="size-4 mr-2" />
                Change Role
              </Button>
              <Button 
                variant="outline" 
                class="w-full justify-start"
                @click="copyEmail"
              >
                <Icon v-if="!copied" name="mail" class="size-4 mr-2" />
                <Icon v-else name="check" class="size-4 mr-2" />
                Copy Email
              </Button>
              <Button 
                v-if="member.teams.length === 0 && !hasAdminPermissions(member.user_type)"
                variant="destructive" 
                class="w-full justify-start"
                @click="openDeleteModal"
              >
                <Icon name="trash" class="size-4 mr-2" />
                Delete Member
              </Button>
            </CardContent>
          </Card>

          <!-- Organization Info -->
          <Card>
            <CardHeader>
              <CardTitle>Organization</CardTitle>
            </CardHeader>
            <CardContent>
              <div class="space-y-3">
                <div>
                  <Label class="text-sm font-medium">Organization</Label>
                  <p class="text-sm text-muted-foreground">{{ organization.name }}</p>
                </div>
                <div>
                  <Label class="text-sm font-medium">Total Teams</Label>
                  <p class="text-sm text-muted-foreground">{{ teams.length }} teams</p>
                </div>
              </div>
            </CardContent>
          </Card>
        </div>
      </div>
    </div>

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

    <!-- Delete Confirmation Modal -->
    <Dialog :open="showDeleteModal" @update:open="showDeleteModal = $event">
      <DialogContent class="sm:max-w-md">
        <DialogHeader>
          <DialogTitle>Delete Member</DialogTitle>
          <DialogDescription>
            Are you sure you want to delete {{ member.name }}? This action cannot be undone.
          </DialogDescription>
        </DialogHeader>
        
        <div class="space-y-4">
          <div class="rounded-lg border border-red-100 bg-red-50 p-4 dark:border-red-200/10 dark:bg-red-700/10">
            <div class="relative space-y-0.5 text-red-600 dark:text-red-100">
              <p class="font-medium">Warning</p>
              <p class="text-sm">This will permanently delete the member and all their data.</p>
            </div>
          </div>
        </div>

        <DialogFooter>
          <Button variant="outline" @click="showDeleteModal = false">
            Cancel
          </Button>
          <Button 
            variant="destructive" 
            @click="deleteMember"
            :disabled="isDeleting"
          >
            {{ isDeleting ? 'Deleting...' : 'Delete Member' }}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>

    <!-- Role Change Modal -->
    <Dialog :open="showRoleModal" @update:open="showRoleModal = $event">
      <DialogContent class="sm:max-w-md">
        <DialogHeader>
          <DialogTitle>Change Role for {{ member.name }}</DialogTitle>
          <DialogDescription>
            Select a new role for this member. This will change their permissions within the organization.
          </DialogDescription>
        </DialogHeader>
        
        <div class="space-y-4">
          <div>
            <Label class="text-sm font-medium mb-2 block">Current Role</Label>
            <Badge 
              :variant="hasAdminPermissions(member.user_type) ? 'default' : 'secondary'"
              class="text-sm"
            >
              {{ member.user_type }}
            </Badge>
          </div>

          <div>
            <Label class="text-sm font-medium mb-2 block">New Role</Label>
            <div class="space-y-2">
              <label class="flex items-center space-x-2 cursor-pointer">
                <input 
                  type="radio" 
                  name="role" 
                  value="member" 
                  v-model="selectedRole"
                  class="rounded border-gray-300 text-primary focus:ring-primary"
                />
                <div>
                  <div class="font-medium">Member</div>
                  <div class="text-sm text-muted-foreground">Can view and participate in teams</div>
                </div>
              </label>
              <label v-if="canChangeToAdmin" class="flex items-center space-x-2 cursor-pointer">
                <input 
                  type="radio" 
                  name="role" 
                  value="admin" 
                  v-model="selectedRole"
                  class="rounded border-gray-300 text-primary focus:ring-primary"
                />
                <div>
                  <div class="font-medium">Admin</div>
                  <div class="text-sm text-muted-foreground">Can manage organization, teams, and members</div>
                </div>
              </label>
              <label v-if="canChangeToOwner" class="flex items-center space-x-2 cursor-pointer">
                <input 
                  type="radio" 
                  name="role" 
                  value="owner" 
                  v-model="selectedRole"
                  class="rounded border-gray-300 text-primary focus:ring-primary"
                />
                <div>
                  <div class="font-medium">Owner</div>
                  <div class="text-sm text-muted-foreground">Full access with all admin permissions</div>
                </div>
              </label>
            </div>
          </div>

          <div v-if="selectedRole === 'admin' || selectedRole === 'owner'" class="rounded-lg border border-amber-100 bg-amber-50 p-4 dark:border-amber-200/10 dark:bg-amber-700/10">
            <div class="relative space-y-0.5 text-amber-600 dark:text-amber-100">
              <p class="font-medium">Admin Privileges</p>
              <p class="text-sm">Admins and owners have full access to manage the organization, including adding/removing members and teams.</p>
            </div>
          </div>
        </div>

        <DialogFooter>
          <Button variant="outline" @click="showRoleModal = false">
            Cancel
          </Button>
          <Button 
            variant="default" 
            @click="updateRole"
            :disabled="isUpdatingRole || selectedRole === member.user_type"
          >
            {{ isUpdatingRole ? 'Updating...' : 'Update Role' }}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import Badge from '@/components/ui/badge/Badge.vue';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import Icon from '@/components/Icon.vue';
import { getInitials } from '@/composables/useInitials';
import { type BreadcrumbItem, type Member, type Team, type Organization, type SharedData } from '@/types';
import { hasAdminPermissions } from '@/lib/utils';

const props = defineProps<{
  member: Member;
  teams: Team[];
  organization: Organization;
}>();

const page = usePage<SharedData>();
const currentUser = computed(() => page.props.auth.user);

const showAssignModal = ref(false);
const isLoading = ref(false);
const showDeleteModal = ref(false);
const isDeleting = ref(false);
const copied = ref(false);
const showRoleModal = ref(false);
const isUpdatingRole = ref(false);
const selectedRole = ref(props.member.user_type);

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Organization',
    href: '/dashboard',
  },
  {
    title: 'Members',
    href: '/organization/members',
  },
  {
    title: props.member.name,
    href: `/organization/members/${props.member.id}`,
  },
];

const availableTeams = computed(() => {
  const memberTeamIds = props.member.teams.map(team => team.id);
  return props.teams.filter(team => !memberTeamIds.includes(team.id));
});

// Role hierarchy logic
const canChangeToAdmin = computed(() => {
  return currentUser.value?.user_type === 'owner' || 
         (currentUser.value?.user_type === 'admin' && props.member.user_type !== 'owner');
});

const canChangeToOwner = computed(() => {
  return currentUser.value?.user_type === 'owner';
});

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  });
};

const copyEmail = () => {
  navigator.clipboard.writeText(props.member.email);
  copied.value = true;
  setTimeout(() => {
    copied.value = false;
  }, 1000);
};

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
  } catch (error) {
    console.error('Failed to remove member from team:', error);
  } finally {
    isLoading.value = false;
  }
};

const openDeleteModal = () => {
  showDeleteModal.value = true;
};

const deleteMember = async () => {
  isDeleting.value = true;
  
  try {
    await router.delete(route('organization.members.destroy', {
      member: props.member.id
    }));
    
    showDeleteModal.value = false;
  } catch (error) {
    console.error('Failed to delete member:', error);
  } finally {
    isDeleting.value = false;
  }
};

const openRoleModal = () => {
  selectedRole.value = props.member.user_type;
  showRoleModal.value = true;
};

const updateRole = async () => {
  if (selectedRole.value === props.member.user_type) {
    return;
  }

  isUpdatingRole.value = true;
  
  try {
    await router.patch(route('organization.members.update-role', {
      member: props.member.id
    }), {
      user_type: selectedRole.value
    });
  } catch (error) {
    console.error('Failed to update member role:', error);
  } finally {
    isUpdatingRole.value = false;
    showRoleModal.value = false;
  }
};
</script> 