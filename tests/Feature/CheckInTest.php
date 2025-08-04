<?php

use App\Enums\CheckInStatus;
use App\Enums\UserType;
use App\Models\CheckIn;
use App\Models\Organization;
use App\Models\Team;
use App\Models\User;

beforeEach(function () {
    $this->organization = Organization::factory()->create();
    $this->team = Team::factory()->create(['organization_id' => $this->organization->id]);
    
    $this->owner = User::factory()->create([
        'organization_id' => $this->organization->id,
        'user_type' => UserType::OWNER,
    ]);
    
    $this->admin = User::factory()->create([
        'organization_id' => $this->organization->id,
        'user_type' => UserType::ADMIN,
    ]);
    
    $this->member = User::factory()->create([
        'organization_id' => $this->organization->id,
        'user_type' => UserType::MEMBER,
    ]);
});

test('users can view check-ins index page', function () {
    $user = $this->owner;
    
    $response = $this->actingAs($user)->get('/check-ins');
    
    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page->component('CheckIns/Index'));
});

test('admins can create check-ins', function () {
    $admin = $this->admin;
    $member = $this->member;
    
    $response = $this->actingAs($admin)->get('/check-ins/create');
    
    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page->component('CheckIns/Create'));
    
    $checkInData = [
        'title' => 'Test Check-in',
        'description' => 'Test description',
        'team_id' => $this->team->id,
        'assigned_user_id' => $member->id,
        'scheduled_date' => now()->addDays(7)->format('Y-m-d'),
    ];
    
    $response = $this->actingAs($admin)->post('/check-ins', $checkInData);
    
    $response->assertRedirect('/check-ins');
    $this->assertDatabaseHas('check_ins', [
        'title' => 'Test Check-in',
        'team_id' => $this->team->id,
        'assigned_user_id' => $member->id,
        'created_by_user_id' => $admin->id,
        'status' => CheckInStatus::PENDING->value,
    ]);
});

test('members cannot create check-ins', function () {
    $member = $this->member;
    
    $response = $this->actingAs($member)->get('/check-ins/create');
    
    $response->assertForbidden();
    
    $checkInData = [
        'title' => 'Test Check-in',
        'description' => 'Test description',
        'team_id' => $this->team->id,
        'assigned_user_id' => $member->id,
        'scheduled_date' => now()->addDays(7)->format('Y-m-d'),
    ];
    
    $response = $this->actingAs($member)->post('/check-ins', $checkInData);
    
    $response->assertForbidden();
});

test('users can view their assigned check-ins', function () {
    $member = $this->member;
    $checkIn = CheckIn::factory()->create([
        'team_id' => $this->team->id,
        'assigned_user_id' => $member->id,
        'created_by_user_id' => $this->admin->id,
    ]);
    
    $response = $this->actingAs($member)->get("/check-ins/{$checkIn->id}");
    
    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page->component('CheckIns/Show'));
});

test('assigned users can mark check-ins as in progress', function () {
    $member = $this->member;
    $checkIn = CheckIn::factory()->create([
        'team_id' => $this->team->id,
        'assigned_user_id' => $member->id,
        'created_by_user_id' => $this->admin->id,
        'status' => CheckInStatus::PENDING,
    ]);
    
    $response = $this->actingAs($member)->patch("/check-ins/{$checkIn->id}/in-progress");
    
    $response->assertRedirect("/check-ins/{$checkIn->id}");
    $this->assertDatabaseHas('check_ins', [
        'id' => $checkIn->id,
        'status' => CheckInStatus::IN_PROGRESS->value,
    ]);
});

test('assigned users can mark check-ins as completed', function () {
    $member = $this->member;
    $checkIn = CheckIn::factory()->create([
        'team_id' => $this->team->id,
        'assigned_user_id' => $member->id,
        'created_by_user_id' => $this->admin->id,
        'status' => CheckInStatus::IN_PROGRESS,
    ]);
    
    $response = $this->actingAs($member)->patch("/check-ins/{$checkIn->id}/complete", [
        'notes' => 'Completed successfully',
    ]);
    
    $response->assertRedirect("/check-ins/{$checkIn->id}");
    $this->assertDatabaseHas('check_ins', [
        'id' => $checkIn->id,
        'status' => CheckInStatus::COMPLETED->value,
        'notes' => 'Completed successfully',
    ]);
    $this->assertDatabaseHas('check_ins', [
        'id' => $checkIn->id,
        'completed_at' => now(),
    ]);
});

test('non-assigned users cannot mark check-ins as completed', function () {
    $otherMember = User::factory()->create([
        'organization_id' => $this->organization->id,
        'user_type' => UserType::MEMBER,
    ]);
    
    $checkIn = CheckIn::factory()->create([
        'team_id' => $this->team->id,
        'assigned_user_id' => $this->member->id,
        'created_by_user_id' => $this->admin->id,
        'status' => CheckInStatus::IN_PROGRESS,
    ]);
    
    $response = $this->actingAs($otherMember)->patch("/check-ins/{$checkIn->id}/complete", [
        'notes' => 'Completed successfully',
    ]);
    
    $response->assertForbidden();
});

