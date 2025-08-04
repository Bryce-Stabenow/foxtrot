# Check-In Feature - API Endpoints

## Overview
This document outlines all the API endpoints for the check-in feature, including request/response formats, validation rules, and authorization requirements.

## Base URL
All endpoints are prefixed with `/check-ins` and require authentication.

## Authentication
All endpoints require:
- Valid authentication token
- Email verification (for non-admin actions)
- Appropriate authorization based on user role

## Endpoints

### 1. List Check-ins

#### GET `/check-ins`

**Description**: Retrieve check-ins based on user role and permissions

**Authorization**: 
- Members: Can view their assigned check-ins
- Admins: Can view check-ins for their team members
- Owners: Can view all check-ins across the organization

**Query Parameters**:
```php
[
    'status' => 'string', // pending, in_progress, completed, overdue
    'team_id' => 'integer', // Filter by team (admin/owner only)
    'assigned_user_id' => 'integer', // Filter by assigned user (admin/owner only)
    'scheduled_date' => 'date', // Filter by scheduled date
    'search' => 'string', // Search in title and description
    'sort' => 'string', // scheduled_date, created_at, title
    'order' => 'string', // asc, desc
    'per_page' => 'integer', // Default: 15
]
```

**Response**:
```json
{
    "data": [
        {
            "id": 1,
            "title": "Complete weekly report",
            "description": "Generate and submit the weekly team report",
            "team_id": 1,
            "team": {
                "id": 1,
                "name": "Development Team"
            },
            "assigned_user_id": 2,
            "assigned_user": {
                "id": 2,
                "name": "John Doe",
                "email": "john@example.com"
            },
            "created_by_user_id": 1,
            "created_by_user": {
                "id": 1,
                "name": "Admin User",
                "email": "admin@example.com"
            },
            "scheduled_date": "2024-01-15",
            "completed_at": null,
            "status": "pending",
            "notes": null,
            "created_at": "2024-01-10T10:00:00Z",
            "updated_at": "2024-01-10T10:00:00Z"
        }
    ],
    "meta": {
        "current_page": 1,
        "last_page": 5,
        "per_page": 15,
        "total": 75
    },
    "filters": {
        "status_counts": {
            "pending": 25,
            "in_progress": 15,
            "completed": 30,
            "overdue": 5
        }
    }
}
```

### 2. Show Check-in

#### GET `/check-ins/{checkIn}`

**Description**: Retrieve a specific check-in by ID

**Authorization**: 
- Members: Can view their assigned check-ins
- Admins: Can view check-ins for their team members
- Owners: Can view any check-in

**Response**:
```json
{
    "data": {
        "id": 1,
        "title": "Complete weekly report",
        "description": "Generate and submit the weekly team report",
        "team_id": 1,
        "team": {
            "id": 1,
            "name": "Development Team"
        },
        "assigned_user_id": 2,
        "assigned_user": {
            "id": 2,
            "name": "John Doe",
            "email": "john@example.com"
        },
        "created_by_user_id": 1,
        "created_by_user": {
            "id": 1,
            "name": "Admin User",
            "email": "admin@example.com"
        },
        "scheduled_date": "2024-01-15",
        "completed_at": null,
        "status": "pending",
        "notes": null,
        "created_at": "2024-01-10T10:00:00Z",
        "updated_at": "2024-01-10T10:00:00Z"
    }
}
```

### 3. Create Check-in

#### POST `/check-ins`

**Description**: Create a new check-in

**Authorization**: 
- Admins: Can create check-ins for their team members
- Owners: Can create check-ins for any team member

**Request Body**:
```json
{
    "title": "Complete weekly report",
    "description": "Generate and submit the weekly team report",
    "team_id": 1,
    "assigned_user_id": 2,
    "scheduled_date": "2024-01-15"
}
```

**Validation Rules**:
```php
[
    'title' => 'required|string|max:255',
    'description' => 'nullable|string',
    'team_id' => 'required|exists:teams,id',
    'assigned_user_id' => 'required|exists:users,id',
    'scheduled_date' => 'required|date|after_or_equal:today'
]
```

