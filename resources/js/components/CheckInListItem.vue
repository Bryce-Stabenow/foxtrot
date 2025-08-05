<template>
  <div class="px-4 py-4 sm:px-6">
    <div class="flex items-center justify-between">
      <div class="flex items-center">
        <div class="flex-shrink-0">
          <div
            :class="[
              'w-3 h-3 rounded-full',
              {
                'bg-yellow-400': checkIn.status === 'pending',
                'bg-blue-400': checkIn.status === 'in_progress',
                'bg-green-400': checkIn.status === 'completed',
                'bg-red-400': checkIn.status === 'overdue',
              }
            ]"
          ></div>
        </div>
        <div class="ml-4">
          <div class="flex items-center">
            <p class="text-sm font-medium text-primary truncate">
              <Link :href="route('check-ins.show', checkIn.id)">
                {{ checkIn.title }}
              </Link>
            </p>
            <div class="ml-2 flex-shrink-0 flex">
              <p
                :class="[
                  'inline-flex px-2 text-xs leading-5 font-semibold rounded-full',
                  {
                    'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400': checkIn.status === 'pending',
                    'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400': checkIn.status === 'in_progress',
                    'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400': checkIn.status === 'completed',
                    'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400': checkIn.status === 'overdue',
                  }
                ]"
              >
                {{ checkIn.status.replace('_', ' ') }}
              </p>
            </div>
          </div>
          <div class="mt-2 flex gap-2">
              <p class="flex items-center text-sm text-muted-foreground">
                <Icon name="user" class="flex-shrink-0 mr-1.5 h-4 w-4 text-muted-foreground" />
                {{ checkIn.assigned_user.name }}
              </p>
              <p class="mt-2 flex items-center text-sm text-muted-foreground sm:mt-0">
                <Icon name="users" class="flex-shrink-0 mr-1.5 h-4 w-4 text-muted-foreground" />
                {{ checkIn.team.name }}
              </p>
            <div class="mt-2 flex items-center text-sm text-muted-foreground sm:mt-0">
              <Icon name="calendar" class="flex-shrink-0 mr-1.5 h-4 w-4 text-muted-foreground" />
              <p>
                Due: {{ formatDate(checkIn.scheduled_date) }}
                <span v-if="checkIn.is_overdue" class="text-destructive font-medium">
                  (Overdue)
                </span>
              </p>
            </div>
          </div>
        </div>
      </div>
      <div class="flex items-center space-x-2">
        <Link
          :href="route('check-ins.show', checkIn.id)"
          class="text-primary hover:text-primary/80 text-sm font-medium"
        >
          View
        </Link>
        <Link
          v-if="canEdit(checkIn)"
          :href="route('check-ins.edit', checkIn.id)"
          class="text-muted-foreground hover:text-foreground text-sm font-medium"
        >
          Edit
        </Link>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import Icon from '@/components/Icon.vue'
import type { CheckIn } from '@/types'

interface Props {
  checkIn: CheckIn
  canEdit: (checkIn: CheckIn) => boolean
  formatDate: (date: string) => string
}

defineProps<Props>()
</script> 