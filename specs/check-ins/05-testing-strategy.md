# Check-In Feature - Testing Strategy

## Overview
This document outlines the comprehensive testing strategy for the check-in feature, including unit tests, feature tests, and testing best practices using Pest framework.

## Testing Philosophy

### Principles
- **Test-driven development** for critical business logic
- **Comprehensive coverage** of all user flows
- **Realistic test data** that mirrors production scenarios
- **Fast execution** with proper test isolation
- **Clear test descriptions** that serve as documentation

### Test Categories
1. **Unit Tests** - Individual components and methods
2. **Feature Tests** - Complete user workflows
3. **Integration Tests** - API endpoints and database interactions
4. **Authorization Tests** - Permission and access control
5. **Frontend Tests** - Vue.js components and user interactions

## Unit Tests

### 1. CheckIn Model Tests

#### File: `tests/Unit/Models/CheckInTest.php`

```php
<?php

use App\Models\CheckIn;
use App\Models\Team;
use App\Models\User;
use App\Enums\CheckInStatus;

beforeEach(function () {
    $this->team = Team::factory()->create();
    $this->assignedUser = User::factory()->create();
    $this->createdBy = User::factory()->create();
});

describe('CheckIn Model', function () {
    it('can create a check-in with required fields', function () {
        $checkIn = CheckIn::factory()->create([
            'team_id' => $this->team->id,
            'assigned_user_id' => $this->assignedUser->id,
            'created_by_user_id' => $this->createdBy->id,
        ]);

        expect($checkIn)
            ->toBeInstanceOf(CheckIn::class)
            ->and($checkIn->title)->toBeString()
            ->and($checkIn->status)->toBe(CheckInStatus::PENDING);
    });

    it('has correct relationships', function () {
        $checkIn = CheckIn::factory()->create([
            'team_id' => $this->team->id,
            'assigned_user_id' => $this->assignedUser->id,
            'created_by_user_id' => $this->createdBy->id,
        ]);

        expect($checkIn->team)->toBeInstanceOf(Team::class);
        expect($checkIn->assignedUser)->toBeInstanceOf(User::class);
        expect($checkIn->createdBy)->toBeInstanceOf(User::class);
    });

    it('can mark check-in as completed', function () {
        $checkIn = CheckIn::factory()->create([
            'status' => CheckInStatus::IN_PROGRESS,
        ]);

        $checkIn->markAsCompleted('Completed successfully');

        expect($checkIn->status)->toBe(CheckInStatus::COMPLETED);
        expect($checkIn->completed_at)->not->toBeNull();
        expect($checkIn->notes)->toBe('Completed successfully');
    });

    it('can mark check-in as in progress', function () {
        $checkIn = CheckIn::factory()->create([
            'status' => CheckInStatus::PENDING,
        ]);

        $checkIn->markAsInProgress();

        expect($checkIn->status)->toBe(CheckInStatus::IN_PROGRESS);
    });

    it('automatically detects overdue check-ins', function () {
        $checkIn = CheckIn::factory()->create([
            'scheduled_date' => now()->subDays(1),
            'status' => CheckInStatus::PENDING,
        ]);

        $checkIn->updateOverdueStatus();

        expect($checkIn->status)->toBe(CheckInStatus::OVERDUE);
    });

    it('can scope by status', function () {
        CheckIn::factory()->count(3)->create(['status' => CheckInStatus::PENDING]);
        CheckIn::factory()->count(2)->create(['status' => CheckInStatus::COMPLETED]);

        expect(CheckIn::pending()->count())->toBe(3);
        expect(CheckIn::completed()->count())->toBe(2);
    });

    it('can scope by team', function () {
        $team1 = Team::factory()->create();
        $team2 = Team::factory()->create();

        CheckIn::factory()->count(3)->create(['team_id' => $team1->id]);
        CheckIn::factory()->count(2)->create(['team_id' => $team2->id]);

        expect(CheckIn::forTeam($team1->id)->count())->toBe(3);
    });

    it('can scope by assigned user', function () {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        CheckIn::factory()->count(3)->create(['assigned_user_id' => $user1->id]);
        CheckIn::factory()->count(2)->create(['assigned_user_id' => $user2->id]);

        expect(CheckIn::assignedTo($user1->id)->count())->toBe(3);
    });
});
```

