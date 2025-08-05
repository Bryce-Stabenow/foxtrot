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
        <Card class="cursor-pointer hover:shadow-md dark:hover:outline-2 transition-shadow" @click="setStatusFilter('')">
          <CardContent class="px-5">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <div class="w-8 h-8 bg-blue-500 rounded-md flex items-center justify-center">
                  <Icon name="checkCircle" class="w-5 h-5 text-white" />
                </div>
              </div>
              <div class="ml-5 w-0 flex-1">
                <dl>
                  <dt class="text-sm font-medium text-muted-foreground truncate">Total</dt>
                  <dd class="text-lg font-medium text-foreground">{{ stats.total }}</dd>
                </dl>
              </div>
            </div>
          </CardContent>
        </Card>

        <Card class="cursor-pointer hover:shadow-md dark:hover:outline-2 transition-shadow" @click="setStatusFilter('pending')">
          <CardContent class="px-5">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <div class="w-8 h-8 bg-yellow-500 rounded-md flex items-center justify-center">
                  <Icon name="clock" class="w-5 h-5 text-white" />
                </div>
              </div>
              <div class="ml-5 w-0 flex-1">
                <dl>
                  <dt class="text-sm font-medium text-muted-foreground truncate">Pending</dt>
                  <dd class="text-lg font-medium text-foreground">{{ stats.pending }}</dd>
                </dl>
              </div>
            </div>
          </CardContent>
        </Card>

        <Card class="cursor-pointer hover:shadow-md dark:hover:outline-2 transition-shadow" @click="setStatusFilter('in_progress')">
          <CardContent class="px-5">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <div class="w-8 h-8 bg-blue-500 rounded-md flex items-center justify-center">
                  <Icon name="play" class="w-5 h-5 text-white" />
                </div>
              </div>
              <div class="ml-5 w-0 flex-1">
                <dl>
                  <dt class="text-sm font-medium text-muted-foreground truncate">In Progress</dt>
                  <dd class="text-lg font-medium text-foreground">{{ stats.in_progress }}</dd>
                </dl>
              </div>
            </div>
          </CardContent>
        </Card>

        <Card class="cursor-pointer hover:shadow-md dark:hover:outline-2 transition-shadow" @click="setStatusFilter('completed')">
          <CardContent class="px-5">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <div class="w-8 h-8 bg-green-500 rounded-md flex items-center justify-center">
                  <Icon name="check" class="w-5 h-5 text-white" />
                </div>
              </div>
              <div class="ml-5 w-0 flex-1">
                <dl>
                  <dt class="text-sm font-medium text-muted-foreground truncate">Completed</dt>
                  <dd class="text-lg font-medium text-foreground">{{ stats.completed }}</dd>
                </dl>
              </div>
            </div>
          </CardContent>
        </Card>

        <Card class="cursor-pointer hover:shadow-md dark:hover:outline-2 transition-shadow" @click="setStatusFilter('overdue')">
          <CardContent class="px-5">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <div class="w-8 h-8 bg-red-500 rounded-md flex items-center justify-center">
                  <Icon name="alertTriangle" class="w-5 h-5 text-white" />
                </div>
              </div>
              <div class="ml-5 w-0 flex-1">
                <dl>
                  <dt class="text-sm font-medium text-muted-foreground truncate">Overdue</dt>
                  <dd class="text-lg font-medium text-foreground">{{ stats.overdue }}</dd>
                </dl>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- Filters -->
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <!-- Search -->
        <div>
          <label for="search" class="block text-sm font-medium text-foreground">Search</label>
          <input
            id="search"
            v-model="filters.search"
            type="text"
            class="mt-1 block w-full border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 rounded-md"
            placeholder="Search check-ins..."
          />
        </div>

        <!-- Status Filter -->
        <div>
          <label for="status" class="block text-sm font-medium text-foreground">Status</label>
          <select
            id="status"
            v-model="filters.status"
            class="mt-1 block w-full border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 rounded-md"
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
          <label for="team" class="block text-sm font-medium text-foreground">Team</label>
          <select
            id="team"
            v-model="filters.team_id"
            class="mt-1 block w-full border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 rounded-md"
          >
            <option value="">All Teams</option>
            <option v-for="team in teams" :key="team.id" :value="team.id">
              {{ team.name }}
            </option>
          </select>
        </div>

        <!-- Sort -->
        <div>
          <label for="sort" class="block text-sm font-medium text-foreground">Sort By</label>
          <select
            id="sort"
            v-model="filters.sort_by"
            class="mt-1 block w-full border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 rounded-md"
          >
            <option value="scheduled_date">Scheduled Date</option>
            <option value="title">Title</option>
            <option value="status">Status</option>
            <option value="created_at">Created Date</option>
          </select>
        </div>
      </div>

      <div class="mt-4 flex justify-end">
        <Button
          @click="clearFilters"
          variant="outline"
          class="inline-flex items-center px-3 py-2 text-sm leading-4 font-medium"
        >
          Clear Filters
        </Button>
      </div>

      <!-- Check-ins List -->
      <Card>
        <div class="divide-y divide-border">
          <CheckInListItem
            v-for="checkIn in checkIns.data"
            :key="checkIn.id"
            :check-in="checkIn"
            :can-edit="canEdit"
            :format-date="formatDate"
          />
        </div>
      </Card>

      <!-- Pagination -->
      <div v-if="checkIns.links" class="mt-6">
        <nav class="flex items-center justify-between">
          <div class="flex-1 flex justify-between sm:hidden">
            <Link
              v-if="checkIns.prev_page_url"
              :href="checkIns.prev_page_url"
              class="relative inline-flex items-center px-4 py-2 border border-input bg-background text-sm font-medium rounded-md text-foreground hover:bg-accent"
            >
              Previous
            </Link>
            <Link
              v-if="checkIns.next_page_url"
              :href="checkIns.next_page_url"
              class="ml-3 relative inline-flex items-center px-4 py-2 border border-input bg-background text-sm font-medium rounded-md text-foreground hover:bg-accent"
            >
              Next
            </Link>
          </div>
          <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
            <div>
              <p class="text-sm text-muted-foreground">
                Showing
                <span class="font-medium text-foreground">{{ checkIns.from }}</span>
                to
                <span class="font-medium text-foreground">{{ checkIns.to }}</span>
                of
                <span class="font-medium text-foreground">{{ checkIns.total }}</span>
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
                      'bg-primary border-primary text-primary-foreground': link.active,
                      'bg-background border-input text-foreground hover:bg-accent': !link.active,
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
import { Card, CardContent } from '@/components/ui/card'
import Icon from '@/components/Icon.vue'
import CheckInListItem from '@/components/CheckInListItem.vue'
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

const canCreate = ref(true) // TODO This should be determined by user permissions
const canEdit = (checkIn: CheckIn) => {
  // TODO This should check user permissions
  return true
}

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString()
}

const setStatusFilter = (status: string) => {
  filters.value.status = status
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