<?php

namespace App\Http\Controllers;

use App\Models\Team;
use Inertia\Inertia;
use Illuminate\Http\Request;

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
        $teams = user()->teams()->with('members')->get();

        return Inertia::render('Teams/Index', [
            'teams' => $teams,
        ]);
    }

    /**
     * Show the form for creating a new team.
     */
    public function create()
    {
        return Inertia::render('Teams/Create');
    }

    /**
     * Store a newly created team in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $team = user()->teams()->create($validated);

        return redirect()->route('teams.show', $team);
    }
}
