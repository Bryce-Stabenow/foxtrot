<template>
  <Head title="Invitations" />
  <AppLayout>
    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold">Invitations</h1>
        <Button @click="showSendForm = true">
          Send Invitation
        </Button>
      </div>

      <!-- Pending Invitations -->
      <Card>
        <CardHeader>
          <CardTitle>Pending Invitations</CardTitle>
          <CardDescription>
            Invitations that are waiting for a response.
          </CardDescription>
        </CardHeader>
        <CardContent>
          <div v-if="pendingInvitations.length === 0" class="text-center text-muted-foreground">
            <p>No pending invitations.</p>
          </div>
          
          <div v-else class="space-y-4">
            <div
              v-for="invitation in pendingInvitations"
              :key="invitation.id"
              class="flex items-center justify-between p-4 border rounded-lg"
            >
              <div class="flex-1">
                <div class="flex items-center space-x-3">
                  <div class="w-10 h-10 bg-muted rounded-full flex items-center justify-center">
                    <span class="text-sm font-medium">
                      {{ invitation.email.charAt(0).toUpperCase() }}
                    </span>
                  </div>
                  <div>
                    <p class="font-medium">{{ invitation.email }}</p>
                    <p class="text-sm text-muted-foreground">
                      Sent by {{ invitation.invited_by.name }} on {{ formatDate(invitation.created_at) }}
                    </p>
                  </div>
                </div>
              </div>
              
              <div class="flex items-center space-x-2">
                <div class="text-sm">
                  <span class="text-blue-600 font-medium">Pending</span>
                </div>
                <DropdownMenu>
                  <DropdownMenuTrigger as-child>
                    <Button variant="ghost" size="sm">
                      <Icon name="MoreVertical" class="h-4 w-4" />
                    </Button>
                  </DropdownMenuTrigger>
                  <DropdownMenuContent>
                    <DropdownMenuItem @click="resendInvitation(invitation)">
                      <Icon name="RefreshCw" class="h-4 w-4 mr-2" />
                      Resend
                    </DropdownMenuItem>
                    <DropdownMenuSeparator />
                    <DropdownMenuItem @click="cancelInvitation(invitation)" class="text-destructive">
                      <Icon name="Trash2" class="h-4 w-4 mr-2" />
                      Cancel
                    </DropdownMenuItem>
                  </DropdownMenuContent>
                </DropdownMenu>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Accepted Invitations -->
      <Card class="my-8">
        <CardHeader>
          <CardTitle>Accepted Invitations</CardTitle>
          <CardDescription>
            Invitations that have been accepted by the recipient.
          </CardDescription>
        </CardHeader>
        <CardContent>
          <div v-if="acceptedInvitations.length === 0" class="text-center text-muted-foreground">
            <p>No accepted invitations.</p>
          </div>
          
          <div v-else class="space-y-4">
            <div
              v-for="invitation in acceptedInvitations"
              :key="invitation.id"
              class="flex items-center justify-between p-4 border rounded-lg"
            >
              <div class="flex-1">
                <div class="flex items-center space-x-3">
                  <div class="w-10 h-10 bg-muted rounded-full flex items-center justify-center">
                    <span class="text-sm font-medium">
                      {{ invitation.email.charAt(0).toUpperCase() }}
                    </span>
                  </div>
                  <div>
                    <p class="font-medium">{{ invitation.email }}</p>
                    <p class="text-sm text-muted-foreground">
                      Sent by {{ invitation.invited_by.name }} on {{ formatDate(invitation.created_at) }}
                    </p>
                  </div>
                </div>
              </div>
              
              <div class="flex items-center space-x-2">
                <div class="text-sm">
                  <span class="text-green-600 font-medium">Accepted</span>
                </div>
                <div class="flex items-center justify-center w-8 h-8">
                  <Icon name="CheckCircle" class="h-5 w-5 text-green-600" />
                </div>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Expired Invitations -->
      <Card>
        <CardHeader>
          <CardTitle>Expired Invitations</CardTitle>
          <CardDescription>
            Invitations that have expired and can no longer be accepted.
          </CardDescription>
        </CardHeader>
        <CardContent>
          <div v-if="expiredInvitations.length === 0" class="text-center text-muted-foreground">
            <p>No expired invitations.</p>
          </div>
          
          <div v-else class="space-y-4">
            <div
              v-for="invitation in expiredInvitations"
              :key="invitation.id"
              class="flex items-center justify-between p-4 border rounded-lg"
            >
              <div class="flex-1">
                <div class="flex items-center space-x-3">
                  <div class="w-10 h-10 bg-muted rounded-full flex items-center justify-center">
                    <span class="text-sm font-medium">
                      {{ invitation.email.charAt(0).toUpperCase() }}
                    </span>
                  </div>
                  <div>
                    <p class="font-medium">{{ invitation.email }}</p>
                    <p class="text-sm text-muted-foreground">
                      Sent by {{ invitation.invited_by.name }} on {{ formatDate(invitation.created_at) }}
                    </p>
                  </div>
                </div>
              </div>
              
              <div class="flex items-center space-x-2">
                <div class="text-sm">
                  <span class="text-red-600 font-medium">Expired</span>
                </div>
                <DropdownMenu>
                  <DropdownMenuTrigger as-child>
                    <Button variant="ghost" size="sm">
                      <Icon name="MoreVertical" class="h-4 w-4" />
                    </Button>
                  </DropdownMenuTrigger>
                  <DropdownMenuContent>
                    <DropdownMenuItem @click="resendInvitation(invitation)">
                      <Icon name="RefreshCw" class="h-4 w-4 mr-2" />
                      Resend
                    </DropdownMenuItem>
                    <DropdownMenuSeparator />
                    <DropdownMenuItem @click="cancelInvitation(invitation)" class="text-destructive">
                      <Icon name="Trash2" class="h-4 w-4 mr-2" />
                      Cancel
                    </DropdownMenuItem>
                  </DropdownMenuContent>
                </DropdownMenu>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>

    <!-- Send Invitation Dialog -->
    <Dialog :open="showSendForm" @update:open="showSendForm = false">
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Send Invitation</DialogTitle>
          <DialogDescription>
            Send an invitation to join your organization. The recipient will receive an email with a secure link to create their account.
          </DialogDescription>
        </DialogHeader>
        
        <form @submit.prevent="sendInvitation" class="space-y-4">
          <div>
            <Label for="email" class="mb-2">Email Address</Label>
            <Input
              id="email"
              v-model="form.email"
              type="email"
              placeholder="colleague@example.com"
              :class="{ 'border-destructive': form.errors.email }"
            />
            <InputError v-if="form.errors.email" :message="form.errors.email" />
          </div>
          
          <DialogFooter>
            <Button type="button" variant="outline" @click="showSendForm = false">
              Cancel
            </Button>
            <Button type="submit" :disabled="form.processing">
              Send Invitation
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>

    <!-- Cancel Invitation Dialog -->
    <Dialog :open="showCancelDialog" @update:open="showCancelDialog = false">
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Cancel Invitation</DialogTitle>
          <DialogDescription>
            Are you sure you want to cancel this invitation? This action cannot be undone.
          </DialogDescription>
        </DialogHeader>
        
        <div class="space-y-4">
          <div v-if="invitationToCancel" class="p-4 border rounded-lg bg-muted/50">
            <div class="flex items-center space-x-3">
              <div class="w-8 h-8 bg-muted rounded-full flex items-center justify-center">
                <span class="text-sm font-medium">
                  {{ invitationToCancel.email.charAt(0).toUpperCase() }}
                </span>
              </div>
              <div>
                <p class="font-medium">{{ invitationToCancel.email }}</p>
                <p class="text-sm text-muted-foreground">
                  Sent on {{ formatDate(invitationToCancel.created_at) }}
                </p>
              </div>
            </div>
          </div>
          
          <DialogFooter>
            <Button type="button" variant="outline" @click="showCancelDialog = false">
              Keep Invitation
            </Button>
            <Button type="button" variant="destructive" @click="confirmCancelInvitation" :disabled="canceling">
              Cancel Invitation
            </Button>
          </DialogFooter>
        </div>
      </DialogContent>
    </Dialog>
    
    <!-- Toast Notification -->
    <Toast
      v-model="showToast"
      :message="toastMessage"
      :type="toastType"
    />
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, computed, type Ref } from 'vue'
import { useForm, router, Head } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import Button from '@/components/ui/button/Button.vue'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog'
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu'
import Input from '@/components/ui/input/Input.vue'
import Label from '@/components/ui/label/Label.vue'
import InputError from '@/components/InputError.vue'
import Icon from '@/components/Icon.vue'
import Toast from '@/components/Toast.vue'
import { type InvitationWithStatus } from '@/types'
import { InvitationStatus } from '@/types/enums'

