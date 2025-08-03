<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\User;
use App\Enums\UserType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class OrganizationMemberController extends Controller
{
    /**
     * Display a listing of organization members.
     */
    public function index(Request $request): \Inertia\Response
    {
        $user = $request->user();
        
        // Ensure user has admin permissions
        if (!hasAdminPermissions($user)) {
            abort(403, 'Only admins and owners can view organization members.');
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
     * Display the specified member.
     */
    public function show(Request $request, User $member): \Inertia\Response
    {
        $user = $request->user();
        
        // Ensure user has admin permissions
        if (!hasAdminPermissions($user)) {
            abort(403, 'Only admins and owners can view member details.');
        }

        // Ensure admin and member belong to the same organization
        if ($user->organization_id !== $member->organization_id) {
            abort(403, 'Unauthorized action.');
        }

        $organization = $user->organization;
        
        if (!$organization) {
            abort(404, 'Organization not found.');
        }

        // Load member with their teams
        $member->load(['teams' => function ($query) use ($organization) {
            $query->where('organization_id', $organization->id);
        }]);

        $teams = $organization->teams()->orderBy('name')->get();

        return Inertia::render('Organization/MemberShow', [
            'member' => $member,
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
        
        // Ensure user has admin permissions
        if (!hasAdminPermissions($user)) {
            abort(403, 'Only admins and owners can assign members to teams.');
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
        
        // Ensure user has admin permissions
        if (!hasAdminPermissions($user)) {
            abort(403, 'Only admins and owners can remove members from teams.');
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

    /**
     * Update a member's role.
     */
    public function updateRole(Request $request, User $member): RedirectResponse
    {
        $user = $request->user();
        
        // Ensure user has admin permissions
        if (!hasAdminPermissions($user)) {
            abort(403, 'Only admins and owners can update member roles.');
        }

        // Ensure admin and member belong to the same organization
        if ($user->organization_id !== $member->organization_id) {
            abort(403, 'Unauthorized action.');
        }

        // Prevent user from changing their own role
        if ($user->id === $member->id) {
            abort(400, 'You cannot change your own role.');
        }

        $request->validate([
            'user_type' => ['required', 'string', Rule::enum(UserType::class)],
        ]);

        $newRole = UserType::from($request->user_type);
        
        // Check role hierarchy permissions
        if ($user->user_type === UserType::ADMIN) {
            // Admins can only change members to admin/member
            if ($member->user_type === UserType::OWNER) {
                abort(403, 'Admins cannot change owner roles.');
            }
            if ($newRole === UserType::OWNER) {
                abort(403, 'Admins cannot promote users to owner.');
            }
        }

        // Update the member's role
        $member->update(['user_type' => $newRole]);

        return back()->with('success', 'Member role updated successfully.');
    }

    /**
     * Delete a member from the organization.
     */
    public function destroy(Request $request, User $member): RedirectResponse
    {
        $user = $request->user();
        
        // Ensure user has admin permissions
        if (!hasAdminPermissions($user)) {
            abort(403, 'Only admins and owners can delete organization members.');
        }

        // Ensure admin and member belong to the same organization
        if ($user->organization_id !== $member->organization_id) {
            abort(403, 'Unauthorized action.');
        }

        // Prevent admin from deleting themselves
        if ($user->id === $member->id) {
            abort(400, 'You cannot delete your own account.');
        }

        // Only allow deletion of members with no team assignments
        if ($member->teams()->count() > 0) {
            abort(400, 'Cannot delete members who are assigned to teams. Please remove them from all teams first.');
        }

        // Delete the member
        $member->delete();

        return redirect()->route('organization.members.index')->with('success', 'Member deleted successfully.');
    }
} 