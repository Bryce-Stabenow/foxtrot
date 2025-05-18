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
