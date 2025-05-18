<?php

namespace App\Http\Controllers;

use App\Models\Team;
use Inertia\Inertia;

class TeamController extends Controller
{
    /**
     * Display the specified team.
     */
    public function show(Team $team)
    {
        $this->authorize('view', $team);

        return Inertia::render('Teams/Show', [
            'team' => $team->load('members'),
        ]);
    }

    /**
     * Display a listing of the user's teams.
     */
    public function index()
    {
        $teams = auth()->user()->teams()->with('members')->get();

        return Inertia::render('Teams/Index', [
            'teams' => $teams,
        ]);
    }
}
