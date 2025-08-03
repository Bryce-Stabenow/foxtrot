<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Enums\OrganizationInvitationStatus;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OrganizationInvitation>
 */
class OrganizationInvitationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = OrganizationInvitation::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'email' => fake()->unique()->safeEmail(),
            'organization_id' => Organization::factory(),
            'invited_by_user_id' => User::factory(),
            'token' => OrganizationInvitation::generateToken(),
            'status' => OrganizationInvitationStatus::PENDING,
            'expires_at' => fake()->dateTimeBetween('now', '+7 days'),
            'accepted_at' => null,
        ];
    }

    /**
     * Indicate that the invitation is accepted.
     */
    public function accepted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrganizationInvitationStatus::ACCEPTED,
            'accepted_at' => fake()->dateTimeBetween('-7 days', 'now'),
        ]);
    }

    /**
     * Indicate that the invitation is expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrganizationInvitationStatus::EXPIRED,
            'expires_at' => fake()->dateTimeBetween('-14 days', '-7 days'),
        ]);
    }

    /**
     * Indicate that the invitation is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrganizationInvitationStatus::PENDING,
            'expires_at' => fake()->dateTimeBetween('now', '+7 days'),
            'accepted_at' => null,
        ]);
    }
}
