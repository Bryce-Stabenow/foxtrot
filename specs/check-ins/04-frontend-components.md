# Check-In Feature - Frontend Components

## Overview
This document outlines all the frontend components for the check-in feature, including Vue.js components, pages, and UI patterns using the existing design system.

## Component Architecture

### 1. Pages

#### CheckIns/Index.vue
**Purpose**: Main check-in listing page with filtering and management

**Features**:
- List all check-ins based on user role
- Filter by status, team, assigned user, date
- Search functionality
- Sort by various criteria
- Bulk actions for admins
- Quick status updates

**Props**: None

**Events**: 
- `check-in-updated` - When a check-in is updated
- `check-in-deleted` - When a check-in is deleted

**Template Structure**:
```vue
<template>
  <AppLayout>
    <template #header>
      <Heading>Check-ins</Heading>
      <Button v-if="canCreate" @click="showCreateModal = true">
        Create Check-in
      </Button>
    </template>

    <div class="space-y-6">
      <!-- Filters -->
      <CheckInFilters 
        v-model:filters="filters"
        :teams="teams"
        :users="users"
        @filter-changed="loadCheckIns"
      />

      <!-- Statistics -->
      <CheckInStats :stats="stats" />

      <!-- Check-in List -->
      <CheckInList 
        :check-ins="checkIns"
        :loading="loading"
        @status-updated="handleStatusUpdate"
        @check-in-clicked="showCheckInDetails"
      />

      <!-- Pagination -->
      <Pagination 
        :meta="meta"
        @page-changed="loadCheckIns"
      />
    </div>

    <!-- Create Modal -->
    <CheckInForm
      v-if="showCreateModal"
      :teams="teams"
      :users="users"
      @saved="handleCheckInCreated"
      @cancelled="showCreateModal = false"
    />
  </AppLayout>
</template>
```

#### CheckIns/Create.vue
**Purpose**: Create new check-in form

**Features**:
- Form validation
- Team and user selection
- Date picker for scheduled date
- Rich text description
- Preview functionality

**Props**:
- `teams` - Array of available teams
- `users` - Array of available users

**Events**:
- `saved` - When check-in is created successfully
- `cancelled` - When form is cancelled

#### CheckIns/Show.vue
**Purpose**: Display individual check-in details

**Features**:
- Complete check-in information
- Status update buttons
- Notes and completion details
- Related check-ins
- Activity timeline

**Props**:
- `checkIn` - CheckIn object

#### CheckIns/Edit.vue
**Purpose**: Edit existing check-in

**Features**:
- Pre-populated form
- Validation
- Change tracking
- Confirmation for changes

**Props**:
- `checkIn` - CheckIn object to edit

### 2. Components

#### CheckInList.vue
**Purpose**: Display list of check-ins with actions

**Features**:
- Responsive grid/list view
- Status indicators
- Quick actions
- Loading states
- Empty states

**Props**:
- `check-ins` - Array of CheckIn objects
- `loading` - Boolean loading state
- `show-actions` - Boolean to show action buttons

**Events**:
- `status-updated` - When status is changed
- `check-in-clicked` - When check-in is clicked
- `check-in-deleted` - When check-in is deleted

**Template Structure**:
```vue
<template>
  <div class="space-y-4">
    <div v-if="loading" class="space-y-4">
      <CheckInCardSkeleton v-for="i in 3" :key="i" />
    </div>

    <div v-else-if="checkIns.length === 0" class="text-center py-12">
      <EmptyState 
        title="No check-ins found"
        description="Get started by creating your first check-in"
        :show-action="canCreate"
        action-text="Create Check-in"
        @action-clicked="$emit('create-clicked')"
      />
    </div>

    <div v-else class="space-y-4">
      <CheckInCard
        v-for="checkIn in checkIns"
        :key="checkIn.id"
        :check-in="checkIn"
        :show-actions="showActions"
        @status-updated="$emit('status-updated', $event)"
        @clicked="$emit('check-in-clicked', checkIn)"
        @deleted="$emit('check-in-deleted', checkIn)"
      />
    </div>
  </div>
</template>
```

