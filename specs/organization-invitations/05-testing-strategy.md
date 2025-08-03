# Organization Invitations - Testing Strategy

## Overview
This document outlines the comprehensive testing strategy for the organization invitation feature, covering unit tests, feature tests, integration tests, and frontend component tests.

## Testing Pyramid

### 1. Unit Tests (Foundation)
- Model relationships and scopes
- Request validation rules
- Notification classes
- Helper functions

### 2. Feature Tests (Integration)
- Complete invitation workflows
- Email sending and delivery
- Authorization and permissions
- Database operations

### 3. Component Tests (Frontend)
- Vue component behavior
- User interactions
- Form validation
- State management

## Unit Tests

### 1. OrganizationInvitation Model Tests

#### Test File: `tests/Unit/Models/OrganizationInvitationTest.php`

```php
<?php

namespace Tests\Unit\Models;

use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationInvitationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_belongs_to_an_organization()
    {
        $organization = Organization::factory()->create();
        $invitation = OrganizationInvitation::factory()->for($organization)->create();

        $this->assertInstanceOf(Organization::class, $invitation->organization);
        $this->assertEquals($organization->id, $invitation->organization->id);
    }

    /** @test */
    public function it_belongs_to_an_inviter()
    {
        $user = User::factory()->create();
        $invitation = OrganizationInvitation::factory()->for($user, 'invitedBy')->create();

        $this->assertInstanceOf(User::class, $invitation->invitedBy);
        $this->assertEquals($user->id, $invitation->invitedBy->id);
    }

    /** @test */
    public function it_generates_unique_token_on_creation()
    {
        $invitation1 = OrganizationInvitation::factory()->create();
        $invitation2 = OrganizationInvitation::factory()->create();

        $this->assertNotEquals($invitation1->token, $invitation2->token);
        $this->assertEquals(64, strlen($invitation1->token));
    }

    /** @test */
    public function it_sets_expiration_date_on_creation()
    {
        $invitation = OrganizationInvitation::factory()->create();

        $this->assertNotNull($invitation->expires_at);
        $this->assertTrue($invitation->expires_at->isAfter(now()));
        $this->assertTrue($invitation->expires_at->isBefore(now()->addDays(8)));
    }

    /** @test */
    public function it_can_scope_to_pending_invitations()
    {
        OrganizationInvitation::factory()->pending()->create();
        OrganizationInvitation::factory()->accepted()->create();
        OrganizationInvitation::factory()->expired()->create();

        $pendingInvitations = OrganizationInvitation::pending()->get();

        $this->assertEquals(1, $pendingInvitations->count());
        $this->assertEquals('pending', $pendingInvitations->first()->status);
    }

    /** @test */
    public function it_can_scope_to_valid_invitations()
    {
        // Valid pending invitation
        OrganizationInvitation::factory()->pending()->create();
        // Expired invitation
        OrganizationInvitation::factory()->expired()->create();
        // Already accepted
        OrganizationInvitation::factory()->accepted()->create();

        $validInvitations = OrganizationInvitation::valid()->get();

        $this->assertEquals(1, $validInvitations->count());
        $this->assertEquals('pending', $validInvitations->first()->status);
    }

    /** @test */
    public function it_can_mark_as_accepted()
    {
        $invitation = OrganizationInvitation::factory()->pending()->create();

        $invitation->markAsAccepted();

        $this->assertEquals('accepted', $invitation->fresh()->status);
        $this->assertNotNull($invitation->fresh()->accepted_at);
    }

    /** @test */
    public function it_can_mark_as_expired()
    {
        $invitation = OrganizationInvitation::factory()->pending()->create();

        $invitation->markAsExpired();

        $this->assertEquals('expired', $invitation->fresh()->status);
    }

    /** @test */
    public function it_can_check_if_expired()
    {
        $validInvitation = OrganizationInvitation::factory()->pending()->create();
        $expiredInvitation = OrganizationInvitation::factory()->expired()->create();

        $this->assertFalse($validInvitation->isExpired());
        $this->assertTrue($expiredInvitation->isExpired());
    }
}
```

### 2. Request Validation Tests

#### Test File: `tests/Unit/Requests/SendInvitationRequestTest.php`

