<?php

use App\Enums\UserType;

/**
 * Get the authenticated user or null if not authenticated.
 *
 * @return \App\Models\User|null
 */
function user()
{
    return auth()->user();
}

/**
 * Check if a user has admin permissions (admin or owner).
 *
 * @param \App\Models\User $user
 * @return bool
 */
function hasAdminPermissions($user)
{
    return in_array($user->user_type, [UserType::ADMIN, UserType::OWNER]);
}