**Response**:
```json
{
    "data": {
        "id": 1,
        "title": "Complete weekly report",
        "description": "Generate and submit the weekly team report",
        "team_id": 1,
        "team": {
            "id": 1,
            "name": "Development Team"
        },
        "assigned_user_id": 2,
        "assigned_user": {
            "id": 2,
            "name": "John Doe",
            "email": "john@example.com"
        },
        "created_by_user_id": 1,
        "created_by_user": {
            "id": 1,
            "name": "Admin User",
            "email": "admin@example.com"
        },
        "scheduled_date": "2024-01-15",
        "completed_at": null,
        "status": "pending",
        "notes": null,
        "created_at": "2024-01-10T10:00:00Z",
        "updated_at": "2024-01-10T10:00:00Z"
    },
    "message": "Check-in created successfully"
}
```

### 4. Update Check-in

#### PUT `/check-ins/{checkIn}`

**Description**: Update an existing check-in

**Authorization**: 
- Admins: Can update check-ins they created for their team
- Owners: Can update any check-in

**Request Body**:
```json
{
    "title": "Updated weekly report",
    "description": "Updated description",
    "scheduled_date": "2024-01-20"
}
```

**Validation Rules**:
```php
[
    'title' => 'sometimes|required|string|max:255',
    'description' => 'nullable|string',
    'scheduled_date' => 'sometimes|required|date'
]
```

**Response**:
```json
{
    "data": {
        "id": 1,
        "title": "Updated weekly report",
        "description": "Updated description",
        "team_id": 1,
        "team": {
            "id": 1,
            "name": "Development Team"
        },
        "assigned_user_id": 2,
        "assigned_user": {
            "id": 2,
            "name": "John Doe",
            "email": "john@example.com"
        },
        "created_by_user_id": 1,
        "created_by_user": {
            "id": 1,
            "name": "Admin User",
            "email": "admin@example.com"
        },
        "scheduled_date": "2024-01-20",
        "completed_at": null,
        "status": "pending",
        "notes": null,
        "created_at": "2024-01-10T10:00:00Z",
        "updated_at": "2024-01-10T11:00:00Z"
    },
    "message": "Check-in updated successfully"
}
```

### 5. Delete Check-in

#### DELETE `/check-ins/{checkIn}`

**Description**: Delete a check-in

**Authorization**: 
- Admins: Can delete check-ins they created for their team
- Owners: Can delete any check-in

**Response**:
```json
{
    "message": "Check-in deleted successfully"
}
```

### 6. Mark Check-in Complete

#### PATCH `/check-ins/{checkIn}/complete`

**Description**: Mark a check-in as completed

**Authorization**: 
- Members: Can mark their assigned check-ins as complete
- Admins: Can mark any check-in as complete

**Request Body**:
```json
{
    "notes": "Completed the weekly report and submitted to management"
}
```

**Validation Rules**:
```php
[
    'notes' => 'nullable|string|max:1000'
]
```

**Response**:
```json
{
    "data": {
        "id": 1,
        "title": "Complete weekly report",
        "description": "Generate and submit the weekly team report",
        "team_id": 1,
        "team": {
            "id": 1,
            "name": "Development Team"
        },
        "assigned_user_id": 2,
        "assigned_user": {
            "id": 2,
            "name": "John Doe",
            "email": "john@example.com"
        },
        "created_by_user_id": 1,
        "created_by_user": {
            "id": 1,
            "name": "Admin User",
            "email": "admin@example.com"
        },
        "scheduled_date": "2024-01-15",
        "completed_at": "2024-01-10T12:00:00Z",
        "status": "completed",
        "notes": "Completed the weekly report and submitted to management",
        "created_at": "2024-01-10T10:00:00Z",
        "updated_at": "2024-01-10T12:00:00Z"
    },
    "message": "Check-in marked as completed"
}
```

### 7. Mark Check-in In Progress

#### PATCH `/check-ins/{checkIn}/in-progress`

**Description**: Mark a check-in as in progress

**Authorization**: 
- Members: Can mark their assigned check-ins as in progress
- Admins: Can mark any check-in as in progress

**Response**:
```json
{
    "data": {
        "id": 1,
        "title": "Complete weekly report",
        "description": "Generate and submit the weekly team report",
        "team_id": 1,
        "team": {
            "id": 1,
            "name": "Development Team"
        },
        "assigned_user_id": 2,
        "assigned_user": {
            "id": 2,
            "name": "John Doe",
            "email": "john@example.com"
        },
        "created_by_user_id": 1,
        "created_by_user": {
            "id": 1,
            "name": "Admin User",
            "email": "admin@example.com"
        },
        "scheduled_date": "2024-01-15",
        "completed_at": null,
        "status": "in_progress",
        "notes": null,
        "created_at": "2024-01-10T10:00:00Z",
        "updated_at": "2024-01-10T12:00:00Z"
    },
    "message": "Check-in marked as in progress"
}
```

