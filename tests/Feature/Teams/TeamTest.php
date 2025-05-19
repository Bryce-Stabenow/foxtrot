<?php

use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('authenticated user can access teams index', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->get(route('teams.index'));

    $response->assertStatus(200);
});

test('authenticated user can access team show', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $user->teams()->attach($team);

    $response = $this->actingAs($user)
        ->get(route('teams.show', $team));

    $response->assertStatus(200);
});

test('unauthenticated user cannot access teams', function () {
    $response = $this->get(route('teams.index'));
    $response->assertRedirect(route('login'));

    $team = Team::factory()->create();
    $response = $this->get(route('teams.show', $team));
    $response->assertRedirect(route('login'));
});

test('authenticated user can access team creation form', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->get(route('teams.create'));

    $response->assertStatus(200);
});

test('authenticated user can create a new team', function () {
    $user = User::factory()->create();
    $teamData = ['name' => 'New Team'];

    $response = $this->actingAs($user)
        ->post(route('teams.store'), $teamData);

    $response->assertRedirect();
    $this->assertDatabaseHas('teams', $teamData);
    $this->assertDatabaseHas('team_members', [
        'user_id' => $user->id,
        'team_id' => Team::where('name', 'New Team')->first()->id,
    ]);
});

test('team creation requires valid data', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('teams.store'), []);

    $response->assertSessionHasErrors('name');
});

test('unauthenticated user cannot access team creation', function () {
    $response = $this->get(route('teams.create'));
    $response->assertRedirect(route('login'));

    $response = $this->post(route('teams.store'), ['name' => 'New Team']);
    $response->assertRedirect(route('login'));
});
