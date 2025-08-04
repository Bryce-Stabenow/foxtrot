# Check-In Feature - Database Schema

## Overview
This document outlines the database schema for the check-in feature, including table structures, relationships, indexes, and data integrity constraints.

## Table Structure

### 1. Check-ins Table

#### Table Name: `check_ins`

#### Columns
| Column Name | Data Type | Constraints | Description |
|-------------|-----------|-------------|-------------|
| `id` | BIGINT UNSIGNED | PRIMARY KEY, AUTO_INCREMENT | Unique identifier |
| `title` | VARCHAR(255) | NOT NULL | Check-in title/task name |
| `description` | TEXT | NULL | Detailed description of the check-in |
| `team_id` | BIGINT UNSIGNED | NOT NULL, FOREIGN KEY | Team the check-in belongs to |
| `assigned_user_id` | BIGINT UNSIGNED | NOT NULL, FOREIGN KEY | User assigned to complete the check-in |
| `created_by_user_id` | BIGINT UNSIGNED | NOT NULL, FOREIGN KEY | User who created the check-in |
| `scheduled_date` | DATE | NOT NULL | Date when check-in should be completed |
| `completed_at` | TIMESTAMP | NULL | Timestamp when check-in was completed |
| `status` | ENUM | NOT NULL, DEFAULT 'pending' | Current status of the check-in |
| `notes` | TEXT | NULL | Notes added when completing the check-in |
| `created_at` | TIMESTAMP | NULL | Record creation timestamp |
| `updated_at` | TIMESTAMP | NULL | Record last update timestamp |

#### Status Enum Values
- `pending` - Check-in is assigned but not started
- `in_progress` - User has started working on the check-in
- `completed` - Check-in has been completed
- `overdue` - Scheduled date has passed and check-in is not completed

#### Foreign Key Constraints
```sql
FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE
FOREIGN KEY (assigned_user_id) REFERENCES users(id) ON DELETE CASCADE
FOREIGN KEY (created_by_user_id) REFERENCES users(id) ON DELETE CASCADE
```

#### Indexes
```sql
INDEX idx_check_ins_team_id (team_id)
INDEX idx_check_ins_assigned_user_id (assigned_user_id)
INDEX idx_check_ins_status (status)
INDEX idx_check_ins_scheduled_date (scheduled_date)
INDEX idx_check_ins_created_by_user_id (created_by_user_id)
INDEX idx_check_ins_team_status (team_id, status)
INDEX idx_check_ins_assigned_status (assigned_user_id, status)
INDEX idx_check_ins_scheduled_status (scheduled_date, status)
```

## Relationships

### 1. CheckIn Model Relationships

#### Belongs To Relationships
```php
// CheckIn belongs to Team
public function team(): BelongsTo
{
    return $this->belongsTo(Team::class);
}

// CheckIn belongs to User (assigned user)
public function assignedUser(): BelongsTo
{
    return $this->belongsTo(User::class, 'assigned_user_id');
}

// CheckIn belongs to User (creator)
public function createdBy(): BelongsTo
{
    return $this->belongsTo(User::class, 'created_by_user_id');
}
```

### 2. Team Model Updates

#### Has Many Relationships
```php
// Team has many CheckIns
public function checkIns(): HasMany
{
    return $this->hasMany(CheckIn::class);
}

// Team has many pending CheckIns
public function pendingCheckIns(): HasMany
{
    return $this->hasMany(CheckIn::class)->where('status', 'pending');
}

// Team has many overdue CheckIns
public function overdueCheckIns(): HasMany
{
    return $this->hasMany(CheckIn::class)->where('status', 'overdue');
}
```

### 3. User Model Updates

#### Has Many Relationships
```php
// User has many CheckIns assigned to them
public function assignedCheckIns(): HasMany
{
    return $this->hasMany(CheckIn::class, 'assigned_user_id');
}

// User has many CheckIns they created
public function createdCheckIns(): HasMany
{
    return $this->hasMany(CheckIn::class, 'created_by_user_id');
}

// User has many pending CheckIns assigned to them
public function pendingAssignedCheckIns(): HasMany
{
    return $this->hasMany(CheckIn::class, 'assigned_user_id')
        ->where('status', 'pending');
}

// User has many overdue CheckIns assigned to them
public function overdueAssignedCheckIns(): HasMany
{
    return $this->hasMany(CheckIn::class, 'assigned_user_id')
        ->where('status', 'overdue');
}
```

## Data Integrity Rules

