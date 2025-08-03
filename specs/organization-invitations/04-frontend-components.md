# Organization Invitations - Frontend Components

## Overview
This document specifies the frontend components, pages, and user interface requirements for the organization invitation feature using Vue.js with Inertia.js and Tailwind CSS.

## Component Architecture

### Page Components
- **Invitations/Index.vue** - Main invitations management page
- **Invitations/Create.vue** - Send new invitation form
- **Invitations/Accept.vue** - Public invitation acceptance page

### Reusable Components
- **InvitationList.vue** - Display invitations with actions
- **SendInvitationForm.vue** - Form for sending invitations
- **InvitationStatus.vue** - Status indicator component
- **InvitationCard.vue** - Individual invitation display

## Page Components

### 1. Invitations/Index.vue

#### Purpose
Main page for organization admins to view and manage all invitations.

#### Layout
```vue
<template>
  <AppLayout title="Invitations">
    <template #header>
      <Heading>Organization Invitations</Heading>
    </template>

    <div class="space-y-6">
      <!-- Invitation Stats -->
      <InvitationStats :stats="stats" />
      
      <!-- Send New Invitation -->
      <Card>
        <CardHeader>
          <CardTitle>Send New Invitation</CardTitle>
          <CardDescription>
            Invite new members to join your organization
          </CardDescription>
        </CardHeader>
        <CardContent>
          <SendInvitationForm @invitation-sent="refreshInvitations" />
        </CardContent>
      </Card>

      <!-- Invitations List -->
      <Card>
        <CardHeader>
          <CardTitle>Pending Invitations</CardTitle>
        </CardHeader>
        <CardContent>
          <InvitationList 
            :invitations="invitations" 
            @resend="resendInvitation"
            @cancel="cancelInvitation"
          />
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
```

#### Props & Data
```typescript
interface Props {
  invitations: {
    data: Invitation[]
    meta: PaginationMeta
  }
  stats: {
    pending: number
    accepted: number
    expired: number
  }
}

interface Invitation {
  id: number
  email: string
  status: 'pending' | 'accepted' | 'expired'
  expires_at: string
  created_at: string
  invited_by: {
    id: number
    name: string
    email: string
  }
  organization: {
    id: number
    name: string
  }
}
```

#### Methods
- `refreshInvitations()` - Reload invitations list
- `resendInvitation(id: number)` - Resend specific invitation
- `cancelInvitation(id: number)` - Cancel specific invitation

---

### 2. Invitations/Create.vue

#### Purpose
Dedicated page for sending new invitations with enhanced form.

#### Layout
```vue
<template>
  <AppLayout title="Send Invitation">
    <template #header>
      <Breadcrumbs>
        <BreadcrumbItem>
          <BreadcrumbLink :href="route('invitations.index')">
            Invitations
          </BreadcrumbLink>
        </BreadcrumbItem>
        <BreadcrumbPage>Send Invitation</BreadcrumbPage>
      </Breadcrumbs>
    </template>

    <div class="max-w-2xl mx-auto">
      <Card>
        <CardHeader>
          <CardTitle>Send Organization Invitation</CardTitle>
          <CardDescription>
            Invite someone to join your organization. They'll receive an email with a secure link to create their account.
          </CardDescription>
        </CardHeader>
        <CardContent>
          <SendInvitationForm 
            @invitation-sent="handleInvitationSent"
            @cancel="goBack"
          />
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
```

#### Methods
- `handleInvitationSent(invitation: Invitation)` - Handle successful invitation
- `goBack()` - Navigate back to invitations list

---

### 3. Invitations/Accept.vue

#### Purpose
Public page for users to accept invitations and create accounts.

