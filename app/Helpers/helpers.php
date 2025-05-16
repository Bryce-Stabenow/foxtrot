<?php 

    /**
     * Get the authenticated user or null if not authenticated.
     *
     * @return \App\Models\User|null
     */
    function user()
    {
        return auth()->user();
    }