<?php

namespace App\Http\Controllers;

use App\Http\Requests\SendInvitationRequest;
use App\Models\OrganizationInvitation;
use App\Notifications\OrganizationInvitationNotification;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use App\Enums\OrganizationInvitationStatus;

class OrganizationInvitationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        $user = auth()->user();
        
        $invitations = OrganizationInvitation::where('organization_id', $user->organization_id)
            ->with(['invitedBy', 'organization'])
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('Invitations/Index', [
            'invitations' => $invitations,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(SendInvitationRequest $request): RedirectResponse
    {
        $user = $request->user();

        // Create the invitation
        $invitation = OrganizationInvitation::createInvitation([
            'email' => $request->email,
            'organization_id' => $user->organization_id,
            'invited_by_user_id' => $user->id,
        ]);

        // Send the notification
        $invitation->notify(new OrganizationInvitationNotification($invitation));

        return redirect()->route('invitations.index')
            ->with('success', 'Invitation sent successfully!');
    }

    /**
     * Resend an invitation.
     */
    public function resend(OrganizationInvitation $invitation): RedirectResponse
    {
        $user = auth()->user();

        // Check if user can resend this invitation
        if ($invitation->organization_id !== $user->organization_id || 
            $invitation->status !== OrganizationInvitationStatus::PENDING) {
            abort(403);
        }

        // Update expiration and resend
        $invitation->update([
            'expires_at' => now()->addDays(7),
        ]);

        $invitation->notify(new OrganizationInvitationNotification($invitation));

        return redirect()->route('invitations.index')
            ->with('success', 'Invitation resent successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(OrganizationInvitation $invitation): RedirectResponse
    {
        $user = auth()->user();

        // Check if user can cancel this invitation
        if ($invitation->organization_id !== $user->organization_id) {
            abort(403);
        }

        $invitation->delete();

        return redirect()->route('invitations.index')
            ->with('success', 'Invitation cancelled successfully!');
    }
}
