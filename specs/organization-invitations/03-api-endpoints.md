# Organization Invitations - API Endpoints

## Overview
This document specifies the API endpoints required for the organization invitation feature, including request/response formats, validation rules, and authorization requirements.

## Authentication & Authorization

### Middleware
- **Protected Routes**: `auth`, `verified` middleware
- **Admin Authorization**: Custom middleware or policy checks for admin-only actions
- **Organization Access**: Ensure users can only access their organization's invitations

### Authorization Rules
- Only organization admins can send invitations
- Users can only view/manage invitations for their own organization
- Public invitation acceptance endpoints require no authentication

## API Endpoints

### 1. List Organization Invitations

#### Endpoint
```
GET /invitations
```

#### Description
Retrieve all invitations for the authenticated user's organization.

#### Authorization
- User must be authenticated and verified
- User must be an admin of their organization

#### Query Parameters
| Parameter | Type | Description | Default |
|-----------|------|-------------|---------|
| `status` | string | Filter by status (pending, accepted, expired) | all |
| `per_page` | integer | Number of invitations per page | 15 |
| `page` | integer | Page number for pagination | 1 |

#### Response Format
```json
{
    "data": [
        {
            "id": 1,
            "email": "john@example.com",
            "status": "pending",
            "expires_at": "2024-01-15T10:00:00Z",
            "created_at": "2024-01-08T10:00:00Z",
            "invited_by": {
                "id": 1,
                "name": "Admin User",
                "email": "admin@example.com"
            },
            "organization": {
                "id": 1,
                "name": "Acme Corp"
            }
        }
    ],
    "meta": {
        "current_page": 1,
        "last_page": 1,
        "per_page": 15,
        "total": 1
    }
}
```

#### HTTP Status Codes
- `200 OK`: Successfully retrieved invitations
- `403 Forbidden`: User is not an admin
- `401 Unauthorized`: User not authenticated

---

### 2. Send Organization Invitation

#### Endpoint
```
POST /invitations
```

#### Description
Send a new invitation to join the organization.

#### Authorization
- User must be authenticated and verified
- User must be an admin of their organization

#### Request Body
```json
{
    "email": "newuser@example.com",
    "message": "Optional personal message"
}
```

#### Validation Rules
```php
[
    'email' => [
        'required',
        'email',
        'max:255',
        Rule::unique('organization_invitations')
            ->where('organization_id', $organizationId)
            ->where('status', 'pending'),
        Rule::notIn(User::where('organization_id', $organizationId)->pluck('email')),
    ],
    'message' => 'nullable|string|max:500'
]
```

#### Response Format
```json
{
    "data": {
        "id": 1,
        "email": "newuser@example.com",
        "status": "pending",
        "expires_at": "2024-01-15T10:00:00Z",
        "created_at": "2024-01-08T10:00:00Z",
        "invited_by": {
            "id": 1,
            "name": "Admin User",
            "email": "admin@example.com"
        },
        "organization": {
            "id": 1,
            "name": "Acme Corp"
        }
    },
    "message": "Invitation sent successfully"
}
```

#### HTTP Status Codes
- `201 Created`: Invitation sent successfully
- `422 Unprocessable Entity`: Validation errors
- `403 Forbidden`: User is not an admin
- `429 Too Many Requests`: Rate limit exceeded

---

### 3. Resend Organization Invitation

#### Endpoint
```
POST /invitations/{invitation}/resend
```

#### Description
Resend an existing invitation to the same email address.

#### Authorization
- User must be authenticated and verified
- User must be an admin of their organization
- Invitation must belong to user's organization

#### URL Parameters
| Parameter | Type | Description |
|-----------|------|-------------|
| `invitation` | integer | Invitation ID |

#### Response Format
```json
{
    "data": {
        "id": 1,
        "email": "newuser@example.com",
        "status": "pending",
        "expires_at": "2024-01-15T10:00:00Z",
        "updated_at": "2024-01-08T15:30:00Z"
    },
    "message": "Invitation resent successfully"
}
```

#### HTTP Status Codes
- `200 OK`: Invitation resent successfully
- `404 Not Found`: Invitation not found
- `403 Forbidden`: User is not an admin or invitation doesn't belong to their organization
- `422 Unprocessable Entity`: Invitation is not in pending status

---

### 4. Cancel Organization Invitation

#### Endpoint
```
DELETE /invitations/{invitation}
```

#### Description
Cancel a pending invitation.

#### Authorization
- User must be authenticated and verified
- User must be an admin of their organization
- Invitation must belong to user's organization

