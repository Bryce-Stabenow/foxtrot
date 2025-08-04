<?php

namespace App\Models;

use App\Enums\CheckInStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;

class CheckIn extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'team_id',
        'assigned_user_id',
        'created_by_user_id',
        'scheduled_date',
        'completed_at',
        'status',
        'notes',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'completed_at' => 'datetime',
        'status' => CheckInStatus::class,
    ];

    protected $dates = [
        'scheduled_date',
        'completed_at',
    ];

    // Relationships
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    // Scopes
    public function scopePending(Builder $query): void
    {
        $query->where('status', CheckInStatus::PENDING);
    }

    public function scopeInProgress(Builder $query): void
    {
        $query->where('status', CheckInStatus::IN_PROGRESS);
    }

    public function scopeCompleted(Builder $query): void
    {
        $query->where('status', CheckInStatus::COMPLETED);
    }

    public function scopeForUser(Builder $query, User $user): void
    {
        $query->where('assigned_user_id', $user->id);
    }

    public function scopeForTeam(Builder $query, Team $team): void
    {
        $query->where('team_id', $team->id);
    }

    public function scopeForOrganization(Builder $query, Organization $organization): void
    {
        $query->whereHas('team', function ($q) use ($organization) {
            $q->where('organization_id', $organization->id);
        });
    }

    public function scopeUpcoming(Builder $query, int $days = 7): void
    {
        $query->where('scheduled_date', '<=', now()->addDays($days))
              ->where('scheduled_date', '>=', now())
              ->whereNotIn('status', [CheckInStatus::COMPLETED]);
    }

    public function scopeOverdueItems(Builder $query): void
    {
        $query->where('scheduled_date', '<', now())
              ->whereNotIn('status', [CheckInStatus::COMPLETED]);
    }

    // Accessors
    public function isOverdue(): Attribute
    {
        return Attribute::make(
            get: fn (): bool => $this->scheduled_date->isPast() && $this->status !== CheckInStatus::COMPLETED
        );
    }

    public function daysUntilDue(): Attribute
    {
        return Attribute::make(
            get: fn (): int => now()->diffInDays($this->scheduled_date, false)
        );
    }

    // Methods
    public function markAsCompleted(string|null $notes = null): void
    {
        $this->update([
            'status' => CheckInStatus::COMPLETED,
            'completed_at' => now(),
            'notes' => $notes,
        ]);
    }

    public function markAsInProgress(): void
    {
        $this->update(['status' => CheckInStatus::IN_PROGRESS]);
    }

    public function updateOverdueStatus(): void
    {
        if ($this->isOverdue && $this->status !== CheckInStatus::COMPLETED) {
            $this->update(['status' => CheckInStatus::OVERDUE]);
        }
    }
} 