#### CheckInCard.vue
**Purpose**: Individual check-in card component

**Features**:
- Status badge with color coding
- Due date indicator
- Assigned user avatar
- Quick status update buttons
- Hover actions

**Props**:
- `check-in` - CheckIn object
- `show-actions` - Boolean to show action buttons
- `compact` - Boolean for compact view

**Events**:
- `status-updated` - When status is changed
- `clicked` - When card is clicked
- `deleted` - When check-in is deleted

**Template Structure**:
```vue
<template>
  <Card class="hover:shadow-md transition-shadow cursor-pointer" @click="$emit('clicked')">
    <CardHeader class="pb-3">
      <div class="flex items-start justify-between">
        <div class="flex-1 min-w-0">
          <CardTitle class="text-lg font-semibold truncate">
            {{ checkIn.title }}
          </CardTitle>
          <p v-if="checkIn.description" class="text-sm text-muted-foreground mt-1 line-clamp-2">
            {{ checkIn.description }}
          </p>
        </div>
        <CheckInStatus :status="checkIn.status" />
      </div>
    </CardHeader>

    <CardContent class="pt-0">
      <div class="flex items-center justify-between">
        <div class="flex items-center space-x-3">
          <Avatar>
            <AvatarImage :src="checkIn.assigned_user.avatar_url" />
            <AvatarFallback>{{ getInitials(checkIn.assigned_user.name) }}</AvatarFallback>
          </Avatar>
          <div>
            <p class="text-sm font-medium">{{ checkIn.assigned_user.name }}</p>
            <p class="text-xs text-muted-foreground">{{ checkIn.team.name }}</p>
          </div>
        </div>

        <div class="text-right">
          <p class="text-sm font-medium">
            Due {{ formatDate(checkIn.scheduled_date) }}
          </p>
          <p class="text-xs text-muted-foreground">
            {{ getDaysUntil(checkIn.scheduled_date) }}
          </p>
        </div>
      </div>

      <div v-if="showActions" class="flex items-center justify-end space-x-2 mt-4 pt-4 border-t">
        <Button
          v-if="canUpdateStatus"
          variant="outline"
          size="sm"
          @click.stop="updateStatus('in_progress')"
        >
          Start
        </Button>
        <Button
          v-if="canComplete"
          variant="default"
          size="sm"
          @click.stop="showCompleteModal = true"
        >
          Complete
        </Button>
        <DropdownMenu>
          <DropdownMenuTrigger as-child>
            <Button variant="ghost" size="sm">
              <Icon name="more-horizontal" class="h-4 w-4" />
            </Button>
          </DropdownMenuTrigger>
          <DropdownMenuContent>
            <DropdownMenuItem @click.stop="editCheckIn">
              Edit
            </DropdownMenuItem>
            <DropdownMenuItem 
              v-if="canDelete"
              @click.stop="deleteCheckIn"
              class="text-destructive"
            >
              Delete
            </DropdownMenuItem>
          </DropdownMenuContent>
        </DropdownMenu>
      </div>
    </CardContent>
  </Card>
</template>
```

#### CheckInStatus.vue
**Purpose**: Status indicator with color coding

**Features**:
- Color-coded status badges
- Status text
- Icon indicators
- Tooltip with additional info

**Props**:
- `status` - Status string (pending, in_progress, completed, overdue)
- `show-text` - Boolean to show status text
- `size` - Size variant (sm, md, lg)

**Status Colors**:
- `pending` - Gray
- `in_progress` - Blue
- `completed` - Green
- `overdue` - Red

#### CheckInForm.vue
**Purpose**: Form for creating/editing check-ins

**Features**:
- Form validation
- Team and user selection
- Date picker
- Rich text editor
- Preview mode

**Props**:
- `check-in` - Optional CheckIn object for editing
- `teams` - Array of available teams
- `users` - Array of available users

**Events**:
- `saved` - When form is saved
- `cancelled` - When form is cancelled

