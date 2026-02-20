<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
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
        $userId = auth('api')->id();
        
        return [
            'email' => 'sometimes|email|unique:users,email,' . $userId,
            'phone' => 'sometimes|unique:users,phone,' . $userId,
            'country_code' => 'required_with:phone|string|max:10',
            'first_name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'gender' => 'sometimes|in:male,female,other',
            'locale' => 'sometimes|in:ru,kk,en,zh-CN',
            'profession_ids' => 'sometimes|array',
            'profession_ids.*' => 'exists:professions,id'
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
            'email.unique' => __('request_validation.update_profile.email_unique'),
            'phone.unique' => __('request_validation.update_profile.phone_unique'),
            'country_code.required_with' => __('request_validation.update_profile.country_code_required_with'),
            'profession_ids.array' => __('request_validation.update_profile.profession_ids_array'),
            'profession_ids.*.exists' => __('request_validation.update_profile.profession_ids_exists')
        ];
    }
}