test('admins can edit check-ins they created', function () {
    $admin = $this->admin;
    $checkIn = CheckIn::factory()->create([
        'team_id' => $this->team->id,
        'assigned_user_id' => $this->member->id,
        'created_by_user_id' => $admin->id,
    ]);
    
    $response = $this->actingAs($admin)->get("/check-ins/{$checkIn->id}/edit");
    
    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page->component('CheckIns/Edit'));
    
    $updatedData = [
        'title' => 'Updated Check-in',
        'description' => 'Updated description',
        'team_id' => $this->team->id,
        'assigned_user_id' => $this->member->id,
        'scheduled_date' => now()->addDays(14)->format('Y-m-d'),
    ];
    
    $response = $this->actingAs($admin)->put("/check-ins/{$checkIn->id}", $updatedData);
    
    $response->assertRedirect("/check-ins/{$checkIn->id}");
    $this->assertDatabaseHas('check_ins', [
        'id' => $checkIn->id,
        'title' => 'Updated Check-in',
        'description' => 'Updated description',
    ]);
});

test('admins can delete check-ins they created', function () {
    $admin = $this->admin;
    $checkIn = CheckIn::factory()->create([
        'team_id' => $this->team->id,
        'assigned_user_id' => $this->member->id,
        'created_by_user_id' => $admin->id,
    ]);
    
    $response = $this->actingAs($admin)->delete("/check-ins/{$checkIn->id}");
    
    $response->assertRedirect('/check-ins');
    $this->assertDatabaseMissing('check_ins', ['id' => $checkIn->id]);
});

test('owners can view all check-ins in their organization', function () {
    $owner = $this->owner;
    $otherTeam = Team::factory()->create(['organization_id' => $this->organization->id]);
    
    CheckIn::factory()->count(3)->create([
        'team_id' => $this->team->id,
        'assigned_user_id' => $this->member->id,
        'created_by_user_id' => $this->admin->id,
    ]);
    
    CheckIn::factory()->count(2)->create([
        'team_id' => $otherTeam->id,
        'assigned_user_id' => $this->member->id,
        'created_by_user_id' => $this->admin->id,
    ]);
    
    $response = $this->actingAs($owner)->get('/check-ins');
    
    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => 
        $page->component('CheckIns/Index')
            ->has('checkIns.data', 5)
    );
});

test('members can only view their assigned check-ins', function () {
    $member = $this->member;
    
    // Create check-ins assigned to this member
    CheckIn::factory()->count(2)->create([
        'team_id' => $this->team->id,
        'assigned_user_id' => $member->id,
        'created_by_user_id' => $this->admin->id,
    ]);
    
    // Create check-ins assigned to other members
    CheckIn::factory()->count(3)->create([
        'team_id' => $this->team->id,
        'assigned_user_id' => $this->admin->id,
        'created_by_user_id' => $this->owner->id,
    ]);
    
    $response = $this->actingAs($member)->get('/check-ins');
    
    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => 
        $page->component('CheckIns/Index')
            ->has('checkIns.data', 2)
    );
});

test('check-ins can be filtered by status', function () {
    $admin = $this->admin;
    
    CheckIn::factory()->create([
        'team_id' => $this->team->id,
        'assigned_user_id' => $this->member->id,
        'created_by_user_id' => $admin->id,
        'status' => CheckInStatus::PENDING,
    ]);
    
    CheckIn::factory()->create([
        'team_id' => $this->team->id,
        'assigned_user_id' => $this->member->id,
        'created_by_user_id' => $admin->id,
        'status' => CheckInStatus::COMPLETED,
    ]);
    
    $response = $this->actingAs($admin)->get('/check-ins?status=pending');
    
    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => 
        $page->component('CheckIns/Index')
            ->has('checkIns.data', 1)
    );
});

test('check-ins can be searched', function () {
    $admin = $this->admin;
    
    CheckIn::factory()->create([
        'team_id' => $this->team->id,
        'assigned_user_id' => $this->member->id,
        'created_by_user_id' => $admin->id,
        'title' => 'Important Task',
    ]);
    
    CheckIn::factory()->create([
        'team_id' => $this->team->id,
        'assigned_user_id' => $this->member->id,
        'created_by_user_id' => $admin->id,
        'title' => 'Regular Task',
    ]);
    
    $response = $this->actingAs($admin)->get('/check-ins?search=Important');
    
    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => 
        $page->component('CheckIns/Index')
            ->has('checkIns.data', 1)
    );
}); 