```php
<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\SendInvitationRequest;
use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class SendInvitationRequestTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_validates_required_fields()
    {
        $rules = (new SendInvitationRequest())->rules();

        $validator = Validator::make([], $rules);

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('email'));
    }

    /** @test */
    public function it_validates_email_format()
    {
        $organization = Organization::factory()->create();
        $rules = (new SendInvitationRequest())->rules();

        $validator = Validator::make([
            'email' => 'invalid-email'
        ], $rules);

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('email'));
    }

    /** @test */
    public function it_prevents_duplicate_pending_invitations()
    {
        $organization = Organization::factory()->create();
        $existingInvitation = OrganizationInvitation::factory()
            ->for($organization)
            ->pending()
            ->create(['email' => 'test@example.com']);

        $rules = (new SendInvitationRequest())->rules();

        $validator = Validator::make([
            'email' => 'test@example.com'
        ], $rules);

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('email'));
    }

    /** @test */
    public function it_prevents_inviting_existing_members()
    {
        $organization = Organization::factory()->create();
        $existingUser = User::factory()->for($organization)->create(['email' => 'member@example.com']);

        $rules = (new SendInvitationRequest())->rules();

        $validator = Validator::make([
            'email' => 'member@example.com'
        ], $rules);

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('email'));
    }

    /** @test */
    public function it_allows_valid_invitation_email()
    {
        $organization = Organization::factory()->create();
        $rules = (new SendInvitationRequest())->rules();

        $validator = Validator::make([
            'email' => 'newuser@example.com',
            'message' => 'Welcome to our organization!'
        ], $rules);

        $this->assertFalse($validator->fails());
    }
}
```

### 3. Notification Tests

#### Test File: `tests/Unit/Notifications/OrganizationInvitationNotificationTest.php`

```php
<?php

namespace Tests\Unit\Notifications;

use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\User;
use App\Notifications\OrganizationInvitationNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class OrganizationInvitationNotificationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_sends_notification_to_invited_email()
    {
        $organization = Organization::factory()->create();
        $inviter = User::factory()->for($organization)->create();
        $invitation = OrganizationInvitation::factory()
            ->for($organization)
            ->for($inviter, 'invitedBy')
            ->create(['email' => 'invited@example.com']);

        $notification = new OrganizationInvitationNotification($invitation);

        $this->assertEquals('invited@example.com', $notification->toMail($invitation)->to[0]['address']);
    }

    /** @test */
    public function it_contains_invitation_link()
    {
        $organization = Organization::factory()->create();
        $inviter = User::factory()->for($organization)->create();
        $invitation = OrganizationInvitation::factory()
            ->for($organization)
            ->for($inviter, 'invitedBy')
            ->create();

        $notification = new OrganizationInvitationNotification($invitation);
        $mail = $notification->toMail($invitation);

        $this->assertStringContainsString($invitation->token, $mail->actionUrl);
        $this->assertStringContainsString('invitations', $mail->actionUrl);
    }

    /** @test */
    public function it_contains_organization_information()
    {
        $organization = Organization::factory()->create(['name' => 'Acme Corp']);
        $inviter = User::factory()->for($organization)->create(['name' => 'John Admin']);
        $invitation = OrganizationInvitation::factory()
            ->for($organization)
            ->for($inviter, 'invitedBy')
            ->create();

        $notification = new OrganizationInvitationNotification($invitation);
        $mail = $notification->toMail($invitation);

        $this->assertStringContainsString('Acme Corp', $mail->subject);
        $this->assertStringContainsString('John Admin', $mail->introLines[0]);
    }

    /** @test */
    public function it_uses_correct_mail_channel()
    {
        $invitation = OrganizationInvitation::factory()->create();
        $notification = new OrganizationInvitationNotification($invitation);

        $this->assertContains('mail', $notification->via(null));
    }
}
```

## Feature Tests

### 1. Invitation Management Tests

#### Test File: `tests/Feature/Invitations/InvitationManagementTest.php`

