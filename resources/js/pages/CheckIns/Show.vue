<template>
  <Head :title="`Check-in: ${checkIn.title}`" />
  <AppLayout>
      <div class="flex items-center justify-between p-4">
        <div>
          <h1 class="text-2xl font-semibold text-gray-900">{{ checkIn.title }}</h1>
          <p class="mt-1 text-sm text-gray-600">
            Check-in details and status
          </p>
        </div>
        <div class="flex items-center space-x-3">
          <Link
            :href="route('check-ins.index')"
            class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
          >
            View all Check-ins
          </Link>
          <Link
            v-if="canEdit"
            :href="route('check-ins.edit', checkIn.id)"
            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
          >
            Edit
          </Link>
        </div>
      </div>

    <div class="px-4">
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2">
          <div class="bg-white shadow sm:rounded-lg">
            <div class="px-4 py-5 sm:p-6">
              <!-- Status Badge -->
              <div class="mb-6">
                <span
                  :class="[
                    'inline-flex px-3 py-1 text-sm font-semibold rounded-full',
                    {
                      'bg-yellow-100 text-yellow-800': checkIn.status === 'pending',
                      'bg-blue-100 text-blue-800': checkIn.status === 'in_progress',
                      'bg-green-100 text-green-800': checkIn.status === 'completed',
                      'bg-red-100 text-red-800': checkIn.status === 'overdue',
                    }
                  ]"
                >
                  {{ checkIn.status.replace('_', ' ') }}
                </span>
              </div>

              <!-- Description -->
              <div class="mb-6">
                <h3 class="text-lg font-medium text-gray-900 mb-2">Description</h3>
                <p v-if="checkIn.description" class="text-gray-700 whitespace-pre-wrap">
                  {{ checkIn.description }}
                </p>
                <p v-else class="text-gray-500 italic">No description provided.</p>
              </div>

              <!-- Notes (if completed) -->
              <div v-if="checkIn.notes" class="mb-6">
                <h3 class="text-lg font-medium text-gray-900 mb-2">Completion Notes</h3>
                <p class="text-gray-700 whitespace-pre-wrap">{{ checkIn.notes }}</p>
              </div>

              <!-- Status Actions -->
              <div v-if="canUpdateStatus" class="border-t pt-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Update Status</h3>
                <div class="flex space-x-3">
                  <button
                    v-if="checkIn.status === 'pending'"
                    @click="markInProgress"
                    :disabled="processing"
                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50"
                  >
                    Mark In Progress
                  </button>
                  <button
                    v-if="['pending', 'in_progress'].includes(checkIn.status)"
                    @click="showCompleteModal = true"
                    :disabled="processing"
                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50"
                  >
                    Mark Complete
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Sidebar -->
        <div class="lg:col-span-1">
          <div class="bg-white shadow sm:rounded-lg">
            <div class="px-4 py-5 sm:p-6">
              <h3 class="text-lg font-medium text-gray-900 mb-4">Details</h3>
              
              <!-- Assigned User -->
              <div class="mb-4">
                <dt class="text-sm font-medium text-gray-500">Assigned To</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ checkIn.assigned_user.name }}</dd>
              </div>

              <!-- Team -->
              <div class="mb-4">
                <dt class="text-sm font-medium text-gray-500">Team</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ checkIn.team.name }}</dd>
              </div>

              <!-- Created By -->
              <div class="mb-4">
                <dt class="text-sm font-medium text-gray-500">Created By</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ checkIn.created_by.name }}</dd>
              </div>

              <!-- Scheduled Date -->
              <div class="mb-4">
                <dt class="text-sm font-medium text-gray-500">Scheduled Date</dt>
                <dd class="mt-1 text-sm text-gray-900">
                  {{ formatDate(checkIn.scheduled_date) }}
                  <span v-if="checkIn.is_overdue" class="text-red-600 font-medium ml-1">
                    (Overdue)
                  </span>
                </dd>
              </div>

              <!-- Created Date -->
              <div class="mb-4">
                <dt class="text-sm font-medium text-gray-500">Created</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ formatDate(checkIn.created_at) }}</dd>
              </div>

              <!-- Completed Date -->
              <div v-if="checkIn.completed_at" class="mb-4">
                <dt class="text-sm font-medium text-gray-500">Completed</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ formatDate(checkIn.completed_at) }}</dd>
              </div>

              <!-- Delete Button -->
              <div v-if="canDelete" class="border-t pt-4">
                <button
                  @click="showDeleteModal = true"
                  class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
                >
                  Delete Check-in
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Complete Modal -->
    <Dialog :open="showCompleteModal" @update:open="showCompleteModal = $event">
      <DialogContent class="sm:max-w-lg">
        <DialogHeader>
          <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-green-100">
            <Icon name="check" class="h-6 w-6 text-green-600" />
          </div>
          <DialogTitle class="text-center">
            Mark Check-in as Complete
          </DialogTitle>
        </DialogHeader>
        <div class="mt-4">
          <p class="text-sm text-gray-500 text-center">
            Add any notes about the completion of this check-in.
          </p>
          <div class="mt-4">
            <textarea
              v-model="completeForm.notes"
              rows="4"
              class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
              placeholder="Optional notes about completion..."
            ></textarea>
          </div>
        </div>
        <DialogFooter class="sm:grid sm:grid-flow-row-dense sm:grid-cols-2 sm:gap-3">
          <button
            type="button"
            @click="markComplete"
            :disabled="completeForm.processing"
            class="inline-flex w-full justify-center rounded-md bg-green-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-green-600 disabled:opacity-50"
          >
            <span v-if="completeForm.processing">Completing...</span>
            <span v-else>Mark Complete</span>
          </button>
          <button
            type="button"
            @click="showCompleteModal = false"
            class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0"
          >
            Cancel
          </button>
        </DialogFooter>
      </DialogContent>
    </Dialog>

    <!-- Delete Modal -->
    <Dialog :open="showDeleteModal" @update:open="showDeleteModal = $event">
      <DialogContent class="sm:max-w-lg">
        <DialogHeader>
          <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-red-100">
            <Icon name="trash" class="h-6 w-6 text-red-600" />
          </div>
          <DialogTitle class="text-center">
            Delete Check-in
          </DialogTitle>
        </DialogHeader>
        <div class="mt-4">
          <p class="text-sm text-gray-500 text-center">
            Are you sure you want to delete this check-in? This action cannot be undone.
          </p>
        </div>
        <DialogFooter class="sm:grid sm:grid-flow-row-dense sm:grid-cols-2 sm:gap-3">
          <button
            type="button"
            @click="deleteCheckIn"
            :disabled="deleteForm.processing"
            class="inline-flex w-full justify-center rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600 disabled:opacity-50"
          >
            <span v-if="deleteForm.processing">Deleting...</span>
            <span v-else>Delete</span>
          </button>
          <button
            type="button"
            @click="showDeleteModal = false"
            class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0"
          >
            Cancel
          </button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { Link, useForm, router, Head } from '@inertiajs/vue3'
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog'
import AppLayout from '@/layouts/AppLayout.vue'
import Icon from '@/components/Icon.vue'
import type { CheckIn } from '@/types'

interface Props {
  checkIn: CheckIn
}

const props = defineProps<Props>()

const showCompleteModal = ref(false)
const showDeleteModal = ref(false)

const completeForm = useForm({
  notes: '',
})

const deleteForm = useForm({})

// These should be determined by user permissions
const canEdit = ref(true)
const canDelete = ref(true)
const canUpdateStatus = ref(true)

const processing = computed(() => completeForm.processing || deleteForm.processing)

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString()
}

const markInProgress = () => {
  router.patch(route('check-ins.in-progress', props.checkIn.id))
}

const markComplete = () => {
  completeForm.patch(route('check-ins.complete', props.checkIn.id), {
    onSuccess: () => {
      showCompleteModal.value = false
    },
  })
}

const deleteCheckIn = () => {
  deleteForm.delete(route('check-ins.destroy', props.checkIn.id))
}
</script> 