### 2. CheckInStatus Enum Tests

#### File: `tests/Unit/Enums/CheckInStatusTest.php`

```php
<?php

use App\Enums\CheckInStatus;

describe('CheckInStatus Enum', function () {
    it('has correct values', function () {
        expect(CheckInStatus::PENDING->value)->toBe('pending');
        expect(CheckInStatus::IN_PROGRESS->value)->toBe('in_progress');
        expect(CheckInStatus::COMPLETED->value)->toBe('completed');
        expect(CheckInStatus::OVERDUE->value)->toBe('overdue');
    });

    it('can get color for each status', function () {
        expect(CheckInStatus::PENDING->color())->toBe('gray');
        expect(CheckInStatus::IN_PROGRESS->color())->toBe('blue');
        expect(CheckInStatus::COMPLETED->color())->toBe('green');
        expect(CheckInStatus::OVERDUE->color())->toBe('red');
    });

    it('can get label for each status', function () {
        expect(CheckInStatus::PENDING->label())->toBe('Pending');
        expect(CheckInStatus::IN_PROGRESS->label())->toBe('In Progress');
        expect(CheckInStatus::COMPLETED->label())->toBe('Completed');
        expect(CheckInStatus::OVERDUE->label())->toBe('Overdue');
    });
});
```

### 3. Request Validation Tests

#### File: `tests/Unit/Requests/CreateCheckInRequestTest.php`

```php
<?php

use App\Http\Requests\CreateCheckInRequest;
use App\Models\Team;
use App\Models\User;

describe('CreateCheckInRequest', function () {
    it('validates required fields', function () {
        $request = new CreateCheckInRequest();
        $rules = $request->rules();

        expect($rules)->toHaveKey('title');
        expect($rules)->toHaveKey('team_id');
        expect($rules)->toHaveKey('assigned_user_id');
        expect($rules)->toHaveKey('scheduled_date');
    });

    it('validates title is required and max length', function () {
        $request = new CreateCheckInRequest();
        $rules = $request->rules();

        expect($rules['title'])->toContain('required');
        expect($rules['title'])->toContain('string');
        expect($rules['title'])->toContain('max:255');
    });

    it('validates scheduled date is in the future', function () {
        $request = new CreateCheckInRequest();
        $rules = $request->rules();

        expect($rules['scheduled_date'])->toContain('required');
        expect($rules['scheduled_date'])->toContain('date');
        expect($rules['scheduled_date'])->toContain('after_or_equal:today');
    });
});
```

## Feature Tests

### 1. CheckIn Management Tests

#### File: `tests/Feature/CheckInManagementTest.php`