```php
<?php

namespace Tests\Feature\Invitations;

use App\Enums\UserType;
use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class InvitationManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    /** @test */
    public function admin_can_view_invitations_page()
    {
        $organization = Organization::factory()->create();
        $admin = User::factory()->for($organization)->create(['user_type' => UserType::ADMIN]);

        $response = $this->actingAs($admin)->get(route('invitations.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('Invitations/Index'));
    }

    /** @test */
    public function non_admin_cannot_access_invitations_page()
    {
        $organization = Organization::factory()->create();
        $member = User::factory()->for($organization)->create(['user_type' => UserType::MEMBER]);

        $response = $this->actingAs($member)->get(route('invitations.index'));

        $response->assertForbidden();
    }

    /** @test */
    public function admin_can_send_invitation()
    {
        $organization = Organization::factory()->create();
        $admin = User::factory()->for($organization)->create(['user_type' => UserType::ADMIN]);

        $response = $this->actingAs($admin)->post(route('invitations.store'), [
            'email' => 'newuser@example.com',
            'message' => 'Welcome to our organization!'
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('organization_invitations', [
            'email' => 'newuser@example.com',
            'organization_id' => $organization->id,
            'invited_by_user_id' => $admin->id,
            'status' => 'pending'
        ]);
    }

    /** @test */
    public function invitation_sends_email_notification()
    {
        $organization = Organization::factory()->create();
        $admin = User::factory()->for($organization)->create(['user_type' => UserType::ADMIN]);

        $this->actingAs($admin)->post(route('invitations.store'), [
            'email' => 'newuser@example.com'
        ]);

        Mail::assertSent(OrganizationInvitationNotification::class, function ($mail) {
            return $mail->hasTo('newuser@example.com');
        });
    }

    /** @test */
    public function admin_cannot_invite_existing_member()
    {
        $organization = Organization::factory()->create();
        $admin = User::factory()->for($organization)->create(['user_type' => UserType::ADMIN]);
        $existingMember = User::factory()->for($organization)->create(['email' => 'member@example.com']);

        $response = $this->actingAs($admin)->post(route('invitations.store'), [
            'email' => 'member@example.com'
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertDatabaseMissing('organization_invitations', [
            'email' => 'member@example.com'
        ]);
    }

    /** @test */
    public function admin_can_resend_invitation()
    {
        $organization = Organization::factory()->create();
        $admin = User::factory()->for($organization)->create(['user_type' => UserType::ADMIN]);
        $invitation = OrganizationInvitation::factory()
            ->for($organization)
            ->for($admin, 'invitedBy')
            ->pending()
            ->create();

        $response = $this->actingAs($admin)->post(route('invitations.resend', $invitation));

        $response->assertRedirect();
        Mail::assertSent(OrganizationInvitationNotification::class, 2); // Original + resend
    }

    /** @test */
    public function admin_can_cancel_invitation()
    {
        $organization = Organization::factory()->create();
        $admin = User::factory()->for($organization)->create(['user_type' => UserType::ADMIN]);
        $invitation = OrganizationInvitation::factory()
            ->for($organization)
            ->for($admin, 'invitedBy')
            ->pending()
            ->create();

        $response = $this->actingAs($admin)->delete(route('invitations.destroy', $invitation));

        $response->assertRedirect();
        $this->assertDatabaseMissing('organization_invitations', ['id' => $invitation->id]);
    }

    /** @test */
    public function admin_cannot_manage_other_organization_invitations()
    {
        $organization1 = Organization::factory()->create();
        $organization2 = Organization::factory()->create();
        $admin = User::factory()->for($organization1)->create(['user_type' => UserType::ADMIN]);
        $invitation = OrganizationInvitation::factory()
            ->for($organization2)
            ->pending()
            ->create();

        $response = $this->actingAs($admin)->delete(route('invitations.destroy', $invitation));

        $response->assertForbidden();
    }
}
```

### 2. Invitation Acceptance Tests

#### Test File: `tests/Feature/Invitations/InvitationAcceptanceTest.php`

