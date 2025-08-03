<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Enums\OrganizationInvitationStatus;

class OrganizationInvitation extends Model
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'email',
        'organization_id',
        'invited_by_user_id',
        'token',
        'status',
        'expires_at',
        'accepted_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'accepted_at' => 'datetime',
        'status' => OrganizationInvitationStatus::class,
    ];

    /**
     * Route notifications for the mail channel.
     *
     * @return array<int, string>
     */
    public function routeNotificationForMail(): array
    {
        return [$this->email => $this->email];
    }

    /**
     * The organization that the invitation belongs to.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * The user who sent the invitation.
     */
    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by_user_id');
    }

    /**
     * Scope a query to only include pending invitations.
     */
    public function scopePending(Builder $query): void
    {
        $query->where('status', OrganizationInvitationStatus::PENDING);
    }

    /**
     * Scope a query to only include accepted invitations.
     */
    public function scopeAccepted(Builder $query): void
    {
        $query->where('status', OrganizationInvitationStatus::ACCEPTED);
    }

    /**
     * Scope a query to only include expired invitations.
     */
    public function scopeExpired(Builder $query): void
    {
        $query->where('status', OrganizationInvitationStatus::EXPIRED);
    }

    /**
     * Scope a query to only include valid (not expired) invitations.
     */
    public function scopeValid(Builder $query): void
    {
        $query->where('status', OrganizationInvitationStatus::PENDING)
              ->where('expires_at', '>', now());
    }

    /**
     * Check if the invitation is expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Check if the invitation is valid (not expired and pending).
     */
    public function isValid(): bool
    {
        return $this->status === OrganizationInvitationStatus::PENDING && !$this->isExpired();
    }

    /**
     * Mark the invitation as accepted.
     */
    public function markAsAccepted(): void
    {
        $this->update([
            'status' => OrganizationInvitationStatus::ACCEPTED,
            'accepted_at' => now(),
        ]);
    }

    /**
     * Mark the invitation as expired.
     */
    public function markAsExpired(): void
    {
        $this->update(['status' => OrganizationInvitationStatus::EXPIRED]);
    }

    /**
     * Generate a secure token for the invitation.
     */
    public static function generateToken(): string
    {
        return Str::uuid()->toString();
    }

    /**
     * Create a new invitation with default expiration.
     */
    public static function createInvitation(array $data): self
    {
        return static::create([
            ...$data,
            'token' => static::generateToken(),
            'expires_at' => now()->addDays(7), // Default 7 days expiration
        ]);
    }

    /**
     * Boot the model and add event listeners.
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-expire invitations when they pass their expiration date
        static::saving(function (self $invitation) {
            if ($invitation->status === OrganizationInvitationStatus::PENDING && $invitation->isExpired()) {
                $invitation->status = OrganizationInvitationStatus::EXPIRED;
            }
        });
    }
}
