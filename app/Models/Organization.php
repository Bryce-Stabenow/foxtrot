<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Enums\UserType;

class Organization extends Model
{
    /** @use HasFactory<\Database\Factories\OrganizationFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'plan_id',
        'type',
    ];

    /**
     * The invitations that belong to the organization.
     */
    public function invitations(): HasMany
    {
        return $this->hasMany(OrganizationInvitation::class);
    }

    /**
     * The members that belong to the organization.
     */
    public function members(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * The admin and owner members that belong to the organization.
     */
    public function admins(): HasMany
    {
        return $this->hasMany(User::class)->whereIn('user_type', [UserType::ADMIN, UserType::OWNER]);
    }

    /**
     * The teams that belong to the organization.
     */
    public function teams(): HasMany
    {
        return $this->hasMany(Team::class);
    }
} 