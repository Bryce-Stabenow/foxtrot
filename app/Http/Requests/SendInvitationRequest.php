<?php

namespace App\Http\Requests;

use App\Models\Organization;
use App\Models\User;
use App\Enums\UserType;
use App\Enums\OrganizationInvitationStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SendInvitationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        
        // Only admins and owners can send invitations
        if (!hasAdminPermissions($user)) {
            return false;
        }

        // User must belong to an organization
        if (!$user->organization_id) {
            return false;
        }

        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email'),
                Rule::unique('organization_invitations', 'email')
                    ->where('organization_id', $this->user()->organization_id)
                    ->where('status', OrganizationInvitationStatus::PENDING),
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.required' => 'Please provide an email address.',
            'email.email' => 'Please provide a valid email address.',
            'email.unique' => 'This email is already a member of your organization or has a pending invitation.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'email' => 'email address',
        ];
    }
}
