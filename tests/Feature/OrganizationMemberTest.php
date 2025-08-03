<?php

use App\Enums\UserType;
use App\Models\Organization;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\{actingAs};

uses(RefreshDatabase::class);

test('admin can view organization members page', function () {
    $organization = Organization::factory()->create();
    $admin = User::factory()->create([
        'user_type' => UserType::ADMIN,
        'organization_id' => $organization->id,
    ]);

    $response = actingAs($admin)->get(route('organization.members.index'));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page->component('Organization/Members'));
});

test('non admin cannot access members page', function () {
    $organization = Organization::factory()->create();
    $member = User::factory()->create([
        'user_type' => UserType::MEMBER,
        'organization_id' => $organization->id,
    ]);

    $response = actingAs($member)->get(route('organization.members.index'));

    $response->assertStatus(403);
});

test('admin can assign member to team', function () {
    $organization = Organization::factory()->create();
    $admin = User::factory()->create([
        'user_type' => UserType::ADMIN,
        'organization_id' => $organization->id,
    ]);
    $member = User::factory()->create([
        'user_type' => UserType::MEMBER,
        'organization_id' => $organization->id,
    ]);
    $team = Team::factory()->create(['organization_id' => $organization->id]);

    $response = actingAs($admin)->post(route('organization.members.assign-to-team', [
        'member' => $member->id,
        'team' => $team->id
    ]));

    $response->assertRedirect();
    $response->assertSessionHas('success', 'Member assigned to team successfully.');
    expect($member->teams()->where('team_id', $team->id)->exists())->toBeTrue();
});

test('admin can remove member from team', function () {
    $organization = Organization::factory()->create();
    $admin = User::factory()->create([
        'user_type' => UserType::ADMIN,
        'organization_id' => $organization->id,
    ]);
    $member = User::factory()->create([
        'user_type' => UserType::MEMBER,
        'organization_id' => $organization->id,
    ]);
    $team = Team::factory()->create(['organization_id' => $organization->id]);
    
    // First assign the member to the team
    $member->teams()->attach($team->id);

    $response = actingAs($admin)->delete(route('organization.members.remove-from-team', [
        'member' => $member->id,
        'team' => $team->id
    ]));

    $response->assertRedirect();
    $response->assertSessionHas('success', 'Member removed from team successfully.');
    expect($member->teams()->where('team_id', $team->id)->exists())->toBeFalse();
});

test('non admin cannot assign members to teams', function () {
    $organization = Organization::factory()->create();
    $member = User::factory()->create([
        'user_type' => UserType::MEMBER,
        'organization_id' => $organization->id,
    ]);
    $otherMember = User::factory()->create([
        'user_type' => UserType::MEMBER,
        'organization_id' => $organization->id,
    ]);
    $team = Team::factory()->create(['organization_id' => $organization->id]);

    $response = actingAs($member)->post(route('organization.members.assign-to-team', [
        'member' => $otherMember->id,
        'team' => $team->id
    ]));

    $response->assertStatus(403);
});

test('admin cannot assign member to team twice', function () {
    $organization = Organization::factory()->create();
    $admin = User::factory()->create([
        'user_type' => UserType::ADMIN,
        'organization_id' => $organization->id,
    ]);
    $member = User::factory()->create([
        'user_type' => UserType::MEMBER,
        'organization_id' => $organization->id,
    ]);
    $team = Team::factory()->create(['organization_id' => $organization->id]);
    
    // First assign the member to the team
    $member->teams()->attach($team->id);

    $response = actingAs($admin)->post(route('organization.members.assign-to-team', [
        'member' => $member->id,
        'team' => $team->id
    ]));

    $response->assertStatus(400);
    $response->assertSee('Member is already assigned to this team.');
});

test('admin cannot remove member from team not assigned', function () {
    $organization = Organization::factory()->create();
    $admin = User::factory()->create([
        'user_type' => UserType::ADMIN,
        'organization_id' => $organization->id,
    ]);
    $member = User::factory()->create([
        'user_type' => UserType::MEMBER,
        'organization_id' => $organization->id,
    ]);
    $team = Team::factory()->create(['organization_id' => $organization->id]);

    $response = actingAs($admin)->delete(route('organization.members.remove-from-team', [
        'member' => $member->id,
        'team' => $team->id
    ]));

    $response->assertStatus(400);
    $response->assertSee('Member is not assigned to this team.');
});