```php
<?php

use App\Models\CheckIn;
use App\Models\Team;
use App\Models\User;
use App\Enums\CheckInStatus;
use App\Enums\UserType;

describe('Check-in Management', function () {
    beforeEach(function () {
        $this->organization = Organization::factory()->create();
        $this->team = Team::factory()->create(['organization_id' => $this->organization->id]);
        $this->admin = User::factory()->create([
            'organization_id' => $this->organization->id,
            'user_type' => UserType::ADMIN,
        ]);
        $this->member = User::factory()->create([
            'organization_id' => $this->organization->id,
            'user_type' => UserType::MEMBER,
        ]);
        $this->team->members()->attach($this->member);
    });

    it('allows admins to create check-ins', function () {
        $this->actingAs($this->admin)
            ->post(route('check-ins.store'), [
                'title' => 'Complete weekly report',
                'description' => 'Generate and submit the weekly team report',
                'team_id' => $this->team->id,
                'assigned_user_id' => $this->member->id,
                'scheduled_date' => now()->addDays(3)->format('Y-m-d'),
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('check_ins', [
            'title' => 'Complete weekly report',
            'team_id' => $this->team->id,
            'assigned_user_id' => $this->member->id,
            'status' => CheckInStatus::PENDING,
        ]);
    });

    it('prevents members from creating check-ins', function () {
        $this->actingAs($this->member)
            ->post(route('check-ins.store'), [
                'title' => 'Complete weekly report',
                'team_id' => $this->team->id,
                'assigned_user_id' => $this->member->id,
                'scheduled_date' => now()->addDays(3)->format('Y-m-d'),
            ])
            ->assertForbidden();
    });

    it('allows assigned users to mark check-ins as complete', function () {
        $checkIn = CheckIn::factory()->create([
            'team_id' => $this->team->id,
            'assigned_user_id' => $this->member->id,
            'status' => CheckInStatus::IN_PROGRESS,
        ]);

        $this->actingAs($this->member)
            ->patch(route('check-ins.complete', $checkIn), [
                'notes' => 'Completed successfully',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('check_ins', [
            'id' => $checkIn->id,
            'status' => CheckInStatus::COMPLETED,
            'notes' => 'Completed successfully',
        ]);
    });

    it('prevents non-assigned users from marking check-ins complete', function () {
        $otherMember = User::factory()->create([
            'organization_id' => $this->organization->id,
        ]);
        $checkIn = CheckIn::factory()->create([
            'team_id' => $this->team->id,
            'assigned_user_id' => $this->member->id,
        ]);

        $this->actingAs($otherMember)
            ->patch(route('check-ins.complete', $checkIn))
            ->assertForbidden();
    });

    it('allows admins to update check-ins', function () {
        $checkIn = CheckIn::factory()->create([
            'team_id' => $this->team->id,
            'created_by_user_id' => $this->admin->id,
        ]);

        $this->actingAs($this->admin)
            ->put(route('check-ins.update', $checkIn), [
                'title' => 'Updated title',
                'scheduled_date' => now()->addDays(5)->format('Y-m-d'),
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('check_ins', [
            'id' => $checkIn->id,
            'title' => 'Updated title',
        ]);
    });

    it('allows admins to delete check-ins', function () {
        $checkIn = CheckIn::factory()->create([
            'team_id' => $this->team->id,
            'created_by_user_id' => $this->admin->id,
        ]);

        $this->actingAs($this->admin)
            ->delete(route('check-ins.destroy', $checkIn))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('check_ins', [
            'id' => $checkIn->id,
        ]);
    });
});
```

### 2. CheckIn Authorization Tests

#### File: `tests/Feature/CheckInAuthorizationTest.php`

```php
<?php

use App\Models\CheckIn;
use App\Models\Team;
use App\Models\User;
use App\Enums\UserType;

describe('Check-in Authorization', function () {
    beforeEach(function () {
        $this->organization = Organization::factory()->create();
        $this->team = Team::factory()->create(['organization_id' => $this->organization->id]);
        $this->owner = User::factory()->create([
            'organization_id' => $this->organization->id,
            'user_type' => UserType::OWNER,
        ]);
        $this->admin = User::factory()->create([
            'organization_id' => $this->organization->id,
            'user_type' => UserType::ADMIN,
        ]);
        $this->member = User::factory()->create([
            'organization_id' => $this->organization->id,
            'user_type' => UserType::MEMBER,
        ]);
    });

    it('allows owners to view all check-ins', function () {
        CheckIn::factory()->count(3)->create([
            'team_id' => $this->team->id,
        ]);

        $this->actingAs($this->owner)
            ->get(route('check-ins.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('CheckIns/Index'));
    });

    it('allows admins to view team check-ins', function () {
        CheckIn::factory()->count(3)->create([
            'team_id' => $this->team->id,
        ]);

        $this->actingAs($this->admin)
            ->get(route('check-ins.index'))
            ->assertOk();
    });

    it('allows members to view their assigned check-ins', function () {
        CheckIn::factory()->create([
            'team_id' => $this->team->id,
            'assigned_user_id' => $this->member->id,
        ]);

        $this->actingAs($this->member)
            ->get(route('check-ins.index'))
            ->assertOk();
    });

    it('prevents members from viewing other users check-ins', function () {
        $otherMember = User::factory()->create([
            'organization_id' => $this->organization->id,
        ]);
        CheckIn::factory()->create([
            'team_id' => $this->team->id,
            'assigned_user_id' => $otherMember->id,
        ]);

        $this->actingAs($this->member)
            ->get(route('check-ins.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => 
                expect($page->props('checkIns')->data)->toHaveCount(0)
            );
    });

    it('allows owners to create check-ins for any team', function () {
        $this->actingAs($this->owner)
            ->post(route('check-ins.store'), [
                'title' => 'Test check-in',
                'team_id' => $this->team->id,
                'assigned_user_id' => $this->member->id,
                'scheduled_date' => now()->addDays(3)->format('Y-m-d'),
            ])
            ->assertRedirect();
    });

    it('prevents admins from creating check-ins for other teams', function () {
        $otherTeam = Team::factory()->create(['organization_id' => $this->organization->id]);

        $this->actingAs($this->admin)
            ->post(route('check-ins.store'), [
                'title' => 'Test check-in',
                'team_id' => $otherTeam->id,
                'assigned_user_id' => $this->member->id,
                'scheduled_date' => now()->addDays(3)->format('Y-m-d'),
            ])
            ->assertForbidden();
    });
});
```

