# Organization Invitation Feature - Implementation Plan

## Overview
This document outlines the implementation plan for a new feature that allows organization admins to invite new members to their organization. The feature will trigger email notifications to invitees, allowing them to create accounts and automatically associate them with the inviting organization.

## Current State Analysis

### Existing Architecture
- **Organization Structure**: Users belong to organizations via `organization_id` foreign key
- **User Types**: Admin and Member roles are defined in `UserType` enum
- **Email System**: Laravel mail is configured and email verification is already implemented
- **Authentication**: Standard Laravel authentication with email verification
- **Frontend**: Inertia.js with Vue.js components and Tailwind CSS

### Key Relationships
- `User` belongs to `Organization` (via `organization_id`)
- `User` has many `Team` (via `team_members` pivot table)
- `Organization` has many `Team`
- `User` has `user_type` enum (admin/member)

## Feature Requirements

### Core Functionality
1. **Admin-only access** to send invitations
2. **Email notifications** to invitees with secure invitation links
3. **Account creation flow** for invited users
4. **Organization association** upon account creation
5. **Invitation management** (view, cancel, resend)

### User Stories
- As an organization admin, I want to invite new members by email
- As an invited user, I want to receive an email with a secure link to join the organization
- As an invited user, I want to create my account and automatically be associated with the organization
- As an admin, I want to view and manage pending invitations
- As an admin, I want to cancel or resend invitations

## Implementation Plan

### 1. Database Changes

#### New Migration: `organization_invitations` table
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
    INDEX idx_organization_invitations_status (status)
);
```

#### Model Updates
- Add `invitations()` relationship to `Organization` model
- Add `invitedInvitations()` relationship to `User` model

### 2. Backend Implementation

#### Models & Relationships
- **New Model**: `OrganizationInvitation` with factory and seeder
- **Relationships**:
  - `OrganizationInvitation` belongs to `Organization`
  - `OrganizationInvitation` belongs to `User` (invited_by)
  - `Organization` has many `OrganizationInvitation`
  - `User` has many `OrganizationInvitation` (as inviter)

#### Controllers
- **`OrganizationInvitationController`**:
  - `index()` - List pending invitations for organization
  - `store()` - Send new invitation
  - `resend()` - Resend invitation
  - `destroy()` - Cancel invitation

- **`InvitationAcceptanceController`**:
  - `show()` - Display invitation acceptance page
  - `store()` - Process invitation acceptance and account creation

#### Requests & Validation
- **`SendInvitationRequest`**: Email validation, admin authorization
- **`AcceptInvitationRequest`**: Account creation validation

#### Notifications
- **`OrganizationInvitationNotification`**: Email notification with secure link
- **Email Template**: Professional invitation email with organization branding

#### Routes
```php
// Organization invitations
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('invitations', [OrganizationInvitationController::class, 'index'])->name('invitations.index');
    Route::post('invitations', [OrganizationInvitationController::class, 'store'])->name('invitations.store');
    Route::post('invitations/{invitation}/resend', [OrganizationInvitationController::class, 'resend'])->name('invitations.resend');
    Route::delete('invitations/{invitation}', [OrganizationInvitationController::class, 'destroy'])->name('invitations.destroy');
});

// Public invitation acceptance
Route::get('invitations/{token}/accept', [InvitationAcceptanceController::class, 'show'])->name('invitations.accept');
Route::post('invitations/{token}/accept', [InvitationAcceptanceController::class, 'store'])->name('invitations.accept.store');
```

### 3. Frontend Implementation

#### Pages
- **`Invitations/Index.vue`**: List and manage invitations
- **`Invitations/Create.vue`**: Send new invitation form
- **`Invitations/Accept.vue`**: Accept invitation and create account

#### Components
- **`InvitationList.vue`**: Display pending invitations with actions
- **`SendInvitationForm.vue`**: Form to send new invitation
- **`InvitationStatus.vue`**: Show invitation status and expiration

#### Navigation
- Add invitations link to sidebar navigation (admin-only)
- Add to admin menu section

### 4. Security & Validation

#### Security Measures
- **Token Generation**: Secure random tokens using `Str::random(64)`
- **Expiration**: Invitations expire after configurable time (default: 7 days)
- **Authorization**: Only organization admins can send invitations
- **Email Validation**: Prevent duplicate invitations to same email
- **Rate Limiting**: Prevent spam invitations (max 10 per hour per admin)

#### Validation Rules
- Email must be valid and not already a member of the organization
- Token must be valid and not expired
- Admin must belong to the organization they're inviting to

### 5. Email Flow

#### Process Flow
1. **Admin sends invitation** → Creates `OrganizationInvitation` record with secure token
2. **Email sent** → `OrganizationInvitationNotification` with secure link
3. **User clicks link** → Redirected to accept invitation page
4. **User creates account** → Account created and associated with organization
5. **Invitation marked as accepted** → Status updated, `accepted_at` timestamp set

#### Email Template Features
- Organization branding and name
- Inviter's name
- Clear call-to-action button
- Security notice about link expiration
- Organization description/context

### 6. Testing Strategy

#### Feature Tests
- Test complete invitation flow from admin to user acceptance
- Test email sending and token validation
- Test account creation and organization association
- Test invitation management (resend, cancel)

#### Unit Tests
- Test invitation model relationships and scopes
- Test notification class
- Test request validation rules

#### Authorization Tests
- Ensure only admins can send invitations
- Ensure users can only accept valid invitations
- Test invitation expiration handling

### 7. Database Seeder
- **`OrganizationInvitationSeeder`**: Create sample invitations for testing
- Include various states: pending, accepted, expired

## Implementation Order

### Phase 1: Foundation
1. Database migration and models
2. Basic relationships and factories
3. Seeder for testing

### Phase 2: Backend Core
1. Controllers and request validation
2. Email notification system
3. Token generation and validation

### Phase 3: Frontend
1. Invitation management pages
2. Invitation acceptance flow
3. Navigation and routing

### Phase 4: Polish
1. Testing (unit and feature)
2. Error handling and edge cases
3. Documentation and user guides

## Key Files to Create/Modify

### New Files
- `database/migrations/xxxx_create_organization_invitations_table.php`
- `app/Models/OrganizationInvitation.php`
- `app/Http/Controllers/OrganizationInvitationController.php`
- `app/Http/Controllers/InvitationAcceptanceController.php`
- `app/Http/Requests/SendInvitationRequest.php`
- `app/Http/Requests/AcceptInvitationRequest.php`
- `app/Notifications/OrganizationInvitationNotification.php`
- `resources/js/pages/Invitations/Index.vue`
- `resources/js/pages/Invitations/Create.vue`
- `resources/js/pages/Invitations/Accept.vue`
- `resources/js/components/InvitationList.vue`
- `resources/js/components/SendInvitationForm.vue`
- `database/seeders/OrganizationInvitationSeeder.php`

### Modified Files
- `app/Models/Organization.php` - Add invitations relationship
- `app/Models/User.php` - Add invitedInvitations relationship
- `routes/web.php` - Add invitation routes
- `resources/js/components/NavMain.vue` - Add invitations link

## Success Criteria
- [ ] Admins can send invitations to new users
- [ ] Invited users receive professional email notifications
- [ ] Users can create accounts through invitation links
- [ ] New accounts are automatically associated with the organization
- [ ] Admins can manage pending invitations
- [ ] Invitations expire after configurable time
- [ ] Comprehensive test coverage
- [ ] Security measures prevent abuse

## Future Enhancements
- Bulk invitation sending
- Invitation templates customization
- Integration with team assignment
- Invitation analytics and reporting
- SSO integration for enterprise organizations 