## Dashboard Endpoints

### 8. Dashboard Statistics

#### GET `/check-ins/dashboard/stats`

**Description**: Get check-in statistics for dashboard

**Authorization**: All authenticated users

**Response**:
```json
{
    "data": {
        "total_check_ins": 75,
        "pending_check_ins": 25,
        "in_progress_check_ins": 15,
        "completed_check_ins": 30,
        "overdue_check_ins": 5,
        "completion_rate": 40.0,
        "upcoming_deadlines": [
            {
                "id": 1,
                "title": "Complete weekly report",
                "scheduled_date": "2024-01-15",
                "status": "pending"
            }
        ],
        "recent_activity": [
            {
                "id": 2,
                "title": "Review documentation",
                "status": "completed",
                "updated_at": "2024-01-10T12:00:00Z"
            }
        ]
    }
}
```

### 9. Team Check-ins

#### GET `/teams/{team}/check-ins`

**Description**: Get check-ins for a specific team

**Authorization**: 
- Team members: Can view their team's check-ins
- Admins: Can view any team's check-ins

**Query Parameters**: Same as list check-ins

**Response**: Same format as list check-ins

## Error Responses

### Validation Errors
```json
{
    "message": "The given data was invalid.",
    "errors": {
        "title": [
            "The title field is required."
        ],
        "scheduled_date": [
            "The scheduled date must be a date after or equal to today."
        ]
    }
}
```

### Authorization Errors
```json
{
    "message": "You are not authorized to perform this action."
}
```

### Not Found Errors
```json
{
    "message": "Check-in not found."
}
```

## Controller Methods

### CheckInController

```php
class CheckInController extends Controller
{
    public function index(Request $request)
    {
        // Return paginated check-ins based on user role
    }

    public function show(CheckIn $checkIn)
    {
        // Return specific check-in with authorization
    }

    public function store(CreateCheckInRequest $request)
    {
        // Create new check-in
    }

    public function update(UpdateCheckInRequest $request, CheckIn $checkIn)
    {
        // Update existing check-in
    }

    public function destroy(CheckIn $checkIn)
    {
        // Delete check-in
    }

    public function markComplete(MarkCheckInCompleteRequest $request, CheckIn $checkIn)
    {
        // Mark check-in as completed
    }

    public function markInProgress(CheckIn $checkIn)
    {
        // Mark check-in as in progress
    }

    public function dashboardStats()
    {
        // Return dashboard statistics
    }
}
```

## Request Classes

### CreateCheckInRequest
```php
class CreateCheckInRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'team_id' => 'required|exists:teams,id',
            'assigned_user_id' => 'required|exists:users,id',
            'scheduled_date' => 'required|date|after_or_equal:today'
        ];
    }

    public function authorize(): bool
    {
        // Check if user can create check-ins for the team
    }
}
```

### UpdateCheckInRequest
```php
class UpdateCheckInRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'scheduled_date' => 'sometimes|required|date'
        ];
    }

    public function authorize(): bool
    {
        // Check if user can update the check-in
    }
}
```

### MarkCheckInCompleteRequest
```php
class MarkCheckInCompleteRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'notes' => 'nullable|string|max:1000'
        ];
    }

    public function authorize(): bool
    {
        // Check if user can mark the check-in as complete
    }
}
```

## Policy Rules

### CheckInPolicy
```php
class CheckInPolicy
{
    public function viewAny(User $user): bool
    {
        // Users can view check-ins they have access to
    }

    public function view(User $user, CheckIn $checkIn): bool
    {
        // Check if user can view the specific check-in
    }

    public function create(User $user): bool
    {
        // Only admins and owners can create check-ins
    }

    public function update(User $user, CheckIn $checkIn): bool
    {
        // Check if user can update the check-in
    }

    public function delete(User $user, CheckIn $checkIn): bool
    {
        // Check if user can delete the check-in
    }

    public function markComplete(User $user, CheckIn $checkIn): bool
    {
        // Check if user can mark the check-in as complete
    }
}
``` 