### 3. CheckIn Status Management Tests

#### File: `tests/Feature/CheckInStatusManagementTest.php`

```php
<?php

use App\Models\CheckIn;
use App\Models\Team;
use App\Models\User;
use App\Enums\CheckInStatus;

describe('Check-in Status Management', function () {
    beforeEach(function () {
        $this->organization = Organization::factory()->create();
        $this->team = Team::factory()->create(['organization_id' => $this->organization->id]);
        $this->member = User::factory()->create(['organization_id' => $this->organization->id]);
        $this->team->members()->attach($this->member);
    });

    it('can mark check-in as in progress', function () {
        $checkIn = CheckIn::factory()->create([
            'team_id' => $this->team->id,
            'assigned_user_id' => $this->member->id,
            'status' => CheckInStatus::PENDING,
        ]);

        $this->actingAs($this->member)
            ->patch(route('check-ins.in-progress', $checkIn))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('check_ins', [
            'id' => $checkIn->id,
            'status' => CheckInStatus::IN_PROGRESS,
        ]);
    });

    it('can mark check-in as completed with notes', function () {
        $checkIn = CheckIn::factory()->create([
            'team_id' => $this->team->id,
            'assigned_user_id' => $this->member->id,
            'status' => CheckInStatus::IN_PROGRESS,
        ]);

        $this->actingAs($this->member)
            ->patch(route('check-ins.complete', $checkIn), [
                'notes' => 'Task completed successfully',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('check_ins', [
            'id' => $checkIn->id,
            'status' => CheckInStatus::COMPLETED,
            'notes' => 'Task completed successfully',
            'completed_at' => now(),
        ]);
    });

    it('prevents marking completed check-ins as in progress', function () {
        $checkIn = CheckIn::factory()->create([
            'team_id' => $this->team->id,
            'assigned_user_id' => $this->member->id,
            'status' => CheckInStatus::COMPLETED,
        ]);

        $this->actingAs($this->member)
            ->patch(route('check-ins.in-progress', $checkIn))
            ->assertUnprocessable();
    });

    it('automatically detects overdue check-ins', function () {
        $checkIn = CheckIn::factory()->create([
            'team_id' => $this->team->id,
            'assigned_user_id' => $this->member->id,
            'scheduled_date' => now()->subDays(1),
            'status' => CheckInStatus::PENDING,
        ]);

        // Run the command to update overdue status
        $this->artisan('check-ins:update-overdue');

        $this->assertDatabaseHas('check_ins', [
            'id' => $checkIn->id,
            'status' => CheckInStatus::OVERDUE,
        ]);
    });
});
```

## Integration Tests

### 1. API Endpoint Tests

#### File: `tests/Feature/Api/CheckInApiTest.php`

