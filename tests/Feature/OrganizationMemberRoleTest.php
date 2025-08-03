<?php

use App\Enums\UserType;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('admin can update member role to admin', function () {
    $organization = Organization::factory()->create();
    $admin = User::factory()->create([
        'organization_id' => $organization->id,
        'user_type' => UserType::ADMIN,
    ]);
    $member = User::factory()->create([
        'organization_id' => $organization->id,
        'user_type' => UserType::MEMBER,
    ]);

    $response = $this->actingAs($admin)
        ->patch(route('organization.members.update-role', $member), [
            'user_type' => 'admin',
        ]);

    $response->assertRedirect();
    $response->assertSessionHas('success', 'Member role updated successfully.');

    $member->refresh();
    expect($member->user_type)->toBe(UserType::ADMIN);
});

test('admin can update admin role to member', function () {
    $organization = Organization::factory()->create();
    $admin = User::factory()->create([
        'organization_id' => $organization->id,
        'user_type' => UserType::ADMIN,
    ]);
    $otherAdmin = User::factory()->create([
        'organization_id' => $organization->id,
        'user_type' => UserType::ADMIN,
    ]);

    $response = $this->actingAs($admin)
        ->patch(route('organization.members.update-role', $otherAdmin), [
            'user_type' => 'member',
        ]);

    $response->assertRedirect();
    $response->assertSessionHas('success', 'Member role updated successfully.');

    $otherAdmin->refresh();
    expect($otherAdmin->user_type)->toBe(UserType::MEMBER);
});

test('admin cannot change their own role', function () {
    $organization = Organization::factory()->create();
    $admin = User::factory()->create([
        'organization_id' => $organization->id,
        'user_type' => UserType::ADMIN,
    ]);

    $response = $this->actingAs($admin)
        ->patch(route('organization.members.update-role', $admin), [
            'user_type' => 'member',
        ]);

    $response->assertStatus(400);
    $response->assertSee('You cannot change your own role.');

    $admin->refresh();
    expect($admin->user_type)->toBe(UserType::ADMIN);
});

test('admin cannot change owner roles', function () {
    $organization = Organization::factory()->create();
    $admin = User::factory()->create([
        'organization_id' => $organization->id,
        'user_type' => UserType::ADMIN,
    ]);
    $owner = User::factory()->create([
        'organization_id' => $organization->id,
        'user_type' => UserType::OWNER,
    ]);

    $response = $this->actingAs($admin)
        ->patch(route('organization.members.update-role', $owner), [
            'user_type' => 'admin',
        ]);

    $response->assertStatus(403);
    $response->assertSee('Admins cannot change owner roles.');

    $owner->refresh();
    expect($owner->user_type)->toBe(UserType::OWNER);
});

test('admin cannot promote users to owner', function () {
    $organization = Organization::factory()->create();
    $admin = User::factory()->create([
        'organization_id' => $organization->id,
        'user_type' => UserType::ADMIN,
    ]);
    $member = User::factory()->create([
        'organization_id' => $organization->id,
        'user_type' => UserType::MEMBER,
    ]);

    $response = $this->actingAs($admin)
        ->patch(route('organization.members.update-role', $member), [
            'user_type' => 'owner',
        ]);

    $response->assertStatus(403);
    $response->assertSee('Admins cannot promote users to owner.');

    $member->refresh();
    expect($member->user_type)->toBe(UserType::MEMBER);
});

test('owner can change admin roles', function () {
    $organization = Organization::factory()->create();
    $owner = User::factory()->create([
        'organization_id' => $organization->id,
        'user_type' => UserType::OWNER,
    ]);
    $admin = User::factory()->create([
        'organization_id' => $organization->id,
        'user_type' => UserType::ADMIN,
    ]);

    $response = $this->actingAs($owner)
        ->patch(route('organization.members.update-role', $admin), [
            'user_type' => 'member',
        ]);

    $response->assertRedirect();
    $response->assertSessionHas('success', 'Member role updated successfully.');

    $admin->refresh();
    expect($admin->user_type)->toBe(UserType::MEMBER);
});

test('owner can change other owner roles', function () {
    $organization = Organization::factory()->create();
    $owner = User::factory()->create([
        'organization_id' => $organization->id,
        'user_type' => UserType::OWNER,
    ]);
    $otherOwner = User::factory()->create([
        'organization_id' => $organization->id,
        'user_type' => UserType::OWNER,
    ]);

    $response = $this->actingAs($owner)
        ->patch(route('organization.members.update-role', $otherOwner), [
            'user_type' => 'admin',
        ]);

    $response->assertRedirect();
    $response->assertSessionHas('success', 'Member role updated successfully.');

    $otherOwner->refresh();
    expect($otherOwner->user_type)->toBe(UserType::ADMIN);
});

test('member cannot update other member roles', function () {
    $organization = Organization::factory()->create();
    $member = User::factory()->create([
        'organization_id' => $organization->id,
        'user_type' => UserType::MEMBER,
    ]);
    $otherMember = User::factory()->create([
        'organization_id' => $organization->id,
        'user_type' => UserType::MEMBER,
    ]);

    $response = $this->actingAs($member)
        ->patch(route('organization.members.update-role', $otherMember), [
            'user_type' => 'admin',
        ]);

    $response->assertStatus(403);
    $response->assertSee('Only admins and owners can update member roles.');

    $otherMember->refresh();
    expect($otherMember->user_type)->toBe(UserType::MEMBER);
});

test('admin cannot update member from different organization', function () {
    $organization1 = Organization::factory()->create();
    $organization2 = Organization::factory()->create();
    
    $admin = User::factory()->create([
        'organization_id' => $organization1->id,
        'user_type' => UserType::ADMIN,
    ]);
    $member = User::factory()->create([
        'organization_id' => $organization2->id,
        'user_type' => UserType::MEMBER,
    ]);

    $response = $this->actingAs($admin)
        ->patch(route('organization.members.update-role', $member), [
            'user_type' => 'admin',
        ]);

    $response->assertStatus(403);
    $response->assertSee('Unauthorized action.');

    $member->refresh();
    expect($member->user_type)->toBe(UserType::MEMBER);
});

test('invalid user_type returns validation error', function () {
    $organization = Organization::factory()->create();
    $admin = User::factory()->create([
        'organization_id' => $organization->id,
        'user_type' => UserType::ADMIN,
    ]);
    $member = User::factory()->create([
        'organization_id' => $organization->id,
        'user_type' => UserType::MEMBER,
    ]);

    $response = $this->actingAs($admin)
        ->patch(route('organization.members.update-role', $member), [
            'user_type' => 'invalid_role',
        ]);

    $response->assertStatus(302);
    $response->assertSessionHasErrors(['user_type']);

    $member->refresh();
    expect($member->user_type)->toBe(UserType::MEMBER);
});

test('missing user_type returns validation error', function () {
    $organization = Organization::factory()->create();
    $admin = User::factory()->create([
        'organization_id' => $organization->id,
        'user_type' => UserType::ADMIN,
    ]);
    $member = User::factory()->create([
        'organization_id' => $organization->id,
        'user_type' => UserType::MEMBER,
    ]);

    $response = $this->actingAs($admin)
        ->patch(route('organization.members.update-role', $member), []);

    $response->assertStatus(302);
    $response->assertSessionHasErrors(['user_type']);

    $member->refresh();
    expect($member->user_type)->toBe(UserType::MEMBER);
}); 