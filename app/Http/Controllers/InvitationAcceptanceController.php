<?php

namespace App\Http\Controllers;

use App\Http\Requests\AcceptInvitationRequest;
use App\Models\OrganizationInvitation;
use App\Models\User;
use App\Enums\UserType;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class InvitationAcceptanceController extends Controller
{
    /**
     * Display the invitation acceptance page.
     */
    public function show(string $token): Response
    {
        $invitation = OrganizationInvitation::where('token', $token)
            ->with(['organization', 'invitedBy'])
            ->first();

        if (!$invitation || !$invitation->isValid()) {
            abort(404, 'Invalid or expired invitation.');
        }

        return Inertia::render('Invitations/Accept', [
            'invitation' => $invitation,
        ]);
    }

    /**
     * Process the invitation acceptance and create account.
     */
    public function store(AcceptInvitationRequest $request, string $token): RedirectResponse
    {
        $invitation = OrganizationInvitation::where('token', $token)
            ->with(['organization'])
            ->first();

        if (!$invitation || !$invitation->isValid()) {
            abort(404, 'Invalid or expired invitation.');
        }

        // Check if user already exists with this email
        $existingUser = User::where('email', $invitation->email)->first();
        if ($existingUser) {
            return redirect()->route('login')
                ->withErrors(['email' => 'An account with this email already exists. Please log in instead.']);
        }

        // Create the user account
        $user = User::create([
            'name' => $request->name,
            'email' => $invitation->email,
            'password' => Hash::make($request->password),
            'organization_id' => $invitation->organization_id,
            'user_type' => UserType::MEMBER, // Default to member role
            'email_verified_at' => now(), // Auto-verify since they came through invitation
        ]);

        // Mark invitation as accepted
        $invitation->markAsAccepted();

        // Log the user in
        Auth::login($user);

        return redirect()->route('dashboard')
            ->with('success', "Welcome to {$invitation->organization->name}!");
    }
}
