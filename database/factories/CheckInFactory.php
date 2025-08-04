<?php

namespace Database\Factories;

use App\Enums\CheckInStatus;
use App\Models\CheckIn;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CheckIn>
 */
class CheckInFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = CheckIn::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $team = Team::factory()->create();
        $assignedUser = User::factory()->create(['organization_id' => $team->organization_id]);
        $createdByUser = User::factory()->create(['organization_id' => $team->organization_id]);

        return [
            'title' => $this->faker->sentence(3, 6),
            'description' => $this->faker->paragraph(),
            'team_id' => $team->id,
            'assigned_user_id' => $assignedUser->id,
            'created_by_user_id' => $createdByUser->id,
            'scheduled_date' => $this->faker->dateTimeBetween('now', '+30 days'),
            'completed_at' => null,
            'status' => CheckInStatus::PENDING,
            'notes' => null,
        ];
    }

    /**
     * Indicate that the check-in is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CheckInStatus::COMPLETED,
            'completed_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'notes' => $this->faker->paragraph(),
        ]);
    }

    /**
     * Indicate that the check-in is in progress.
     */
    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CheckInStatus::IN_PROGRESS,
        ]);
    }

    /**
     * Indicate that the check-in is overdue.
     */
    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CheckInStatus::OVERDUE,
            'scheduled_date' => $this->faker->dateTimeBetween('-30 days', '-1 day'),
        ]);
    }

    /**
     * Indicate that the check-in is due soon.
     */
    public function dueSoon(): static
    {
        return $this->state(fn (array $attributes) => [
            'scheduled_date' => $this->faker->dateTimeBetween('now', '+7 days'),
        ]);
    }

    /**
     * Indicate that the check-in is due today.
     */
    public function dueToday(): static
    {
        return $this->state(fn (array $attributes) => [
            'scheduled_date' => now(),
        ]);
    }
} 