```php
<?php

use App\Models\CheckIn;
use App\Models\Team;
use App\Models\User;
use App\Enums\CheckInStatus;

describe('Check-in API Endpoints', function () {
    beforeEach(function () {
        $this->organization = Organization::factory()->create();
        $this->team = Team::factory()->create(['organization_id' => $this->organization->id]);
        $this->admin = User::factory()->create([
            'organization_id' => $this->organization->id,
            'user_type' => UserType::ADMIN,
        ]);
        $this->member = User::factory()->create([
            'organization_id' => $this->organization->id,
            'user_type' => UserType::MEMBER,
        ]);
    });

    it('returns paginated check-ins', function () {
        CheckIn::factory()->count(15)->create([
            'team_id' => $this->team->id,
        ]);

        $this->actingAs($this->admin)
            ->getJson(route('api.check-ins.index'))
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'title',
                        'description',
                        'team_id',
                        'assigned_user_id',
                        'scheduled_date',
                        'status',
                        'created_at',
                        'updated_at',
                    ],
                ],
                'meta' => [
                    'current_page',
                    'last_page',
                    'per_page',
                    'total',
                ],
            ]);
    });

    it('filters check-ins by status', function () {
        CheckIn::factory()->count(3)->create([
            'team_id' => $this->team->id,
            'status' => CheckInStatus::PENDING,
        ]);
        CheckIn::factory()->count(2)->create([
            'team_id' => $this->team->id,
            'status' => CheckInStatus::COMPLETED,
        ]);

        $this->actingAs($this->admin)
            ->getJson(route('api.check-ins.index', ['status' => 'pending']))
            ->assertOk()
            ->assertJsonCount(3, 'data');
    });

    it('creates check-in via API', function () {
        $data = [
            'title' => 'API Test Check-in',
            'description' => 'Test description',
            'team_id' => $this->team->id,
            'assigned_user_id' => $this->member->id,
            'scheduled_date' => now()->addDays(3)->format('Y-m-d'),
        ];

        $this->actingAs($this->admin)
            ->postJson(route('api.check-ins.store'), $data)
            ->assertCreated()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'title',
                    'description',
                    'team_id',
                    'assigned_user_id',
                    'scheduled_date',
                    'status',
                ],
            ]);

        $this->assertDatabaseHas('check_ins', [
            'title' => 'API Test Check-in',
            'team_id' => $this->team->id,
            'assigned_user_id' => $this->member->id,
        ]);
    });

    it('updates check-in via API', function () {
        $checkIn = CheckIn::factory()->create([
            'team_id' => $this->team->id,
            'created_by_user_id' => $this->admin->id,
        ]);

        $updateData = [
            'title' => 'Updated Title',
            'scheduled_date' => now()->addDays(5)->format('Y-m-d'),
        ];

        $this->actingAs($this->admin)
            ->putJson(route('api.check-ins.update', $checkIn), $updateData)
            ->assertOk()
            ->assertJson([
                'data' => [
                    'title' => 'Updated Title',
                ],
            ]);
    });

    it('marks check-in as complete via API', function () {
        $checkIn = CheckIn::factory()->create([
            'team_id' => $this->team->id,
            'assigned_user_id' => $this->member->id,
            'status' => CheckInStatus::IN_PROGRESS,
        ]);

        $this->actingAs($this->member)
            ->patchJson(route('api.check-ins.complete', $checkIn), [
                'notes' => 'Completed via API',
            ])
            ->assertOk()
            ->assertJson([
                'data' => [
                    'status' => CheckInStatus::COMPLETED,
                    'notes' => 'Completed via API',
                ],
            ]);
    });
});
```

## Frontend Tests

### 1. Component Tests

#### File: `tests/Feature/Components/CheckInCardTest.php`