#### Layout
```vue
<template>
  <div class="min-h-screen bg-gray-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
    <div class="sm:mx-auto sm:w-full sm:max-w-md">
      <div class="text-center">
        <AppLogo class="mx-auto h-12 w-auto" />
        <h2 class="mt-6 text-3xl font-bold text-gray-900">
          Join {{ invitation.organization.name }}
        </h2>
        <p class="mt-2 text-sm text-gray-600">
          You've been invited by {{ invitation.invited_by.name }}
        </p>
      </div>
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
      <Card>
        <CardContent class="p-6">
          <form @submit.prevent="acceptInvitation">
            <div class="space-y-6">
              <!-- Email Display -->
              <div>
                <Label>Email Address</Label>
                <div class="mt-1 p-3 bg-gray-50 border rounded-md">
                  {{ invitation.email }}
                </div>
              </div>

              <!-- Name Input -->
              <div>
                <Label for="name">Full Name</Label>
                <Input
                  id="name"
                  v-model="form.name"
                  type="text"
                  required
                  :error="form.errors.name"
                />
                <InputError :message="form.errors.name" />
              </div>

              <!-- Password Input -->
              <div>
                <Label for="password">Password</Label>
                <Input
                  id="password"
                  v-model="form.password"
                  type="password"
                  required
                  :error="form.errors.password"
                />
                <InputError :message="form.errors.password" />
              </div>

              <!-- Password Confirmation -->
              <div>
                <Label for="password_confirmation">Confirm Password</Label>
                <Input
                  id="password_confirmation"
                  v-model="form.password_confirmation"
                  type="password"
                  required
                  :error="form.errors.password_confirmation"
                />
                <InputError :message="form.errors.password_confirmation" />
              </div>

              <!-- Submit Button -->
              <Button type="submit" class="w-full" :loading="form.processing">
                Create Account & Join Organization
              </Button>
            </div>
          </form>
        </CardContent>
      </Card>
    </div>
  </div>
</template>
```

#### Props & Data
```typescript
interface Props {
  invitation: {
    token: string
    email: string
    organization: {
      id: number
      name: string
      description?: string
    }
    invited_by: {
      name: string
    }
    expires_at: string
    is_valid: boolean
  }
}

interface FormData {
  name: string
  password: string
  password_confirmation: string
}
```

---

## Reusable Components

### 1. InvitationList.vue

#### Purpose
Display list of invitations with actions and status indicators.

#### Layout
```vue
<template>
  <div class="space-y-4">
    <!-- Filters -->
    <div class="flex items-center justify-between">
      <div class="flex space-x-2">
        <Button
          v-for="status in statusFilters"
          :key="status.value"
          :variant="activeStatus === status.value ? 'default' : 'outline'"
          size="sm"
          @click="setStatusFilter(status.value)"
        >
          {{ status.label }}
        </Button>
      </div>
      
      <div class="text-sm text-gray-500">
        {{ filteredInvitations.length }} invitation(s)
      </div>
    </div>

    <!-- Invitations -->
    <div class="space-y-3">
      <InvitationCard
        v-for="invitation in filteredInvitations"
        :key="invitation.id"
        :invitation="invitation"
        @resend="$emit('resend', invitation.id)"
        @cancel="$emit('cancel', invitation.id)"
      />
    </div>

    <!-- Empty State -->
    <div v-if="filteredInvitations.length === 0" class="text-center py-8">
      <div class="text-gray-400">
        <Icon name="mail" class="mx-auto h-12 w-12 mb-4" />
        <p class="text-lg font-medium">No invitations found</p>
        <p class="text-sm">No invitations match your current filter.</p>
      </div>
    </div>
  </div>
</template>
```

#### Props
```typescript
interface Props {
  invitations: Invitation[]
}

interface Emits {
  resend: [id: number]
  cancel: [id: number]
}
```

---

### 2. SendInvitationForm.vue

#### Purpose
Form component for sending new invitations.

