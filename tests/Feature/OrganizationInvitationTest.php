<?php

use App\Enums\UserType;
use App\Enums\OrganizationInvitationStatus;
use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\User;
use App\Notifications\OrganizationInvitationNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use function Pest\Laravel\{actingAs, get, post, delete};

uses(RefreshDatabase::class);

test('admin can send invitation', function () {
    Notification::fake();

    $organization = Organization::factory()->create();
    $admin = User::factory()->create([
        'user_type' => UserType::ADMIN,
        'organization_id' => $organization->id,
    ]);

    $response = actingAs($admin)
        ->post(route('invitations.store'), [
            'email' => 'test@example.com',
        ]);

    $response->assertRedirect(route('invitations.index'));
    $response->assertSessionHas('success');

    expect(OrganizationInvitation::where('email', 'test@example.com')->first())
        ->toHaveKey('organization_id', $organization->id)
        ->toHaveKey('invited_by_user_id', $admin->id)
        ->toHaveKey('status', OrganizationInvitationStatus::PENDING->value);

    Notification::assertSentTo(
        OrganizationInvitation::first(),
        OrganizationInvitationNotification::class
    );
});

test('non admin cannot send invitation', function () {
    $organization = Organization::factory()->create();
    $member = User::factory()->create([
        'user_type' => UserType::MEMBER,
        'organization_id' => $organization->id,
    ]);

    $response = actingAs($member)
        ->post(route('invitations.store'), [
            'email' => 'test@example.com',
        ]);

    $response->assertStatus(403);
});

test('cannot invite existing user', function () {
    $organization = Organization::factory()->create();
    $admin = User::factory()->create([
        'user_type' => UserType::ADMIN,
        'organization_id' => $organization->id,
    ]);
    $existingUser = User::factory()->create([
        'organization_id' => $organization->id,
    ]);

    $response = actingAs($admin)
        ->post(route('invitations.store'), [
            'email' => $existingUser->email,
        ]);

    $response->assertSessionHasErrors('email');
});

test('cannot invite same email twice', function () {
    $organization = Organization::factory()->create();
    $admin = User::factory()->create([
        'user_type' => UserType::ADMIN,
        'organization_id' => $organization->id,
    ]);

    // Send first invitation
    actingAs($admin)
        ->post(route('invitations.store'), [
            'email' => 'test@example.com',
        ]);

    // Try to send second invitation to same email
    $response = actingAs($admin)
        ->post(route('invitations.store'), [
            'email' => 'test@example.com',
        ]);

    $response->assertSessionHasErrors('email');
});

test('user can accept invitation', function () {
    $organization = Organization::factory()->create();
    $admin = User::factory()->create([
        'user_type' => UserType::ADMIN,
        'organization_id' => $organization->id,
    ]);

    $invitation = OrganizationInvitation::factory()->create([
        'email' => 'newuser@example.com',
        'organization_id' => $organization->id,
        'invited_by_user_id' => $admin->id,
        'status' => OrganizationInvitationStatus::PENDING,
    ]);

    $response = post(route('invitations.accept.store', $invitation->token), [
        'name' => 'New User',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertRedirect(route('dashboard'));

    expect(User::where('email', 'newuser@example.com')->first())
        ->toHaveKey('name', 'New User')
        ->toHaveKey('organization_id', $organization->id)
        ->toHaveKey('user_type', UserType::MEMBER->value);

    expect(OrganizationInvitation::find($invitation->id))
        ->toHaveKey('status', OrganizationInvitationStatus::ACCEPTED->value);
});

test('cannot accept expired invitation', function () {
    $invitation = OrganizationInvitation::factory()->create([
        'status' => OrganizationInvitationStatus::EXPIRED,
        'expires_at' => now()->subDays(1),
    ]);

    $response = get(route('invitations.accept', $invitation->token));

    $response->assertStatus(404);
});

test('admin can resend invitation', function () {
    Notification::fake();

    $organization = Organization::factory()->create();
    $admin = User::factory()->create([
        'user_type' => UserType::ADMIN,
        'organization_id' => $organization->id,
    ]);

    $invitation = OrganizationInvitation::factory()->create([
        'organization_id' => $organization->id,
        'invited_by_user_id' => $admin->id,
        'status' => OrganizationInvitationStatus::PENDING,
    ]);

    $response = actingAs($admin)
        ->post(route('invitations.resend', $invitation));

    $response->assertRedirect(route('invitations.index'));
    $response->assertSessionHas('success');

    Notification::assertSentTo(
        $invitation->fresh(),
        OrganizationInvitationNotification::class
    );
});

test('admin can cancel invitation', function () {
    $organization = Organization::factory()->create();
    $admin = User::factory()->create([
        'user_type' => UserType::ADMIN,
        'organization_id' => $organization->id,
    ]);

    $invitation = OrganizationInvitation::factory()->create([
        'organization_id' => $organization->id,
        'invited_by_user_id' => $admin->id,
    ]);

    $response = actingAs($admin)
        ->delete(route('invitations.destroy', $invitation));

    $response->assertRedirect(route('invitations.index'));
    $response->assertSessionHas('success');

    expect(OrganizationInvitation::find($invitation->id))->toBeNull();
});

test('admin can view invitations', function () {
    $organization = Organization::factory()->create();
    $admin = User::factory()->create([
        'user_type' => UserType::ADMIN,
        'organization_id' => $organization->id,
    ]);

    $invitation = OrganizationInvitation::factory()->create([
        'organization_id' => $organization->id,
        'invited_by_user_id' => $admin->id,
    ]);

    $response = actingAs($admin)
        ->get(route('invitations.index'));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => 
        $page->component('Invitations/Index')
            ->has('invitations')
    );
});
