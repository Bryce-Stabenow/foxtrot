# Check-In Feature - Implementation Plan

## Overview
This document outlines the implementation plan for the core check-in feature of the Foxtrot application. Check-ins are tasks assigned to team members by admins, with scheduled completion dates and status tracking. Completed check-ins are viewable by the assigned user, team admins, and organization owners.

## Current State Analysis

### Existing Architecture
- **Organization Structure**: Users belong to organizations via `organization_id` foreign key
- **Team Structure**: Teams belong to organizations, users can be members of multiple teams
- **User Types**: Member, Admin, and Owner roles defined in `UserType` enum
- **Authentication**: Standard Laravel authentication with email verification
- **Frontend**: Inertia.js with Vue.js components and Tailwind CSS

### Key Relationships
- `User` belongs to `Organization` (via `organization_id`)
- `User` has many `Team` (via `team_members` pivot table)
- `Organization` has many `Team`
- `User` has `user_type` enum (member/admin/owner)

## Feature Requirements

### Core Functionality
1. **Admin-only creation** of check-ins for team members
2. **Scheduled completion dates** for each check-in
3. **Status tracking** (pending, in_progress, completed, overdue)
4. **Multi-level visibility** (assigned user, team admins, organization owner)
5. **Check-in management** (create, edit, delete, mark complete)
6. **Dashboard views** for different user roles

### User Stories
- As a team admin, I want to create check-ins for team members with scheduled completion dates
- As a team member, I want to see my assigned check-ins and their status
- As a team member, I want to mark check-ins as completed
- As a team admin, I want to view all check-ins for my team members
- As an organization owner, I want to view all check-ins across all teams
- As any user, I want to see overdue check-ins highlighted
- As a team admin, I want to edit or delete check-ins I created

## Implementation Plan

### 1. Database Changes

#### New Migration: `check_ins` table
```sql
CREATE TABLE check_ins (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    team_id BIGINT UNSIGNED NOT NULL,
    assigned_user_id BIGINT UNSIGNED NOT NULL,
    created_by_user_id BIGINT UNSIGNED NOT NULL,
    scheduled_date DATE NOT NULL,
    completed_at TIMESTAMP NULL,
    status ENUM('pending', 'in_progress', 'completed', 'overdue') DEFAULT 'pending',
    notes TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by_user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_check_ins_team_id (team_id),
    INDEX idx_check_ins_assigned_user_id (assigned_user_id),
    INDEX idx_check_ins_status (status),
    INDEX idx_check_ins_scheduled_date (scheduled_date),
    INDEX idx_check_ins_created_by_user_id (created_by_user_id)
);
```

#### Model Updates
- Add `checkIns()` relationship to `Team` model
- Add `assignedCheckIns()` and `createdCheckIns()` relationships to `User` model

### 2. Backend Implementation

#### Models & Relationships
- **New Model**: `CheckIn` with factory and seeder
- **Relationships**:
  - `CheckIn` belongs to `Team`
  - `CheckIn` belongs to `User` (assigned_user)
  - `CheckIn` belongs to `User` (created_by)
  - `Team` has many `CheckIn`
  - `User` has many `CheckIn` (as assigned_user)
  - `User` has many `CheckIn` (as created_by)

#### Enums
- **New Enum**: `CheckInStatus` with values: pending, in_progress, completed, overdue

#### Controllers
- **`CheckInController`**:
  - `index()` - List check-ins based on user role and permissions
  - `show()` - Show individual check-in details
  - `store()` - Create new check-in (admin only)
  - `update()` - Update check-in (admin only)
  - `destroy()` - Delete check-in (admin only)
  - `markComplete()` - Mark check-in as completed (assigned user only)
  - `markInProgress()` - Mark check-in as in progress (assigned user only)

#### Requests & Validation
- **`CreateCheckInRequest`**: Title, description, team_id, assigned_user_id, scheduled_date validation
- **`UpdateCheckInRequest`**: Same as create with optional fields
- **`MarkCheckInCompleteRequest`**: Notes validation

#### Policies
- **`CheckInPolicy`**: Authorization rules for check-in actions
  - Admins can create/edit/delete check-ins for their teams
  - Assigned users can mark their check-ins complete
  - Organization owners can view all check-ins
  - Team admins can view check-ins for their team members

#### Routes
```php
// Check-in management
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('check-ins', [CheckInController::class, 'index'])->name('check-ins.index');
    Route::get('check-ins/{checkIn}', [CheckInController::class, 'show'])->name('check-ins.show');
    Route::post('check-ins', [CheckInController::class, 'store'])->name('check-ins.store');
    Route::put('check-ins/{checkIn}', [CheckInController::class, 'update'])->name('check-ins.update');
    Route::delete('check-ins/{checkIn}', [CheckInController::class, 'destroy'])->name('check-ins.destroy');
    Route::patch('check-ins/{checkIn}/complete', [CheckInController::class, 'markComplete'])->name('check-ins.complete');
    Route::patch('check-ins/{checkIn}/in-progress', [CheckInController::class, 'markInProgress'])->name('check-ins.in-progress');
});
```

### 3. Frontend Implementation

#### Pages
- **`CheckIns/Index.vue`**: List check-ins with filtering and sorting
- **`CheckIns/Create.vue`**: Create new check-in form (admin only)
- **`CheckIns/Show.vue`**: Show individual check-in details
- **`CheckIns/Edit.vue`**: Edit check-in form (admin only)