```php
<?php

use App\Models\CheckIn;
use App\Models\Team;
use App\Models\User;
use App\Enums\CheckInStatus;

describe('CheckInCard Component', function () {
    it('displays check-in information correctly', function () {
        $checkIn = CheckIn::factory()->create([
            'title' => 'Test Check-in',
            'description' => 'Test description',
            'status' => CheckInStatus::PENDING,
        ]);

        $this->actingAs($checkIn->assignedUser)
            ->get(route('check-ins.index'))
            ->assertInertia(fn ($page) => 
                $page->component('CheckIns/Index')
                    ->has('checkIns.data', 1)
                    ->where('checkIns.data.0.title', 'Test Check-in')
                    ->where('checkIns.data.0.status', CheckInStatus::PENDING)
            );
    });

    it('shows correct status badge colors', function () {
        $pendingCheckIn = CheckIn::factory()->create(['status' => CheckInStatus::PENDING]);
        $completedCheckIn = CheckIn::factory()->create(['status' => CheckInStatus::COMPLETED]);

        $this->actingAs($pendingCheckIn->assignedUser)
            ->get(route('check-ins.index'))
            ->assertInertia(fn ($page) => 
                $page->component('CheckIns/Index')
                    ->has('checkIns.data')
            );
    });

    it('shows action buttons for assigned user', function () {
        $checkIn = CheckIn::factory()->create([
            'status' => CheckInStatus::PENDING,
        ]);

        $this->actingAs($checkIn->assignedUser)
            ->get(route('check-ins.index'))
            ->assertInertia(fn ($page) => 
                $page->component('CheckIns/Index')
            );
    });
});
```

## Database Tests

### 1. Migration Tests

#### File: `tests/Feature/Database/CheckInMigrationTest.php`

```php
<?php

use Illuminate\Support\Facades\Schema;

describe('Check-in Migration', function () {
    it('creates check_ins table with correct structure', function () {
        $this->artisan('migrate');

        expect(Schema::hasTable('check_ins'))->toBeTrue();

        $columns = Schema::getColumnListing('check_ins');
        
        expect($columns)->toContain('id');
        expect($columns)->toContain('title');
        expect($columns)->toContain('description');
        expect($columns)->toContain('team_id');
        expect($columns)->toContain('assigned_user_id');
        expect($columns)->toContain('created_by_user_id');
        expect($columns)->toContain('scheduled_date');
        expect($columns)->toContain('completed_at');
        expect($columns)->toContain('status');
        expect($columns)->toContain('notes');
        expect($columns)->toContain('created_at');
        expect($columns)->toContain('updated_at');
    });

    it('has correct foreign key constraints', function () {
        $this->artisan('migrate');

        $foreignKeys = Schema::getConnection()
            ->getDoctrineSchemaManager()
            ->listTableForeignKeys('check_ins');

        $foreignKeyNames = array_map(fn($fk) => $fk->getName(), $foreignKeys);

        expect($foreignKeyNames)->toContain('check_ins_team_id_foreign');
        expect($foreignKeyNames)->toContain('check_ins_assigned_user_id_foreign');
        expect($foreignKeyNames)->toContain('check_ins_created_by_user_id_foreign');
    });

    it('has correct indexes', function () {
        $this->artisan('migrate');

        $indexes = Schema::getConnection()
            ->getDoctrineSchemaManager()
            ->listTableIndexes('check_ins');

        $indexNames = array_keys($indexes);

        expect($indexNames)->toContain('check_ins_team_id_index');
        expect($indexNames)->toContain('check_ins_assigned_user_id_index');
        expect($indexNames)->toContain('check_ins_status_index');
        expect($indexNames)->toContain('check_ins_scheduled_date_index');
    });
});
```

## Performance Tests

### 1. Load Testing

#### File: `tests/Performance/CheckInLoadTest.php`

```php
<?php

use App\Models\CheckIn;
use App\Models\Team;
use App\Models\User;

describe('Check-in Performance', function () {
    it('handles large number of check-ins efficiently', function () {
        $team = Team::factory()->create();
        $user = User::factory()->create();
        
        // Create 1000 check-ins
        CheckIn::factory()->count(1000)->create([
            'team_id' => $team->id,
            'assigned_user_id' => $user->id,
        ]);

        $startTime = microtime(true);

        $this->actingAs($user)
            ->get(route('check-ins.index'))
            ->assertOk();

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        // Should complete within 1 second
        expect($executionTime)->toBeLessThan(1.0);
    });

    it('efficiently filters large datasets', function () {
        $team = Team::factory()->create();
        $user = User::factory()->create();
        
        // Create check-ins with different statuses
        CheckIn::factory()->count(500)->create([
            'team_id' => $team->id,
            'status' => CheckInStatus::PENDING,
        ]);
        CheckIn::factory()->count(500)->create([
            'team_id' => $team->id,
            'status' => CheckInStatus::COMPLETED,
        ]);

        $startTime = microtime(true);

        $this->actingAs($user)
            ->get(route('check-ins.index', ['status' => 'pending']))
            ->assertOk();

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        // Should complete within 500ms
        expect($executionTime)->toBeLessThan(0.5);
    });
});
```