test('admin cannot remove member from team twice', function () {
    $organization = Organization::factory()->create();
    $admin = User::factory()->create([
        'user_type' => UserType::ADMIN,
        'organization_id' => $organization->id,
    ]);
    $member = User::factory()->create([
        'user_type' => UserType::MEMBER,
        'organization_id' => $organization->id,
    ]);
    $team = Team::factory()->create(['organization_id' => $organization->id]);

    $response = actingAs($admin)->delete(route('organization.members.remove-from-team', [
        'member' => $member->id,
        'team' => $team->id
    ]));

    $response->assertStatus(400);
    $response->assertSee('Member is not assigned to this team.');
});

test('admin can delete member with no team assignments', function () {
    $organization = Organization::factory()->create();
    $admin = User::factory()->create([
        'user_type' => UserType::ADMIN,
        'organization_id' => $organization->id,
    ]);
    $member = User::factory()->create([
        'user_type' => UserType::MEMBER,
        'organization_id' => $organization->id,
    ]);

    $response = actingAs($admin)->delete(route('organization.members.destroy', [
        'member' => $member->id
    ]));

    $response->assertRedirect();
    $response->assertSessionHas('success', 'Member deleted successfully.');
    expect(User::find($member->id))->toBeNull();
});

test('admin cannot delete member with team assignments', function () {
    $organization = Organization::factory()->create();
    $admin = User::factory()->create([
        'user_type' => UserType::ADMIN,
        'organization_id' => $organization->id,
    ]);
    $member = User::factory()->create([
        'user_type' => UserType::MEMBER,
        'organization_id' => $organization->id,
    ]);
    $team = Team::factory()->create(['organization_id' => $organization->id]);
    
    // Assign member to team
    $member->teams()->attach($team->id);

    $response = actingAs($admin)->delete(route('organization.members.destroy', [
        'member' => $member->id
    ]));

    $response->assertStatus(400);
    $response->assertSee('Cannot delete members who are assigned to teams. Please remove them from all teams first.');
    expect(User::find($member->id))->not->toBeNull();
});

test('admin cannot delete themselves', function () {
    $organization = Organization::factory()->create();
    $admin = User::factory()->create([
        'user_type' => UserType::ADMIN,
        'organization_id' => $organization->id,
    ]);

    $response = actingAs($admin)->delete(route('organization.members.destroy', [
        'member' => $admin->id
    ]));

    $response->assertStatus(400);
    $response->assertSee('You cannot delete your own account.');
    expect(User::find($admin->id))->not->toBeNull();
});

test('non admin cannot delete members', function () {
    $organization = Organization::factory()->create();
    $member = User::factory()->create([
        'user_type' => UserType::MEMBER,
        'organization_id' => $organization->id,
    ]);
    $otherMember = User::factory()->create([
        'user_type' => UserType::MEMBER,
        'organization_id' => $organization->id,
    ]);

    $response = actingAs($member)->delete(route('organization.members.destroy', [
        'member' => $otherMember->id
    ]));

    $response->assertStatus(403);
    expect(User::find($otherMember->id))->not->toBeNull();
});

test('admin cannot delete member from different organization', function () {
    $organization1 = Organization::factory()->create();
    $organization2 = Organization::factory()->create();
    $admin = User::factory()->create([
        'user_type' => UserType::ADMIN,
        'organization_id' => $organization1->id,
    ]);
    $member = User::factory()->create([
        'user_type' => UserType::MEMBER,
        'organization_id' => $organization2->id,
    ]);

    $response = actingAs($admin)->delete(route('organization.members.destroy', [
        'member' => $member->id
    ]));

    $response->assertStatus(403);
    expect(User::find($member->id))->not->toBeNull();
});

test('members page shows all organization members', function () {
    $organization = Organization::factory()->create();
    $admin = User::factory()->create([
        'user_type' => UserType::ADMIN,
        'organization_id' => $organization->id,
    ]);
    $member1 = User::factory()->create([
        'user_type' => UserType::MEMBER,
        'organization_id' => $organization->id,
    ]);
    $member2 = User::factory()->create([
        'user_type' => UserType::MEMBER,
        'organization_id' => $organization->id,
    ]);

    $response = actingAs($admin)->get(route('organization.members.index'));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => 
        $page->has('members', 3) // admin + 2 members
    );
});

test('members page includes teams data', function () {
    $organization = Organization::factory()->create();
    $admin = User::factory()->create([
        'user_type' => UserType::ADMIN,
        'organization_id' => $organization->id,
    ]);
    $team = Team::factory()->create(['organization_id' => $organization->id]);

    $response = actingAs($admin)->get(route('organization.members.index'));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => 
        $page->has('teams', 1)
    );
}); 