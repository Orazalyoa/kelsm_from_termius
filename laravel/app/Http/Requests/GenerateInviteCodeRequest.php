<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GenerateInviteCodeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'organization_id' => 'required|exists:organizations,id',
            'user_type' => 'nullable|in:expert,company_admin',
            'permissions' => 'nullable|array',
            'permissions.can_apply_consultation' => 'nullable|boolean',
            'max_uses' => 'nullable|integer|min:1|max:100',
            'expires_at' => 'nullable|date|after:now'
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'organization_id.required' => __('request_validation.generate_invite_code.organization_id_required'),
            'organization_id.exists' => __('request_validation.generate_invite_code.organization_id_exists'),
            'user_type.in' => __('request_validation.generate_invite_code.user_type_in'),
            'permissions.array' => __('request_validation.generate_invite_code.permissions_array'),
            'permissions.can_apply_consultation.boolean' => __('request_validation.generate_invite_code.permissions_can_apply_consultation_boolean'),
            'max_uses.integer' => __('request_validation.generate_invite_code.max_uses_integer'),
            'max_uses.min' => __('request_validation.generate_invite_code.max_uses_min'),
            'max_uses.max' => __('request_validation.generate_invite_code.max_uses_max'),
            'expires_at.date' => __('request_validation.generate_invite_code.expires_at_date'),
            'expires_at.after' => __('request_validation.generate_invite_code.expires_at_after')
        ];
    }
}