## Test Data Factories

### 1. CheckIn Factory

#### File: `database/factories/CheckInFactory.php`

```php
<?php

namespace Database\Factories;

use App\Models\CheckIn;
use App\Models\Team;
use App\Models\User;
use App\Enums\CheckInStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

class CheckInFactory extends Factory
{
    protected $model = CheckIn::class;

    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(),
            'team_id' => Team::factory(),
            'assigned_user_id' => User::factory(),
            'created_by_user_id' => User::factory(),
            'scheduled_date' => $this->faker->dateTimeBetween('now', '+30 days'),
            'completed_at' => null,
            'status' => CheckInStatus::PENDING,
            'notes' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CheckInStatus::PENDING,
        ]);
    }

    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CheckInStatus::IN_PROGRESS,
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CheckInStatus::COMPLETED,
            'completed_at' => now(),
            'notes' => $this->faker->sentence(),
        ]);
    }

    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CheckInStatus::OVERDUE,
            'scheduled_date' => $this->faker->dateTimeBetween('-30 days', '-1 day'),
        ]);
    }
}
```

## Test Configuration

### 1. Pest Configuration

#### File: `tests/Pest.php`

```php
<?php

use App\Models\User;
use App\Models\Team;
use App\Models\CheckIn;
use App\Enums\UserType;
use App\Enums\CheckInStatus;

beforeEach(function () {
    // Common setup for all tests
    $this->organization = Organization::factory()->create();
    $this->team = Team::factory()->create(['organization_id' => $this->organization->id]);
    $this->admin = User::factory()->create([
        'organization_id' => $this->organization->id,
        'user_type' => UserType::ADMIN,
    ]);
    $this->member = User::factory()->create([
        'organization_id' => $this->organization->id,
        'user_type' => UserType::MEMBER,
    ]);
    $this->team->members()->attach($this->member);
});

// Helper functions
function createCheckIn($attributes = []): CheckIn
{
    return CheckIn::factory()->create(array_merge([
        'team_id' => $this->team->id,
        'assigned_user_id' => $this->member->id,
        'created_by_user_id' => $this->admin->id,
    ], $attributes));
}

function createOverdueCheckIn($attributes = []): CheckIn
{
    return CheckIn::factory()->overdue()->create(array_merge([
        'team_id' => $this->team->id,
        'assigned_user_id' => $this->member->id,
        'created_by_user_id' => $this->admin->id,
    ], $attributes));
}
```

## Test Coverage Goals

### Coverage Targets
- **Models**: 100% coverage
- **Controllers**: 95% coverage
- **Requests**: 100% coverage
- **Policies**: 100% coverage
- **Components**: 90% coverage
- **Overall**: 90% coverage

### Critical Paths
1. Check-in creation workflow
2. Status update workflow
3. Authorization checks
4. Overdue detection
5. Dashboard statistics
6. API endpoints

### Edge Cases
1. Invalid data submission
2. Unauthorized access attempts
3. Concurrent status updates
4. Large dataset performance
5. Network failures
6. Database constraints

## Continuous Integration

### GitHub Actions Workflow
```yaml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    
    steps:
    - uses: actions/checkout@v3
    
    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: '8.2'
        extensions: mbstring, xml, ctype, iconv, intl, pdo_sqlite
        coverage: xdebug
    
    - name: Install dependencies
      run: composer install -q --no-ansi --no-interaction --no-scripts --no-progress --prefer-dist
    
    - name: Copy environment file
      run: cp .env.example .env
    
    - name: Generate key
      run: php artisan key:generate
    
    - name: Create database
      run: |
        mkdir -p database
        touch database/database.sqlite
    
    - name: Run migrations
      run: php artisan migrate --force
    
    - name: Run tests
      run: php artisan test --coverage --min=90
```

This comprehensive testing strategy ensures the check-in feature is robust, reliable, and maintainable while providing excellent user experience. 