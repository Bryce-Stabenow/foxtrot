<template>
  <AppLayout>
    <template #header>
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-semibold text-gray-900">Edit Check-in</h1>
          <p class="mt-1 text-sm text-gray-600">
            Update check-in details
          </p>
        </div>
        <Link
          :href="route('check-ins.show', checkIn.id)"
          class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
        >
          Back to Check-in
        </Link>
      </div>
    </template>

    <div class="max-w-2xl mx-auto">
      <form @submit.prevent="submit" class="space-y-6">
        <div class="bg-white shadow sm:rounded-lg">
          <div class="px-4 py-5 sm:p-6">
            <!-- Title -->
            <div class="mb-6">
              <label for="title" class="block text-sm font-medium text-gray-700">
                Title *
              </label>
              <input
                id="title"
                v-model="form.title"
                type="text"
                required
                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                :class="{ 'border-red-300': errors.title }"
              />
              <p v-if="errors.title" class="mt-1 text-sm text-red-600">
                {{ errors.title }}
              </p>
            </div>

            <!-- Description -->
            <div class="mb-6">
              <label for="description" class="block text-sm font-medium text-gray-700">
                Description
              </label>
              <textarea
                id="description"
                v-model="form.description"
                rows="4"
                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                :class="{ 'border-red-300': errors.description }"
                placeholder="Provide details about the check-in..."
              ></textarea>
              <p v-if="errors.description" class="mt-1 text-sm text-red-600">
                {{ errors.description }}
              </p>
            </div>

            <!-- Team -->
            <div class="mb-6">
              <label for="team_id" class="block text-sm font-medium text-gray-700">
                Team *
              </label>
              <select
                id="team_id"
                v-model="form.team_id"
                required
                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                :class="{ 'border-red-300': errors.team_id }"
              >
                <option value="">Select a team</option>
                <option v-for="team in teams" :key="team.id" :value="team.id">
                  {{ team.name }}
                </option>
              </select>
              <p v-if="errors.team_id" class="mt-1 text-sm text-red-600">
                {{ errors.team_id }}
              </p>
            </div>

            <!-- Assigned User -->
            <div class="mb-6">
              <label for="assigned_user_id" class="block text-sm font-medium text-gray-700">
                Assign To *
              </label>
              <select
                id="assigned_user_id"
                v-model="form.assigned_user_id"
                required
                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                :class="{ 'border-red-300': errors.assigned_user_id }"
              >
                <option value="">Select a team member</option>
                <option v-for="user in users" :key="user.id" :value="user.id">
                  {{ user.name }}
                </option>
              </select>
              <p v-if="errors.assigned_user_id" class="mt-1 text-sm text-red-600">
                {{ errors.assigned_user_id }}
              </p>
            </div>

            <!-- Scheduled Date -->
            <div class="mb-6">
              <label for="scheduled_date" class="block text-sm font-medium text-gray-700">
                Scheduled Date *
              </label>
              <input
                id="scheduled_date"
                v-model="form.scheduled_date"
                type="date"
                required
                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                :class="{ 'border-red-300': errors.scheduled_date }"
              />
              <p v-if="errors.scheduled_date" class="mt-1 text-sm text-red-600">
                {{ errors.scheduled_date }}
              </p>
            </div>
          </div>
        </div>

        <!-- Form Actions -->
        <div class="flex justify-end space-x-3">
          <Link
            :href="route('check-ins.show', checkIn.id)"
            class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
          >
            Cancel
          </Link>
          <button
            type="submit"
            :disabled="processing"
            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50"
          >
            <span v-if="processing">Updating...</span>
            <span v-else>Update Check-in</span>
          </button>
        </div>
      </form>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import type { CheckIn, Team, User } from '@/types'

interface Props {
  checkIn: CheckIn
  teams: Team[]
  users: User[]
}

const props = defineProps<Props>()

const form = useForm({
  title: props.checkIn.title,
  description: props.checkIn.description || '',
  team_id: props.checkIn.team_id.toString(),
  assigned_user_id: props.checkIn.assigned_user_id.toString(),
  scheduled_date: props.checkIn.scheduled_date,
})

const processing = computed(() => form.processing)
const errors = computed(() => form.errors)

const submit = () => {
  form.put(route('check-ins.update', props.checkIn.id))
}
</script> 