**Template Structure**:
```vue
<template>
  <Dialog :open="true" @close="$emit('cancelled')">
    <DialogContent class="max-w-2xl">
      <DialogHeader>
        <DialogTitle>
          {{ checkIn ? 'Edit Check-in' : 'Create Check-in' }}
        </DialogTitle>
        <DialogDescription>
          {{ checkIn ? 'Update the check-in details' : 'Create a new check-in for a team member' }}
        </DialogDescription>
      </DialogHeader>

      <form @submit.prevent="handleSubmit" class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="space-y-2">
            <Label for="title">Title</Label>
            <Input
              id="title"
              v-model="form.title"
              :error="errors.title"
              placeholder="Enter check-in title"
            />
          </div>

          <div class="space-y-2">
            <Label for="scheduled_date">Scheduled Date</Label>
            <Input
              id="scheduled_date"
              v-model="form.scheduled_date"
              type="date"
              :error="errors.scheduled_date"
              :min="minDate"
            />
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="space-y-2">
            <Label for="team">Team</Label>
            <Select v-model="form.team_id" :error="errors.team_id">
              <SelectTrigger>
                <SelectValue placeholder="Select a team" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem
                  v-for="team in teams"
                  :key="team.id"
                  :value="team.id"
                >
                  {{ team.name }}
                </SelectItem>
              </SelectContent>
            </Select>
          </div>

          <div class="space-y-2">
            <Label for="assigned_user">Assigned User</Label>
            <Select v-model="form.assigned_user_id" :error="errors.assigned_user_id">
              <SelectTrigger>
                <SelectValue placeholder="Select a user" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem
                  v-for="user in availableUsers"
                  :key="user.id"
                  :value="user.id"
                >
                  {{ user.name }}
                </SelectItem>
              </SelectContent>
            </Select>
          </div>
        </div>

        <div class="space-y-2">
          <Label for="description">Description</Label>
          <Textarea
            id="description"
            v-model="form.description"
            :error="errors.description"
            placeholder="Enter check-in description"
            rows="4"
          />
        </div>

        <DialogFooter>
          <Button type="button" variant="outline" @click="$emit('cancelled')">
            Cancel
          </Button>
          <Button type="submit" :loading="loading">
            {{ checkIn ? 'Update' : 'Create' }} Check-in
          </Button>
        </DialogFooter>
      </form>
    </DialogContent>
  </Dialog>
</template>
```

#### CheckInFilters.vue
**Purpose**: Filter and sort check-ins

**Features**:
- Status filter
- Team filter
- User filter
- Date range filter
- Search functionality
- Sort options
- Clear filters

**Props**:
- `filters` - Current filter state
- `teams` - Array of available teams
- `users` - Array of available users

**Events**:
- `filter-changed` - When filters are updated

#### CheckInStats.vue
**Purpose**: Display check-in statistics

**Features**:
- Total check-ins count
- Status breakdown
- Completion rate
- Progress indicators
- Quick filters

**Props**:
- `stats` - Statistics object

#### CheckInCardSkeleton.vue
**Purpose**: Loading skeleton for check-in cards

**Features**:
- Animated loading state
- Matches card layout
- Responsive design

### 3. Composables

#### useCheckIns.ts
**Purpose**: Check-in data management and API calls

**Features**:
- CRUD operations
- Filtering and pagination
- Status updates
- Error handling
- Loading states

**Methods**:
```typescript
interface UseCheckIns {
  // Data
  checkIns: Ref<CheckIn[]>
  loading: Ref<boolean>
  error: Ref<string | null>
  
  // Methods
  loadCheckIns(filters?: CheckInFilters): Promise<void>
  createCheckIn(data: CreateCheckInData): Promise<CheckIn>
  updateCheckIn(id: number, data: UpdateCheckInData): Promise<CheckIn>
  deleteCheckIn(id: number): Promise<void>
  markComplete(id: number, notes?: string): Promise<CheckIn>
  markInProgress(id: number): Promise<CheckIn>
  
  // Utilities
  getCheckIn(id: number): CheckIn | undefined
  getStats(): CheckInStats
}
```

#### useCheckInPermissions.ts
**Purpose**: Check-in authorization logic

