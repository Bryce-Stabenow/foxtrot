<template>
  <Head title="Check-ins" />
  <AppLayout>
    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-semibold">Check-ins</h1>
          <p class="mt-1 text-sm text-muted-foreground">
            Manage and track team check-ins
          </p>
        </div>
        <div v-if="canCreate" class="flex items-center gap-3">
          <Button @click="router.visit(route('check-ins.create'))">
            Create Check-in
          </Button>
        </div>
      </div>

      <!-- Statistics Cards -->
      <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-5">
      <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <div class="w-8 h-8 bg-blue-500 rounded-md flex items-center justify-center">
                <Icon name="check-circle" class="w-5 h-5 text-white" />
              </div>
            </div>
            <div class="ml-5 w-0 flex-1">
              <dl>
                <dt class="text-sm font-medium text-gray-500 truncate">Total</dt>
                <dd class="text-lg font-medium text-gray-900">{{ stats.total }}</dd>
              </dl>
            </div>
          </div>
        </div>
      </div>

      <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <div class="w-8 h-8 bg-yellow-500 rounded-md flex items-center justify-center">
                <Icon name="clock" class="w-5 h-5 text-white" />
              </div>
            </div>
            <div class="ml-5 w-0 flex-1">
              <dl>
                <dt class="text-sm font-medium text-gray-500 truncate">Pending</dt>
                <dd class="text-lg font-medium text-gray-900">{{ stats.pending }}</dd>
              </dl>
            </div>
          </div>
        </div>
      </div>

      <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <div class="w-8 h-8 bg-blue-500 rounded-md flex items-center justify-center">
                <Icon name="play" class="w-5 h-5 text-white" />
              </div>
            </div>
            <div class="ml-5 w-0 flex-1">
              <dl>
                <dt class="text-sm font-medium text-gray-500 truncate">In Progress</dt>
                <dd class="text-lg font-medium text-gray-900">{{ stats.in_progress }}</dd>
              </dl>
            </div>
          </div>
        </div>
      </div>

      <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <div class="w-8 h-8 bg-green-500 rounded-md flex items-center justify-center">
                <Icon name="check" class="w-5 h-5 text-white" />
              </div>
            </div>
            <div class="ml-5 w-0 flex-1">
              <dl>
                <dt class="text-sm font-medium text-gray-500 truncate">Completed</dt>
                <dd class="text-lg font-medium text-gray-900">{{ stats.completed }}</dd>
              </dl>
            </div>
          </div>
        </div>
      </div>

      <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <div class="w-8 h-8 bg-red-500 rounded-md flex items-center justify-center">
                <Icon name="alert-triangle" class="w-5 h-5 text-white" />
              </div>
            </div>
            <div class="ml-5 w-0 flex-1">
              <dl>
                <dt class="text-sm font-medium text-gray-500 truncate">Overdue</dt>
                <dd class="text-lg font-medium text-gray-900">{{ stats.overdue }}</dd>
              </dl>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div class="bg-white shadow rounded-lg mb-6">
      <div class="px-4 py-5 sm:p-6">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
          <!-- Search -->
          <div>
            <label for="search" class="block text-sm font-medium text-gray-700">Search</label>
            <input
              id="search"
              v-model="filters.search"
              type="text"
              class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
              placeholder="Search check-ins..."
            />
          </div>

          <!-- Status Filter -->
          <div>
            <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
            <select
              id="status"
              v-model="filters.status"
              class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
            >
              <option value="">All Statuses</option>
              <option value="pending">Pending</option>
              <option value="in_progress">In Progress</option>
              <option value="completed">Completed</option>
              <option value="overdue">Overdue</option>
            </select>
          </div>

          <!-- Team Filter -->
          <div>
            <label for="team" class="block text-sm font-medium text-gray-700">Team</label>
            <select
              id="team"
              v-model="filters.team_id"
              class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
            >
              <option value="">All Teams</option>
              <option v-for="team in teams" :key="team.id" :value="team.id">
                {{ team.name }}
              </option>
            </select>
          </div>

          <!-- Sort -->
          <div>
            <label for="sort" class="block text-sm font-medium text-gray-700">Sort By</label>
            <select
              id="sort"
              v-model="filters.sort_by"
              class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
            >
              <option value="scheduled_date">Scheduled Date</option>
              <option value="title">Title</option>
              <option value="status">Status</option>
              <option value="created_at">Created Date</option>
            </select>
          </div>
        </div>

        <div class="mt-4 flex justify-end">
          <button
            @click="clearFilters"
            class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
          >
            Clear Filters
          </button>
        </div>
      </div>
    </div>

    <!-- Check-ins List -->
    <div class="bg-white shadow overflow-hidden sm:rounded-md">
      <ul class="divide-y divide-gray-200">
        <li v-for="checkIn in checkIns.data" :key="checkIn.id">
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
                    <p class="text-sm font-medium text-blue-600 truncate">
                      <Link :href="route('check-ins.show', checkIn.id)">
                        {{ checkIn.title }}
                      </Link>
                    </p>
                    <div class="ml-2 flex-shrink-0 flex">
                      <p
                        :class="[
                          'inline-flex px-2 text-xs leading-5 font-semibold rounded-full',
                          {
                            'bg-yellow-100 text-yellow-800': checkIn.status === 'pending',
                            'bg-blue-100 text-blue-800': checkIn.status === 'in_progress',
                            'bg-green-100 text-green-800': checkIn.status === 'completed',
                            'bg-red-100 text-red-800': checkIn.status === 'overdue',
                          }
                        ]"
                      >
                        {{ checkIn.status.replace('_', ' ') }}
                      </p>
                    </div>
                  </div>
                  <div class="mt-2 sm:flex sm:justify-between">
                    <div class="sm:flex">
                      <p class="flex items-center text-sm text-gray-500">
                        <Icon name="user" class="flex-shrink-0 mr-1.5 h-4 w-4 text-gray-400" />
                        {{ checkIn.assigned_user.name }}
                      </p>
                      <p class="mt-2 flex items-center text-sm text-gray-500 sm:mt-0 sm:ml-6">
                        <Icon name="users" class="flex-shrink-0 mr-1.5 h-4 w-4 text-gray-400" />
                        {{ checkIn.team.name }}
                      </p>
                    </div>
                    <div class="mt-2 flex items-center text-sm text-gray-500 sm:mt-0">
                      <Icon name="calendar" class="flex-shrink-0 mr-1.5 h-4 w-4 text-gray-400" />
                      <p>
                        Due: {{ formatDate(checkIn.scheduled_date) }}
                        <span v-if="checkIn.is_overdue" class="text-red-600 font-medium">
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
                  class="text-blue-600 hover:text-blue-900 text-sm font-medium"
                >
                  View
                </Link>
                <Link
                  v-if="canEdit(checkIn)"
                  :href="route('check-ins.edit', checkIn.id)"
                  class="text-gray-600 hover:text-gray-900 text-sm font-medium"
                >
                  Edit
                </Link>
              </div>
            </div>
          </div>
        </li>
      </ul>
    </div>

    <!-- Pagination -->
    <div v-if="checkIns.links" class="mt-6">
      <nav class="flex items-center justify-between">
        <div class="flex-1 flex justify-between sm:hidden">
          <Link
            v-if="checkIns.prev_page_url"
            :href="checkIns.prev_page_url"
            class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
          >
            Previous
          </Link>
          <Link
            v-if="checkIns.next_page_url"
            :href="checkIns.next_page_url"
            class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
          >
            Next
          </Link>
        </div>
        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
          <div>
            <p class="text-sm text-gray-700">
              Showing
              <span class="font-medium">{{ checkIns.from }}</span>
              to
              <span class="font-medium">{{ checkIns.to }}</span>
              of
              <span class="font-medium">{{ checkIns.total }}</span>
              results
            </p>
          </div>
          <div>
            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
              <Link
                v-for="link in checkIns.links"
                :key="link.label"
                :href="link.url"
                :class="[
                  'relative inline-flex items-center px-4 py-2 border text-sm font-medium',
                  {
                    'bg-blue-50 border-blue-500 text-blue-600': link.active,
                    'bg-white border-gray-300 text-gray-500 hover:bg-gray-50': !link.active,
                  }
                ]"
                v-html="link.label"
              />
            </nav>
          </div>
        </div>
      </nav>
    </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import Button from '@/components/ui/button/Button.vue'
import Icon from '@/components/Icon.vue'
import type { CheckIn, Team, Stats, Filters } from '@/types'
import { Head } from '@inertiajs/vue3'

interface Props {
  checkIns: {
    data: CheckIn[]
    links: any[]
    prev_page_url: string | null
    next_page_url: string | null
    from: number
    to: number
    total: number
  }
  teams: Team[]
  stats: Stats
  filters: Filters
}

const props = defineProps<Props>()

const filters = ref<Filters>({
  status: props.filters.status || '',
  team_id: props.filters.team_id || '',
  search: props.filters.search || '',
  sort_by: props.filters.sort_by || 'scheduled_date',
  sort_direction: props.filters.sort_direction || 'asc',
})

const canCreate = ref(true) // This should be determined by user permissions
const canEdit = (checkIn: CheckIn) => {
  // This should check user permissions
  return true
}

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString()
}

const clearFilters = () => {
  filters.value = {
    status: '',
    team_id: '',
    search: '',
    sort_by: 'scheduled_date',
    sort_direction: 'asc',
  }
}

watch(filters, (newFilters) => {
  router.get(route('check-ins.index'), newFilters, {
    preserveState: true,
    preserveScroll: true,
  })
}, { deep: true })
</script> 