#### URL Parameters
| Parameter | Type | Description |
|-----------|------|-------------|
| `invitation` | integer | Invitation ID |

#### Response Format
```json
{
    "message": "Invitation cancelled successfully"
}
```

#### HTTP Status Codes
- `200 OK`: Invitation cancelled successfully
- `404 Not Found`: Invitation not found
- `403 Forbidden`: User is not an admin or invitation doesn't belong to their organization
- `422 Unprocessable Entity`: Invitation is not in pending status

---

### 5. Show Invitation Acceptance Page

#### Endpoint
```
GET /invitations/{token}/accept
```

#### Description
Display the invitation acceptance page for public access.

#### Authorization
- No authentication required (public endpoint)

#### URL Parameters
| Parameter | Type | Description |
|-----------|------|-------------|
| `token` | string | Invitation token |

#### Response Format
```json
{
    "data": {
        "token": "abc123...",
        "email": "newuser@example.com",
        "organization": {
            "id": 1,
            "name": "Acme Corp",
            "description": "Leading technology company"
        },
        "invited_by": {
            "name": "Admin User"
        },
        "expires_at": "2024-01-15T10:00:00Z",
        "is_valid": true
    }
}
```

#### HTTP Status Codes
- `200 OK`: Invitation details retrieved
- `404 Not Found`: Invalid or expired token
- `410 Gone`: Invitation already accepted

---

### 6. Accept Organization Invitation

#### Endpoint
```
POST /invitations/{token}/accept
```

#### Description
Accept an invitation and create a new user account.

#### Authorization
- No authentication required (public endpoint)

#### URL Parameters
| Parameter | Type | Description |
|-----------|------|-------------|
| `token` | string | Invitation token |

#### Request Body
```json
{
    "name": "John Doe",
    "password": "securepassword123",
    "password_confirmation": "securepassword123"
}
```

#### Validation Rules
```php
[
    'name' => 'required|string|max:255',
    'password' => 'required|string|min:8|confirmed',
    'password_confirmation' => 'required'
]
```

#### Response Format
```json
{
    "data": {
        "user": {
            "id": 2,
            "name": "John Doe",
            "email": "newuser@example.com",
            "organization_id": 1
        },
        "invitation": {
            "id": 1,
            "status": "accepted",
            "accepted_at": "2024-01-08T16:00:00Z"
        }
    },
    "message": "Account created successfully"
}
```

#### HTTP Status Codes
- `201 Created`: Account created and invitation accepted
- `422 Unprocessable Entity`: Validation errors
- `404 Not Found`: Invalid or expired token
- `409 Conflict`: Invitation already accepted

---

## Error Response Format

### Standard Error Response
```json
{
    "message": "The given data was invalid.",
    "errors": {
        "email": [
            "The email field is required."
        ],
        "password": [
            "The password must be at least 8 characters."
        ]
    }
}
```

### Common HTTP Status Codes
- `400 Bad Request`: Invalid request format
- `401 Unauthorized`: Authentication required
- `403 Forbidden`: Insufficient permissions
- `404 Not Found`: Resource not found
- `422 Unprocessable Entity`: Validation errors
- `429 Too Many Requests`: Rate limit exceeded
- `500 Internal Server Error`: Server error

---

## Rate Limiting

### Invitation Sending
- **Limit**: 10 invitations per hour per user
- **Window**: 1 hour
- **Headers**: 
  - `X-RateLimit-Limit`: 10
  - `X-RateLimit-Remaining`: 9
  - `X-RateLimit-Reset`: 1642161600

### Invitation Acceptance
- **Limit**: 5 attempts per hour per IP
- **Window**: 1 hour
- **Purpose**: Prevent brute force attacks on invitation tokens

---

## Request/Response Headers

### Standard Headers
```
Content-Type: application/json
Accept: application/json
Authorization: Bearer {token} (for protected endpoints)
```

### Rate Limiting Headers
```
X-RateLimit-Limit: 10
X-RateLimit-Remaining: 9
X-RateLimit-Reset: 1642161600
```

---

## Pagination

### Pagination Headers
```
Link: <https://api.example.com/invitations?page=2>; rel="next", <https://api.example.com/invitations?page=1>; rel="prev"
X-Total-Count: 45
X-Per-Page: 15
```

### Pagination Meta
```json
{
    "meta": {
        "current_page": 1,
        "last_page": 3,
        "per_page": 15,
        "total": 45,
        "from": 1,
        "to": 15
    }
}
``` 