**Features**:
- Permission checks
- Role-based access
- Action authorization

**Methods**:
```typescript
interface UseCheckInPermissions {
  canCreate: ComputedRef<boolean>
  canUpdate: (checkIn: CheckIn) => boolean
  canDelete: (checkIn: CheckIn) => boolean
  canMarkComplete: (checkIn: CheckIn) => boolean
  canView: (checkIn: CheckIn) => boolean
}
```

### 4. Types

#### CheckIn Types
```typescript
interface CheckIn {
  id: number
  title: string
  description: string | null
  team_id: number
  team: Team
  assigned_user_id: number
  assigned_user: User
  created_by_user_id: number
  created_by_user: User
  scheduled_date: string
  completed_at: string | null
  status: CheckInStatus
  notes: string | null
  created_at: string
  updated_at: string
}

type CheckInStatus = 'pending' | 'in_progress' | 'completed' | 'overdue'

interface CheckInFilters {
  status?: CheckInStatus
  team_id?: number
  assigned_user_id?: number
  scheduled_date?: string
  search?: string
  sort?: string
  order?: 'asc' | 'desc'
  per_page?: number
}

interface CheckInStats {
  total_check_ins: number
  pending_check_ins: number
  in_progress_check_ins: number
  completed_check_ins: number
  overdue_check_ins: number
  completion_rate: number
  upcoming_deadlines: CheckIn[]
  recent_activity: CheckIn[]
}
```

### 5. Navigation Integration

#### NavMain.vue Updates
Add check-ins link to main navigation:

```vue
<template>
  <nav class="space-y-1">
    <!-- Existing navigation items -->
    
    <NavItem
      v-if="canAccessCheckIns"
      :href="route('check-ins.index')"
      :active="route().current('check-ins.*')"
    >
      <Icon name="check-square" class="h-4 w-4" />
      <span>Check-ins</span>
      <Badge v-if="overdueCount > 0" variant="destructive" class="ml-auto">
        {{ overdueCount }}
      </Badge>
    </NavItem>
  </nav>
</template>
```

#### Dashboard.vue Updates
Add check-in widgets to dashboard:

```vue
<template>
  <div class="space-y-6">
    <!-- Existing dashboard content -->
    
    <!-- Check-in Widgets -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <CheckInStats :stats="checkInStats" />
      <UpcomingCheckIns :check-ins="upcomingCheckIns" />
      <OverdueCheckIns :check-ins="overdueCheckIns" />
    </div>
  </div>
</template>
```

### 6. UI Patterns

#### Status Indicators
- Color-coded badges for status
- Icons for visual clarity
- Tooltips for additional context

#### Date Formatting
- Relative dates (e.g., "Due in 3 days")
- Absolute dates for precision
- Overdue highlighting

#### Loading States
- Skeleton components for cards
- Spinner overlays for actions
- Progressive loading for lists

#### Empty States
- Helpful messaging
- Action buttons when appropriate
- Illustration or icon

#### Error Handling
- Form validation errors
- API error messages
- Network error states
- Retry mechanisms

### 7. Responsive Design

#### Mobile Considerations
- Stacked layout for cards
- Touch-friendly buttons
- Swipe actions
- Simplified filters

#### Tablet Considerations
- Side-by-side layouts
- Touch and mouse interactions
- Optimized spacing

#### Desktop Considerations
- Multi-column layouts
- Hover states
- Keyboard shortcuts
- Advanced filtering

### 8. Accessibility

#### ARIA Labels
- Status announcements
- Action descriptions
- Form labels

#### Keyboard Navigation
- Tab order
- Enter/space activation
- Escape key handling

#### Screen Reader Support
- Semantic HTML
- Descriptive text
- Status announcements

### 9. Performance

#### Optimization Strategies
- Virtual scrolling for large lists
- Debounced search
- Lazy loading
- Cached data

#### Bundle Size
- Tree-shaking
- Code splitting
- Lazy components

### 10. Testing

#### Component Tests
- Props validation
- Event emission
- User interactions
- Accessibility

#### Integration Tests
- API integration
- Navigation flows
- State management
- Error scenarios 