```php
<?php

namespace Tests\Feature\Invitations;

use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvitationAcceptanceTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function user_can_view_invitation_acceptance_page()
    {
        $organization = Organization::factory()->create(['name' => 'Acme Corp']);
        $inviter = User::factory()->for($organization)->create(['name' => 'John Admin']);
        $invitation = OrganizationInvitation::factory()
            ->for($organization)
            ->for($inviter, 'invitedBy')
            ->pending()
            ->create();

        $response = $this->get(route('invitations.accept', $invitation->token));

        $response->assertOk();
        $response->assertInertia(fn ($page) => 
            $page->component('Invitations/Accept')
                ->has('invitation')
        );
    }

    /** @test */
    public function user_cannot_view_expired_invitation()
    {
        $invitation = OrganizationInvitation::factory()->expired()->create();

        $response = $this->get(route('invitations.accept', $invitation->token));

        $response->assertNotFound();
    }

    /** @test */
    public function user_cannot_view_accepted_invitation()
    {
        $invitation = OrganizationInvitation::factory()->accepted()->create();

        $response = $this->get(route('invitations.accept', $invitation->token));

        $response->assertGone();
    }

    /** @test */
    public function user_can_accept_invitation_and_create_account()
    {
        $organization = Organization::factory()->create();
        $inviter = User::factory()->for($organization)->create();
        $invitation = OrganizationInvitation::factory()
            ->for($organization)
            ->for($inviter, 'invitedBy')
            ->pending()
            ->create(['email' => 'newuser@example.com']);

        $response = $this->post(route('invitations.accept.store', $invitation->token), [
            'name' => 'New User',
            'password' => 'password123',
            'password_confirmation' => 'password123'
        ]);

        $response->assertRedirect(route('dashboard'));

        // Check user was created
        $this->assertDatabaseHas('users', [
            'email' => 'newuser@example.com',
            'name' => 'New User',
            'organization_id' => $organization->id
        ]);

        // Check invitation was marked as accepted
        $this->assertDatabaseHas('organization_invitations', [
            'id' => $invitation->id,
            'status' => 'accepted'
        ]);
    }

    /** @test */
    public function user_cannot_accept_invitation_with_invalid_data()
    {
        $invitation = OrganizationInvitation::factory()->pending()->create();

        $response = $this->post(route('invitations.accept.store', $invitation->token), [
            'name' => '',
            'password' => 'short',
            'password_confirmation' => 'different'
        ]);

        $response->assertSessionHasErrors(['name', 'password']);
        $this->assertDatabaseMissing('users', ['email' => $invitation->email]);
    }

    /** @test */
    public function user_cannot_accept_invitation_with_existing_email()
    {
        $organization = Organization::factory()->create();
        $invitation = OrganizationInvitation::factory()
            ->for($organization)
            ->pending()
            ->create(['email' => 'existing@example.com']);
        
        // Create user with same email
        User::factory()->create(['email' => 'existing@example.com']);

        $response = $this->post(route('invitations.accept.store', $invitation->token), [
            'name' => 'New User',
            'password' => 'password123',
            'password_confirmation' => 'password123'
        ]);

        $response->assertSessionHasErrors();
    }
}
```

### 3. Email Integration Tests

#### Test File: `tests/Feature/Invitations/EmailIntegrationTest.php`

```php
<?php

namespace Tests\Feature\Invitations;

use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\User;
use App\Notifications\OrganizationInvitationNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class EmailIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    /** @test */
    public function invitation_email_contains_correct_information()
    {
        $organization = Organization::factory()->create(['name' => 'Acme Corp']);
        $inviter = User::factory()->for($organization)->create(['name' => 'John Admin']);
        $invitation = OrganizationInvitation::factory()
            ->for($organization)
            ->for($inviter, 'invitedBy')
            ->create(['email' => 'invited@example.com']);

        $notification = new OrganizationInvitationNotification($invitation);
        $mail = $notification->toMail($invitation);

        $this->assertStringContainsString('Acme Corp', $mail->subject);
        $this->assertStringContainsString('John Admin', $mail->introLines[0]);
        $this->assertStringContainsString($invitation->token, $mail->actionUrl);
    }

    /** @test */
    public function invitation_email_sent_to_correct_address()
    {
        $organization = Organization::factory()->create();
        $inviter = User::factory()->for($organization)->create();
        $invitation = OrganizationInvitation::factory()
            ->for($organization)
            ->for($inviter, 'invitedBy')
            ->create(['email' => 'test@example.com']);

        $this->actingAs($inviter)->post(route('invitations.store'), [
            'email' => 'test@example.com'
        ]);

        Mail::assertSent(OrganizationInvitationNotification::class, function ($mail) {
            return $mail->hasTo('test@example.com');
        });
    }

    /** @test */
    public function resend_invitation_sends_new_email()
    {
        $organization = Organization::factory()->create();
        $admin = User::factory()->for($organization)->create(['user_type' => 'admin']);
        $invitation = OrganizationInvitation::factory()
            ->for($organization)
            ->for($admin, 'invitedBy')
            ->pending()
            ->create();

        $this->actingAs($admin)->post(route('invitations.resend', $invitation));

        Mail::assertSent(OrganizationInvitationNotification::class, 2);
    }
}
```

