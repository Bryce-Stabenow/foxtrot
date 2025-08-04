<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCheckInRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('checkIn'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'team_id' => [
                'sometimes',
                'required',
                'exists:teams,id',
                Rule::exists('teams', 'id')->where(function ($query) {
                    $query->where('organization_id', $this->user()->organization_id);
                }),
            ],
            'assigned_user_id' => [
                'sometimes',
                'required',
                'exists:users,id',
                Rule::exists('users', 'id')->where(function ($query) {
                    $query->where('organization_id', $this->user()->organization_id);
                }),
            ],
            'scheduled_date' => ['sometimes', 'required', 'date', 'after_or_equal:today'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'team_id.exists' => 'The selected team is invalid.',
            'assigned_user_id.exists' => 'The selected user is invalid.',
            'scheduled_date.after_or_equal' => 'The scheduled date must be today or in the future.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array
     */
    public function attributes(): array
    {
        return [
            'title' => 'title',
            'description' => 'description',
            'team_id' => 'team',
            'assigned_user_id' => 'assigned user',
            'scheduled_date' => 'scheduled date',
        ];
    }
}