### 1. Business Rules
- A check-in must belong to a team
- A check-in must have an assigned user
- A check-in must have a creator
- The assigned user must be a member of the team
- The creator must be an admin of the team or organization owner
- Scheduled date cannot be in the past when creating a check-in
- Completed check-ins cannot be modified (except by admins)
- Overdue status is automatically calculated based on scheduled_date

### 2. Validation Rules
```php
// CreateCheckInRequest validation rules
'title' => 'required|string|max:255',
'description' => 'nullable|string',
'team_id' => 'required|exists:teams,id',
'assigned_user_id' => 'required|exists:users,id',
'scheduled_date' => 'required|date|after_or_equal:today',

// UpdateCheckInRequest validation rules
'title' => 'sometimes|required|string|max:255',
'description' => 'nullable|string',
'scheduled_date' => 'sometimes|required|date',

// MarkCheckInCompleteRequest validation rules
'notes' => 'nullable|string|max:1000',
```

## Query Optimization

### 1. Common Queries and Indexes

#### User Dashboard Queries
```sql
-- Get user's assigned check-ins
SELECT * FROM check_ins 
WHERE assigned_user_id = ? 
ORDER BY scheduled_date ASC;

-- Get user's overdue check-ins
SELECT * FROM check_ins 
WHERE assigned_user_id = ? AND status = 'overdue'
ORDER BY scheduled_date ASC;
```

#### Admin Dashboard Queries
```sql
-- Get team's check-ins
SELECT * FROM check_ins 
WHERE team_id = ? 
ORDER BY scheduled_date ASC;

-- Get team's overdue check-ins
SELECT * FROM check_ins 
WHERE team_id = ? AND status = 'overdue'
ORDER BY scheduled_date ASC;
```

#### Owner Dashboard Queries
```sql
-- Get organization's check-ins across all teams
SELECT c.*, t.name as team_name, u.name as assigned_user_name
FROM check_ins c
JOIN teams t ON c.team_id = t.id
JOIN users u ON c.assigned_user_id = u.id
WHERE t.organization_id = ?
ORDER BY c.scheduled_date ASC;
```

### 2. Performance Considerations
- Index on `(team_id, status)` for team-based filtering
- Index on `(assigned_user_id, status)` for user-based filtering
- Index on `(scheduled_date, status)` for date-based queries
- Composite indexes for common query patterns

## Migration File

### Migration: `create_check_ins_table.php`
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('check_ins', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignId('team_id')->constrained()->onDelete('cascade');
            $table->foreignId('assigned_user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('created_by_user_id')->constrained('users')->onDelete('cascade');
            $table->date('scheduled_date');
            $table->timestamp('completed_at')->nullable();
            $table->enum('status', ['pending', 'in_progress', 'completed', 'overdue'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();

            // Indexes for performance
            $table->index('team_id');
            $table->index('assigned_user_id');
            $table->index('status');
            $table->index('scheduled_date');
            $table->index('created_by_user_id');
            $table->index(['team_id', 'status']);
            $table->index(['assigned_user_id', 'status']);
            $table->index(['scheduled_date', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('check_ins');
    }
};
```

## Seeder Data

### Sample Check-in Data
```php
// CheckInSeeder.php
CheckIn::create([
    'title' => 'Complete weekly report',
    'description' => 'Generate and submit the weekly team report',
    'team_id' => 1,
    'assigned_user_id' => 2,
    'created_by_user_id' => 1,
    'scheduled_date' => now()->addDays(3),
    'status' => 'pending',
]);

CheckIn::create([
    'title' => 'Review project documentation',
    'description' => 'Review and update project documentation for client presentation',
    'team_id' => 1,
    'assigned_user_id' => 3,
    'created_by_user_id' => 1,
    'scheduled_date' => now()->addDays(1),
    'status' => 'in_progress',
]);

CheckIn::create([
    'title' => 'Setup development environment',
    'description' => 'Configure local development environment for new team member',
    'team_id' => 2,
    'assigned_user_id' => 4,
    'created_by_user_id' => 2,
    'scheduled_date' => now()->subDays(1),
    'status' => 'overdue',
]);
```

## Future Considerations

### 1. Potential Enhancements
- Add `priority` field for check-in prioritization
- Add `tags` or `categories` for check-in organization
- Add `estimated_hours` for time tracking
- Add `dependencies` for check-ins that depend on others
- Add `recurring` fields for repeating check-ins

### 2. Scalability Considerations
- Consider partitioning for large datasets
- Implement soft deletes for audit trails
- Add archive table for completed check-ins
- Consider caching for frequently accessed data

### 3. Monitoring and Analytics
- Track check-in completion rates
- Monitor overdue patterns
- Analyze team performance metrics
- Generate reports for management 