## Component Tests

### 1. Vue Component Tests

#### Test File: `tests/Component/InvitationCard.test.ts`

```typescript
import { mount } from '@vue/test-utils'
import { describe, it, expect } from 'vitest'
import InvitationCard from '@/components/InvitationCard.vue'

describe('InvitationCard', () => {
  const mockInvitation = {
    id: 1,
    email: 'test@example.com',
    status: 'pending',
    expires_at: '2024-01-15T10:00:00Z',
    created_at: '2024-01-08T10:00:00Z',
    invited_by: {
      id: 1,
      name: 'Admin User',
      email: 'admin@example.com'
    },
    organization: {
      id: 1,
      name: 'Acme Corp'
    }
  }

  it('displays invitation email', () => {
    const wrapper = mount(InvitationCard, {
      props: { invitation: mockInvitation }
    })

    expect(wrapper.text()).toContain('test@example.com')
  })

  it('displays inviter name', () => {
    const wrapper = mount(InvitationCard, {
      props: { invitation: mockInvitation }
    })

    expect(wrapper.text()).toContain('Admin User')
  })

  it('shows action buttons for pending invitations', () => {
    const wrapper = mount(InvitationCard, {
      props: { invitation: mockInvitation }
    })

    expect(wrapper.find('[data-test="resend-button"]').exists()).toBe(true)
    expect(wrapper.find('[data-test="cancel-button"]').exists()).toBe(true)
  })

  it('does not show action buttons for accepted invitations', () => {
    const acceptedInvitation = { ...mockInvitation, status: 'accepted' }
    const wrapper = mount(InvitationCard, {
      props: { invitation: acceptedInvitation }
    })

    expect(wrapper.find('[data-test="resend-button"]').exists()).toBe(false)
    expect(wrapper.find('[data-test="cancel-button"]').exists()).toBe(false)
  })

  it('emits resend event when resend button clicked', async () => {
    const wrapper = mount(InvitationCard, {
      props: { invitation: mockInvitation }
    })

    await wrapper.find('[data-test="resend-button"]').trigger('click')

    expect(wrapper.emitted('resend')).toBeTruthy()
    expect(wrapper.emitted('resend')[0]).toEqual([1])
  })

  it('emits cancel event when cancel button clicked', async () => {
    const wrapper = mount(InvitationCard, {
      props: { invitation: mockInvitation }
    })

    await wrapper.find('[data-test="cancel-button"]').trigger('click')

    expect(wrapper.emitted('cancel')).toBeTruthy()
    expect(wrapper.emitted('cancel')[0]).toEqual([1])
  })
})
```

### 2. Form Component Tests

#### Test File: `tests/Component/SendInvitationForm.test.ts`