#### Layout
```vue
<template>
  <form @submit.prevent="sendInvitation" class="space-y-6">
    <!-- Email Input -->
    <div>
      <Label for="email">Email Address</Label>
      <Input
        id="email"
        v-model="form.email"
        type="email"
        placeholder="colleague@example.com"
        required
        :error="form.errors.email"
      />
      <InputError :message="form.errors.email" />
      <p class="mt-1 text-sm text-gray-500">
        Enter the email address of the person you'd like to invite.
      </p>
    </div>

    <!-- Message Input -->
    <div>
      <Label for="message">Personal Message (Optional)</Label>
      <textarea
        id="message"
        v-model="form.message"
        rows="3"
        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
        placeholder="Add a personal message to your invitation..."
        :error="form.errors.message"
      />
      <InputError :message="form.errors.message" />
      <p class="mt-1 text-sm text-gray-500">
        This message will be included in the invitation email.
      </p>
    </div>

    <!-- Submit Button -->
    <div class="flex justify-end space-x-3">
      <Button
        type="button"
        variant="outline"
        @click="$emit('cancel')"
      >
        Cancel
      </Button>
      <Button type="submit" :loading="form.processing">
        Send Invitation
      </Button>
    </div>
  </form>
</template>
```

#### Props & Methods
```typescript
interface Props {
  organizationId?: number
}

interface Emits {
  'invitation-sent': [invitation: Invitation]
  cancel: []
}

interface FormData {
  email: string
  message?: string
}
```

---

### 3. InvitationCard.vue

#### Purpose
Individual invitation display with actions and status.

#### Layout
```vue
<template>
  <div class="border rounded-lg p-4 hover:bg-gray-50 transition-colors">
    <div class="flex items-start justify-between">
      <div class="flex-1">
        <!-- Email and Status -->
        <div class="flex items-center space-x-3">
          <div class="flex-1">
            <p class="font-medium text-gray-900">{{ invitation.email }}</p>
            <p class="text-sm text-gray-500">
              Invited by {{ invitation.invited_by.name }}
            </p>
          </div>
          <InvitationStatus :status="invitation.status" />
        </div>

        <!-- Details -->
        <div class="mt-2 text-sm text-gray-500">
          <p>Sent {{ formatDate(invitation.created_at) }}</p>
          <p v-if="invitation.status === 'pending'">
            Expires {{ formatDate(invitation.expires_at) }}
          </p>
        </div>
      </div>

      <!-- Actions -->
      <div v-if="invitation.status === 'pending'" class="flex space-x-2">
        <Button
          size="sm"
          variant="outline"
          @click="$emit('resend', invitation.id)"
        >
          Resend
        </Button>
        <Button
          size="sm"
          variant="destructive"
          @click="confirmCancel"
        >
          Cancel
        </Button>
      </div>
    </div>
  </div>
</template>
```

#### Props
```typescript
interface Props {
  invitation: Invitation
}

interface Emits {
  resend: [id: number]
  cancel: [id: number]
}
```

---

### 4. InvitationStatus.vue

#### Purpose
Status indicator component for invitations.

#### Layout
```vue
<template>
  <span
    :class="[
      'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
      statusClasses[status]
    ]"
  >
    <Icon
      :name="statusIcon[status]"
      class="w-3 h-3 mr-1"
    />
    {{ statusLabel[status] }}
  </span>
</template>
```

#### Props & Data
```typescript
interface Props {
  status: 'pending' | 'accepted' | 'expired'
}

const statusClasses = {
  pending: 'bg-yellow-100 text-yellow-800',
  accepted: 'bg-green-100 text-green-800',
  expired: 'bg-gray-100 text-gray-800'
}

const statusIcon = {
  pending: 'clock',
  accepted: 'check',
  expired: 'x'
}

const statusLabel = {
  pending: 'Pending',
  accepted: 'Accepted',
  expired: 'Expired'
}
```

---

## Navigation Integration

### Sidebar Navigation
Add invitations link to the main navigation for admin users:

```vue
<!-- In NavMain.vue -->
<SidebarMenuItem
  v-if="user.user_type === 'admin'"
  :href="route('invitations.index')"
  :active="route().current('invitations.*')"
>
  <Icon name="mail" class="w-4 h-4" />
  <span>Invitations</span>
  <SidebarMenuBadge v-if="pendingInvitationsCount > 0">
    {{ pendingInvitationsCount }}
  </SidebarMenuBadge>
</SidebarMenuItem>
```

### Breadcrumbs
Implement breadcrumb navigation for invitation pages:

```vue
<Breadcrumbs>
  <BreadcrumbItem>
    <BreadcrumbLink :href="route('dashboard')">
      Dashboard
    </BreadcrumbLink>
  </BreadcrumbItem>
  <BreadcrumbItem>
    <BreadcrumbLink :href="route('invitations.index')">
      Invitations
    </BreadcrumbLink>
  </BreadcrumbItem>
  <BreadcrumbPage>{{ currentPage }}</BreadcrumbPage>
</Breadcrumbs>
```

---

## State Management

### Form Handling
Use Inertia.js form handling for all invitation operations:

```typescript
// Send invitation
const form = useForm({
  email: '',
  message: ''
})

const sendInvitation = () => {
  form.post(route('invitations.store'), {
    onSuccess: (invitation) => {
      emit('invitation-sent', invitation)
      form.reset()
    }
  })
}

// Accept invitation
const form = useForm({
  name: '',
  password: '',
  password_confirmation: ''
})

const acceptInvitation = () => {
  form.post(route('invitations.accept.store', { token: invitation.token }), {
    onSuccess: () => {
      window.location.href = route('dashboard')
    }
  })
}
```

### Loading States
Implement loading states for all async operations:

```vue
<Button 
  type="submit" 
  :loading="form.processing"
  :disabled="form.processing"
>
  {{ form.processing ? 'Sending...' : 'Send Invitation' }}
</Button>
```

---

## Error Handling

### Form Validation
Display validation errors inline with form fields:

```vue
<Input
  v-model="form.email"
  :error="form.errors.email"
/>
<InputError :message="form.errors.email" />
```

### Toast Notifications
Show success/error messages for user feedback:

```typescript
// Success notification
toast.success('Invitation sent successfully!')

// Error notification
toast.error('Failed to send invitation. Please try again.')
```

---

## Responsive Design

### Mobile-First Approach
Ensure all components work well on mobile devices:

```vue
<!-- Responsive grid for invitation cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
  <InvitationCard
    v-for="invitation in invitations"
    :key="invitation.id"
    :invitation="invitation"
  />
</div>
```

### Touch-Friendly Actions
Make action buttons large enough for touch interaction:

```vue
<Button
  size="lg"
  class="min-h-[44px]"
  @click="handleAction"
>
  Send Invitation
</Button>
```

---

## Accessibility

### ARIA Labels
Add proper ARIA labels for screen readers:

```vue
<Button
  aria-label="Resend invitation to {{ invitation.email }}"
  @click="$emit('resend', invitation.id)"
>
  Resend
</Button>
```

### Keyboard Navigation
Ensure all interactive elements are keyboard accessible:

```vue
<div
  tabindex="0"
  @keydown.enter="handleClick"
  @keydown.space="handleClick"
  role="button"
>
  <!-- Card content -->
</div>
```

---

## Testing Considerations

### Component Testing
Test all components with various states:

```typescript
// Test invitation card with different statuses
describe('InvitationCard', () => {
  it('shows pending status correctly', () => {
    // Test implementation
  })
  
  it('shows accepted status correctly', () => {
    // Test implementation
  })
  
  it('emits resend event when resend button clicked', () => {
    // Test implementation
  })
})
```

### Integration Testing
Test complete user flows:

```typescript
// Test complete invitation flow
describe('Invitation Flow', () => {
  it('admin can send invitation', () => {
    // Test implementation
  })
  
  it('user can accept invitation', () => {
    // Test implementation
  })
})
``` 