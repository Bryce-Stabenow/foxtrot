# Organization Invitations - Database Schema

## Overview
This document details the database schema changes required for the organization invitation feature, including the new table structure, relationships, and data integrity constraints.

## New Table: `organization_invitations`

### Table Structure
```sql
CREATE TABLE organization_invitations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    organization_id BIGINT UNSIGNED NOT NULL,
    invited_by_user_id BIGINT UNSIGNED NOT NULL,
    token VARCHAR(255) UNIQUE NOT NULL,
    status ENUM('pending', 'accepted', 'expired') DEFAULT 'pending',
    expires_at TIMESTAMP NOT NULL,
    accepted_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    FOREIGN KEY (invited_by_user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_organization_invitations_email (email),
    INDEX idx_organization_invitations_token (token),
    INDEX idx_organization_invitations_status (status),
    INDEX idx_organization_invitations_organization_status (organization_id, status),
    INDEX idx_organization_invitations_expires_at (expires_at)
);
```

### Field Descriptions

| Field | Type | Description | Constraints |
|-------|------|-------------|-------------|
| `id` | BIGINT UNSIGNED | Primary key | AUTO_INCREMENT |
| `email` | VARCHAR(255) | Email address of invited user | NOT NULL |
| `organization_id` | BIGINT UNSIGNED | Organization being invited to | NOT NULL, FK |
| `invited_by_user_id` | BIGINT UNSIGNED | User who sent the invitation | NOT NULL, FK |
| `token` | VARCHAR(255) | Secure token for invitation link | NOT NULL, UNIQUE |
| `status` | ENUM | Current status of invitation | 'pending', 'accepted', 'expired' |
| `expires_at` | TIMESTAMP | When invitation expires | NOT NULL |
| `accepted_at` | TIMESTAMP | When invitation was accepted | NULL |
| `created_at` | TIMESTAMP | When invitation was created | NULL |
| `updated_at` | TIMESTAMP | When invitation was last updated | NULL |

### Indexes
- **Primary Key**: `id`
- **Unique Index**: `token` (for secure invitation links)
- **Email Index**: `email` (for duplicate checking)
- **Status Index**: `status` (for filtering)
- **Composite Index**: `organization_id, status` (for organization-specific queries)
- **Expiration Index**: `expires_at` (for cleanup jobs)

### Foreign Key Constraints
- `organization_id` → `organizations.id` (CASCADE DELETE)
- `invited_by_user_id` → `users.id` (CASCADE DELETE)

## Model Relationships

### OrganizationInvitation Model
```php
class OrganizationInvitation extends Model
{
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
    ];

    // Relationships
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by_user_id');
    }
}
```

### Updated Organization Model
```php
class Organization extends Model
{
    // ... existing code ...

    public function invitations(): HasMany
    {
        return $this->hasMany(OrganizationInvitation::class);
    }

    public function pendingInvitations(): HasMany
    {
        return $this->hasMany(OrganizationInvitation::class)
            ->where('status', 'pending')
            ->where('expires_at', '>', now());
    }
}
```

### Updated User Model
```php
class User extends Authenticatable
{
    // ... existing code ...

    public function invitedInvitations(): HasMany
    {
        return $this->hasMany(OrganizationInvitation::class, 'invited_by_user_id');
    }
}
```

## Data Integrity Rules

### Business Rules
1. **Email Uniqueness**: Only one pending invitation per email per organization
2. **Token Uniqueness**: Each invitation token must be globally unique
3. **Expiration**: Invitations automatically expire after configurable time (default: 7 days)
4. **Status Transitions**: 
   - `pending` → `accepted` (when user accepts)
   - `pending` → `expired` (when expires)
   - No other transitions allowed

### Validation Rules
```php
// SendInvitationRequest validation
'email' => [
    'required',
    'email',
    'max:255',
    Rule::unique('organization_invitations')
        ->where('organization_id', $organizationId)
        ->where('status', 'pending'),
    Rule::notIn(User::where('organization_id', $organizationId)->pluck('email')),
],
```

## Migration File Structure

### Migration: `create_organization_invitations_table.php`
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organization_invitations', function (Blueprint $table) {
            $table->id();
            $table->string('email');
            $table->foreignId('organization_id')->constrained()->onDelete('cascade');
            $table->foreignId('invited_by_user_id')->constrained()->onDelete('cascade');
            $table->string('token')->unique();
            $table->enum('status', ['pending', 'accepted', 'expired'])->default('pending');
            $table->timestamp('expires_at');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();

            $table->index(['email']);
            $table->index(['token']);
            $table->index(['status']);
            $table->index(['organization_id', 'status']);
            $table->index(['expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_invitations');
    }
};
```

## Factory and Seeder

### OrganizationInvitationFactory
```php
class OrganizationInvitationFactory extends Factory
{
    protected $model = OrganizationInvitation::class;

    public function definition(): array
    {
        return [
            'email' => $this->faker->unique()->safeEmail(),
            'organization_id' => Organization::factory(),
            'invited_by_user_id' => User::factory(),
            'token' => Str::random(64),
            'status' => $this->faker->randomElement(['pending', 'accepted', 'expired']),
            'expires_at' => now()->addDays(7),
            'accepted_at' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'expires_at' => now()->addDays(7),
            'accepted_at' => null,
        ]);
    }

    public function accepted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'accepted',
            'accepted_at' => now(),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'expired',
            'expires_at' => now()->subDays(1),
            'accepted_at' => null,
        ]);
    }
}
```

### OrganizationInvitationSeeder
```php
class OrganizationInvitationSeeder extends Seeder
{
    public function run(): void
    {
        // Create sample invitations for testing
        OrganizationInvitation::factory()
            ->count(5)
            ->pending()
            ->create();

        OrganizationInvitation::factory()
            ->count(3)
            ->accepted()
            ->create();

        OrganizationInvitation::factory()
            ->count(2)
            ->expired()
            ->create();
    }
}
```

## Maintenance Considerations

### Cleanup Jobs
- **Expired Invitations**: Regular job to mark expired invitations
- **Old Records**: Archive or delete old accepted/expired invitations
- **Token Rotation**: Consider token expiration for security

### Performance Considerations
- **Indexes**: Optimized for common query patterns
- **Partitioning**: Consider partitioning by status for large datasets
- **Caching**: Cache frequently accessed invitation data

### Security Considerations
- **Token Security**: Cryptographically secure random tokens
- **Rate Limiting**: Prevent invitation spam
- **Audit Trail**: Track invitation lifecycle for compliance 