```typescript
import { mount } from '@vue/test-utils'
import { describe, it, expect, vi } from 'vitest'
import SendInvitationForm from '@/components/SendInvitationForm.vue'

describe('SendInvitationForm', () => {
  it('renders email input field', () => {
    const wrapper = mount(SendInvitationForm)

    expect(wrapper.find('[data-test="email-input"]').exists()).toBe(true)
  })

  it('renders message textarea', () => {
    const wrapper = mount(SendInvitationForm)

    expect(wrapper.find('[data-test="message-textarea"]').exists()).toBe(true)
  })

  it('emits invitation-sent event on successful submission', async () => {
    const wrapper = mount(SendInvitationForm)

    await wrapper.find('[data-test="email-input"]').setValue('test@example.com')
    await wrapper.find('[data-test="message-textarea"]').setValue('Welcome!')
    await wrapper.find('form').trigger('submit')

    expect(wrapper.emitted('invitation-sent')).toBeTruthy()
  })

  it('validates required email field', async () => {
    const wrapper = mount(SendInvitationForm)

    await wrapper.find('form').trigger('submit')

    expect(wrapper.text()).toContain('Email is required')
  })

  it('validates email format', async () => {
    const wrapper = mount(SendInvitationForm)

    await wrapper.find('[data-test="email-input"]').setValue('invalid-email')
    await wrapper.find('form').trigger('submit')

    expect(wrapper.text()).toContain('Please enter a valid email')
  })

  it('emits cancel event when cancel button clicked', async () => {
    const wrapper = mount(SendInvitationForm)

    await wrapper.find('[data-test="cancel-button"]').trigger('click')

    expect(wrapper.emitted('cancel')).toBeTruthy()
  })
})
```

## Integration Tests

### 1. Complete Workflow Tests

#### Test File: `tests/Feature/Invitations/CompleteWorkflowTest.php`

```php
<?php

namespace Tests\Feature\Invitations;

use App\Enums\UserType;
use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CompleteWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    /** @test */
    public function complete_invitation_workflow()
    {
        // 1. Admin sends invitation
        $organization = Organization::factory()->create(['name' => 'Acme Corp']);
        $admin = User::factory()->for($organization)->create(['user_type' => UserType::ADMIN]);

        $response = $this->actingAs($admin)->post(route('invitations.store'), [
            'email' => 'newuser@example.com',
            'message' => 'Welcome to our team!'
        ]);

        $response->assertRedirect();

        // 2. Verify invitation was created
        $this->assertDatabaseHas('organization_invitations', [
            'email' => 'newuser@example.com',
            'organization_id' => $organization->id,
            'status' => 'pending'
        ]);

        // 3. Verify email was sent
        Mail::assertSent(OrganizationInvitationNotification::class);

        // 4. Get invitation token
        $invitation = OrganizationInvitation::where('email', 'newuser@example.com')->first();

        // 5. User visits invitation page
        $response = $this->get(route('invitations.accept', $invitation->token));
        $response->assertOk();

        // 6. User accepts invitation
        $response = $this->post(route('invitations.accept.store', $invitation->token), [
            'name' => 'New User',
            'password' => 'password123',
            'password_confirmation' => 'password123'
        ]);

        $response->assertRedirect(route('dashboard'));

        // 7. Verify user was created and associated with organization
        $this->assertDatabaseHas('users', [
            'email' => 'newuser@example.com',
            'name' => 'New User',
            'organization_id' => $organization->id
        ]);

        // 8. Verify invitation was marked as accepted
        $this->assertDatabaseHas('organization_invitations', [
            'id' => $invitation->id,
            'status' => 'accepted'
        ]);
    }

    /** @test */
    public function invitation_management_workflow()
    {
        $organization = Organization::factory()->create();
        $admin = User::factory()->for($organization)->create(['user_type' => UserType::ADMIN]);

        // 1. Send multiple invitations
        $this->actingAs($admin)->post(route('invitations.store'), [
            'email' => 'user1@example.com'
        ]);
        $this->actingAs($admin)->post(route('invitations.store'), [
            'email' => 'user2@example.com'
        ]);

        // 2. View invitations list
        $response = $this->actingAs($admin)->get(route('invitations.index'));
        $response->assertOk();

        // 3. Resend invitation
        $invitation = OrganizationInvitation::where('email', 'user1@example.com')->first();
        $response = $this->actingAs($admin)->post(route('invitations.resend', $invitation));
        $response->assertRedirect();

        // 4. Cancel invitation
        $invitation = OrganizationInvitation::where('email', 'user2@example.com')->first();
        $response = $this->actingAs($admin)->delete(route('invitations.destroy', $invitation));
        $response->assertRedirect();

        // 5. Verify only one invitation remains
        $this->assertDatabaseCount('organization_invitations', 1);
        $this->assertDatabaseHas('organization_invitations', [
            'email' => 'user1@example.com'
        ]);
    }
}
```

## Performance Tests

### 1. Database Performance Tests

#### Test File: `tests/Performance/InvitationPerformanceTest.php`

