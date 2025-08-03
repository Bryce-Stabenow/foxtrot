<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\User;
use App\Enums\UserType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class OrganizationMemberController extends Controller
{
    /**
     * Display a listing of organization members.
     */
    public function index(Request $request): \Inertia\Response
    {
        $user = $request->user();
        
        // Ensure user is an admin
        if ($user->user_type !== UserType::ADMIN) {
            abort(403, 'Only admins can view organization members.');
        }

        $organization = $user->organization;
        
        if (!$organization) {
            abort(404, 'Organization not found.');
        }

        $members = $organization->members()
            ->with(['teams' => function ($query) use ($organization) {
                $query->where('organization_id', $organization->id);
            }])
            ->orderBy('name')
            ->get();

        $teams = $organization->teams()->orderBy('name')->get();

        return Inertia::render('Organization/Members', [
            'members' => $members,
            'teams' => $teams,
            'organization' => $organization,
        ]);
    }

    /**
     * Assign a member to a team.
     */
    public function assignToTeam(Request $request, User $member, Team $team): RedirectResponse
    {
        $user = $request->user();
        
        // Ensure user is an admin
        if ($user->user_type !== UserType::ADMIN) {
            abort(403, 'Only admins can assign members to teams.');
        }

        // Ensure admin and member belong to the same organization
        if ($user->organization_id !== $member->organization_id || $user->organization_id !== $team->organization_id) {
            abort(403, 'Unauthorized action.');
        }

        // Check if member is already assigned to this team
        if ($member->teams()->where('team_id', $team->id)->exists()) {
            abort(400, 'Member is already assigned to this team.');
        }

        $member->teams()->attach($team->id);

        return back()->with('success', 'Member assigned to team successfully.');
    }

    /**
     * Remove a member from a team.
     */
    public function removeFromTeam(Request $request, User $member, Team $team): RedirectResponse
    {
        $user = $request->user();
        
        // Ensure user is an admin
        if ($user->user_type !== UserType::ADMIN) {
            abort(403, 'Only admins can remove members from teams.');
        }

        // Ensure admin and member belong to the same organization
        if ($user->organization_id !== $member->organization_id || $user->organization_id !== $team->organization_id) {
            abort(403, 'Unauthorized action.');
        }

        // Check if member is assigned to this team
        if (!$member->teams()->where('team_id', $team->id)->exists()) {
            abort(400, 'Member is not assigned to this team.');
        }

        $member->teams()->detach($team->id);

        return back()->with('success', 'Member removed from team successfully.');
    }
} 