#### Components
- **`CheckInList.vue`**: Display check-ins with status indicators
- **`CheckInCard.vue`**: Individual check-in card component
- **`CheckInForm.vue`**: Form for creating/editing check-ins
- **`CheckInStatus.vue`**: Status indicator with color coding
- **`CheckInFilters.vue`**: Filter and sort options
- **`CheckInStats.vue`**: Statistics dashboard for admins/owners

#### Navigation
- Add check-ins link to main navigation
- Add to team management section
- Add quick access from dashboard

### 4. Status Management

#### Status Logic
- **Pending**: Default status for new check-ins
- **In Progress**: User has started working on the check-in
- **Completed**: User has marked the check-in as complete
- **Overdue**: Scheduled date has passed and status is not completed

#### Status Updates
- Automatic overdue detection via scheduled job
- Manual status updates by assigned users
- Status change notifications to admins

### 5. Dashboard Integration

#### Dashboard Views
- **Member Dashboard**: Show assigned check-ins with upcoming deadlines
- **Admin Dashboard**: Show team check-ins with completion statistics
- **Owner Dashboard**: Show organization-wide check-in overview

#### Widgets
- **Upcoming Check-ins**: Show check-ins due in next 7 days
- **Overdue Check-ins**: Highlight overdue items
- **Completion Rate**: Show team/organization completion statistics
- **Recent Activity**: Show recent check-in updates

### 6. Notifications

#### Email Notifications
- **Check-in Assignment**: Notify user when assigned to a check-in
- **Check-in Reminder**: Remind users of upcoming deadlines
- **Check-in Completion**: Notify admins when check-ins are completed
- **Overdue Alert**: Alert admins of overdue check-ins

#### In-App Notifications
- Real-time notifications for status changes
- Dashboard alerts for overdue items
- Activity feed for check-in updates

### 7. Testing Strategy

#### Feature Tests
- Test complete check-in lifecycle from creation to completion
- Test authorization rules for different user roles
- Test status updates and overdue detection
- Test dashboard views and filtering

#### Unit Tests
- Test check-in model relationships and scopes
- Test status enum and validation
- Test policy authorization rules

#### Authorization Tests
- Ensure only admins can create/edit/delete check-ins
- Ensure users can only update their assigned check-ins
- Test organization owner access to all check-ins

### 8. Database Seeder
- **`CheckInSeeder`**: Create sample check-ins for testing
- Include various states: pending, in_progress, completed, overdue
- Include different teams and users

## Implementation Order

### Phase 1: Foundation
1. Database migration and models
2. Basic relationships and factories
3. Status enum and validation
4. Seeder for testing

### Phase 2: Backend Core
1. Controllers and request validation
2. Policy authorization rules
3. Status management logic
4. Basic API endpoints

### Phase 3: Frontend
1. Check-in list and detail pages
2. Create/edit forms for admins
3. Status update functionality
4. Dashboard integration

### Phase 4: Advanced Features
1. Notifications and email alerts
2. Dashboard widgets and statistics
3. Filtering and search functionality
4. Overdue detection and alerts

### Phase 5: Polish
1. Testing (unit and feature)
2. Error handling and edge cases
3. Performance optimization
4. Documentation and user guides

## Key Files to Create/Modify

### New Files
- `database/migrations/xxxx_create_check_ins_table.php`
- `app/Models/CheckIn.php`
- `app/Enums/CheckInStatus.php`
- `app/Http/Controllers/CheckInController.php`
- `app/Http/Requests/CreateCheckInRequest.php`
- `app/Http/Requests/UpdateCheckInRequest.php`
- `app/Http/Requests/MarkCheckInCompleteRequest.php`
- `app/Policies/CheckInPolicy.php`
- `resources/js/pages/CheckIns/Index.vue`
- `resources/js/pages/CheckIns/Create.vue`
- `resources/js/pages/CheckIns/Show.vue`
- `resources/js/pages/CheckIns/Edit.vue`
- `resources/js/components/CheckInList.vue`
- `resources/js/components/CheckInCard.vue`
- `resources/js/components/CheckInForm.vue`
- `resources/js/components/CheckInStatus.vue`
- `resources/js/components/CheckInFilters.vue`
- `resources/js/components/CheckInStats.vue`
- `database/seeders/CheckInSeeder.php`
- `database/factories/CheckInFactory.php`

### Modified Files
- `app/Models/Team.php` - Add checkIns relationship
- `app/Models/User.php` - Add assignedCheckIns and createdCheckIns relationships
- `routes/web.php` - Add check-in routes
- `resources/js/components/NavMain.vue` - Add check-ins link
- `resources/js/pages/Dashboard.vue` - Add check-in widgets
- `app/Providers/AuthServiceProvider.php` - Register CheckInPolicy

## Success Criteria
- [ ] Admins can create check-ins for team members with scheduled dates
- [ ] Users can view and update their assigned check-ins
- [ ] Status tracking works correctly (pending, in_progress, completed, overdue)
- [ ] Dashboard shows appropriate check-ins based on user role
- [ ] Authorization rules prevent unauthorized access
- [ ] Overdue detection works automatically
- [ ] Comprehensive test coverage
- [ ] Email notifications for important events

## Future Enhancements
- Bulk check-in creation
- Check-in templates and recurring check-ins
- File attachments for check-ins
- Check-in comments and collaboration
- Advanced reporting and analytics
- Mobile app integration
- Integration with external task management tools
- Check-in dependencies and prerequisites 