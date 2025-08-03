<template>
  <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
      <div class="text-center">
        <div class="mx-auto h-12 w-12 bg-blue-100 rounded-full flex items-center justify-center">
          <Icon name="Mail" class="h-6 w-6 text-blue-600" />
        </div>
        <h2 class="mt-6 text-3xl font-extrabold">
          Accept Invitation
        </h2>
        <p class="mt-2 text-sm">
          You've been invited to join <span class="font-medium">{{ invitation.organization.name }}</span>
        </p>
        <p class="text-xs mt-1">
          Invited by {{ invitation.invited_by.name }}
        </p>
      </div>

      <Card>
        <CardContent class="p-6">
          <form @submit.prevent="acceptInvitation" class="space-y-4">
            <div>
              <Label for="email">Email Address</Label>
              <Input
                id="email"
                :value="invitation.email"
                type="email"
                disabled
              />
              <p class="text-xs mt-1">
                This is the email address where you received the invitation.
              </p>
            </div>

            <div>
              <Label for="name">Full Name</Label>
              <Input
                id="name"
                v-model="form.name"
                type="text"
                placeholder="Enter your full name"
                :class="{ 'border-destructive': form.errors.name }"
                required
              />
              <InputError v-if="form.errors.name" :message="form.errors.name" />
            </div>

            <div>
              <Label for="password">Password</Label>
              <Input
                id="password"
                v-model="form.password"
                type="password"
                placeholder="Create a secure password"
                :class="{ 'border-destructive': form.errors.password }"
                required
              />
              <InputError v-if="form.errors.password" :message="form.errors.password" />
            </div>

            <div>
              <Label for="password_confirmation">Confirm Password</Label>
              <Input
                id="password_confirmation"
                v-model="form.password_confirmation"
                type="password"
                placeholder="Confirm your password"
                :class="{ 'border-destructive': form.errors.password_confirmation }"
                required
              />
              <InputError v-if="form.errors.password_confirmation" :message="form.errors.password_confirmation" />
            </div>

            <div class="bg-blue-50 dark:bg-blue-900 border border-blue-200 dark:border-blue-300 rounded-lg p-4 my-8">
              <div class="flex">
                <Icon name="Info" class="h-5 w-5 text-blue-400 dark:text-blue-300 mr-2 mt-0.5" />
                <div class="text-sm text-blue-800 dark:text-blue-300">
                  <p class="font-medium">About this invitation</p>
                  <p class="mt-1">
                    By accepting this invitation, you'll create an account and automatically join 
                    <span class="font-medium">{{ invitation.organization.name }}</span>.
                  </p>
                </div>
              </div>
            </div>

            <Button type="submit" class="w-full" :disabled="form.processing">
              <Icon v-if="form.processing" name="RefreshCw" class="h-4 w-4 mr-2 animate-spin" />
              {{ form.processing ? 'Creating Account...' : 'Accept Invitation & Create Account' }}
            </Button>
          </form>
        </CardContent>
      </Card>

      <div class="text-center">
        <p class="text-xs text-gray-500">
          This invitation will expire on {{ formatDate(invitation.expires_at) }}
        </p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
import Button from '@/components/ui/button/Button.vue'
import { Card, CardContent } from '@/components/ui/card'
import Input from '@/components/ui/input/Input.vue'
import Label from '@/components/ui/label/Label.vue'
import InputError from '@/components/InputError.vue'
import Icon from '@/components/Icon.vue'

interface Invitation {
  id: number
  email: string
  token: string
  expires_at: string
  organization: {
    name: string
  }
  invited_by: {
    name: string
  }
}

interface Props {
  invitation: Invitation
}

const props = defineProps<Props>()

const form = useForm({
  name: '',
  password: '',
  password_confirmation: '',
})

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  })
}

const acceptInvitation = () => {
  form.post(route('invitations.accept.store', props.invitation.token))
}
</script> 