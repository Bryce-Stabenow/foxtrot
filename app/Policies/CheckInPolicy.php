<?php

namespace App\Policies;

use App\Enums\UserType;
use App\Models\CheckIn;
use App\Models\User;

class CheckInPolicy
{
    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, CheckIn $checkIn): bool
    {
        // Users can view check-ins they are assigned to
        if ($checkIn->assigned_user_id === $user->id) {
            return true;
        }

        // Team admins can view check-ins for their team members
        if (hasAdminPermissions($user) && $checkIn->team->organization_id === $user->organization_id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Only admins and owners can create check-ins
        return hasAdminPermissions($user);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, CheckIn $checkIn): bool
    {
        // Users can only update check-ins they created
        if ($checkIn->created_by_user_id === $user->id) {
            return true;
        }

        // Organization owners can update any check-in in their organization
        if ($user->user_type === UserType::OWNER && $checkIn->team->organization_id === $user->organization_id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, CheckIn $checkIn): bool
    {
        // Users can only delete check-ins they created
        if ($checkIn->created_by_user_id === $user->id) {
            return true;
        }

        // Organization owners can delete any check-in in their organization
        if ($user->user_type === UserType::OWNER && $checkIn->team->organization_id === $user->organization_id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can mark the check-in as completed.
     */
    public function markComplete(User $user, CheckIn $checkIn): bool
    {
        // Only the assigned user can mark a check-in as completed
        return $checkIn->assigned_user_id === $user->id;
    }

    /**
     * Determine whether the user can mark the check-in as in progress.
     */
    public function markInProgress(User $user, CheckIn $checkIn): bool
    {
        // Only the assigned user can mark a check-in as in progress
        return $checkIn->assigned_user_id === $user->id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, CheckIn $checkIn): bool
    {
        return false; // Soft deletes not implemented
    }
}