```php
<?php

namespace Tests\Performance;

use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvitationPerformanceTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function invitation_queries_are_optimized()
    {
        $organization = Organization::factory()->create();
        
        // Create many invitations
        OrganizationInvitation::factory()
            ->count(100)
            ->for($organization)
            ->create();

        $startTime = microtime(true);
        
        // Test invitation listing query
        $invitations = $organization->invitations()->with(['invitedBy', 'organization'])->get();
        
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        // Should complete within reasonable time
        $this->assertLessThan(0.1, $executionTime);
        $this->assertEquals(100, $invitations->count());
    }

    /** @test */
    public function invitation_indexes_are_effective()
    {
        $organization = Organization::factory()->create();
        
        // Create invitations with different statuses
        OrganizationInvitation::factory()
            ->count(50)
            ->for($organization)
            ->pending()
            ->create();

        OrganizationInvitation::factory()
            ->count(30)
            ->for($organization)
            ->accepted()
            ->create();

        $startTime = microtime(true);
        
        // Test status-based query
        $pendingInvitations = OrganizationInvitation::where('status', 'pending')->get();
        
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        $this->assertLessThan(0.05, $executionTime);
        $this->assertEquals(50, $pendingInvitations->count());
    }
}
```

## Test Data Factories

### 1. OrganizationInvitationFactory

```php
<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class OrganizationInvitationFactory extends Factory
{
    protected $model = OrganizationInvitation::class;

    public function definition(): array
    {
        return [
            'email' => $this->faker->unique()->safeEmail(),
            'organization_id' => Organization::factory(),
            'invited_by_user_id' => User::factory(),
            'token' => Str::random(64),
            'status' => $this->faker->randomElement(['pending', 'accepted', 'expired']),
            'expires_at' => now()->addDays(7),
            'accepted_at' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'expires_at' => now()->addDays(7),
            'accepted_at' => null,
        ]);
    }

    public function accepted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'accepted',
            'accepted_at' => now(),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'expired',
            'expires_at' => now()->subDays(1),
            'accepted_at' => null,
        ]);
    }
}
```

## Test Configuration

### 1. PHPUnit Configuration

```xml
<!-- phpunit.xml -->
<testsuites>
    <testsuite name="Unit">
        <directory suffix="Test.php">./tests/Unit</directory>
    </testsuite>
    <testsuite name="Feature">
        <directory suffix="Test.php">./tests/Feature</directory>
    </testsuite>
    <testsuite name="Performance">
        <directory suffix="Test.php">./tests/Performance</directory>
    </testsuite>
</testsuites>
```

### 2. Test Environment Setup

```php
// config/testing.php
return [
    'mail' => [
        'default' => 'array',
    ],
    'queue' => [
        'default' => 'sync',
    ],
];
```

## Continuous Integration

### 1. GitHub Actions Workflow

```yaml
# .github/workflows/tests.yml
name: Tests

on: [push, pull_request]

jobs:
  tests:
    runs-on: ubuntu-latest
    
    steps:
    - uses: actions/checkout@v3
    
    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: '8.2'
        
    - name: Install dependencies
      run: composer install --prefer-dist --no-progress
        
    - name: Copy environment file
      run: cp .env.example .env
        
    - name: Generate key
      run: php artisan key:generate
        
    - name: Create database
      run: |
        mysql -e 'CREATE DATABASE foxtrot_testing;'
        
    - name: Run migrations
      run: php artisan migrate --env=testing
        
    - name: Run tests
      run: php artisan test --coverage
```

## Coverage Goals

### 1. Code Coverage Targets
- **Models**: 95% coverage
- **Controllers**: 90% coverage
- **Requests**: 100% coverage
- **Notifications**: 100% coverage
- **Components**: 85% coverage

### 2. Test Categories
- **Unit Tests**: 60% of test suite
- **Feature Tests**: 30% of test suite
- **Component Tests**: 10% of test suite

## Monitoring and Maintenance

### 1. Test Metrics
- Track test execution time
- Monitor test coverage trends
- Alert on failing tests
- Performance regression detection

### 2. Test Maintenance
- Regular test data updates
- Dependency updates
- Test environment maintenance
- Documentation updates 