interface Props {
  invitations: InvitationWithStatus[]
}

const props = defineProps<Props>()

const showSendForm = ref(false)
const showToast = ref(false)
const toastMessage = ref('')
const toastType = ref<'success' | 'error'>('success')

const form = useForm({
  email: '',
})

// Computed properties to split invitations by status
const pendingInvitations = computed(() => {
  return props.invitations.filter(invitation => invitation.status === InvitationStatus.PENDING)
})

const acceptedInvitations = computed(() => {
  return props.invitations.filter(invitation => invitation.status === InvitationStatus.ACCEPTED)
})

const expiredInvitations = computed(() => {
  return props.invitations.filter(invitation => invitation.status === InvitationStatus.EXPIRED)
})

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  })
}

const sendInvitation = () => {
  form.post(route('invitations.store'), {
    onSuccess: () => {
      form.reset()
      showSendForm.value = false
    },
  })
}

const resendInvitation = (invitation: InvitationWithStatus) => {
  router.post(route('invitations.resend', invitation.id), {}, {
    onSuccess: () => {
      toastMessage.value = 'Invitation resent successfully!'
      toastType.value = 'success'
      showToast.value = true
    },
    onError: () => {
      toastMessage.value = 'Failed to resend invitation. Please try again.'
      toastType.value = 'error'
      showToast.value = true
    }
  })
}

const cancelInvitation = (invitation: InvitationWithStatus) => {
  invitationToCancel.value = invitation
  showCancelDialog.value = true
}

const showCancelDialog = ref(false)
const invitationToCancel: Ref<InvitationWithStatus | null> = ref(null)
const canceling = ref(false)

const confirmCancelInvitation = () => {
  if (!invitationToCancel.value) return

  canceling.value = true
  router.delete(route('invitations.destroy', invitationToCancel.value.id), {
    onSuccess: () => {
      toastMessage.value = 'Invitation cancelled successfully!'
      toastType.value = 'success'
      showToast.value = true
      showCancelDialog.value = false
      invitationToCancel.value = null
      canceling.value = false
    },
    onError: () => {
      toastMessage.value = 'Failed to cancel invitation. Please try again.'
      toastType.value = 'error'
      showToast.value = true
      canceling.value = false
    }
  })
}
</script> 