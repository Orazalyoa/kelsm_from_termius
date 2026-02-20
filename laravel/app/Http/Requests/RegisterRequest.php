<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
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
            'user_type' => 'required|in:company_admin,expert',
            'email' => 'required_without:phone|email|unique:users,email',
            'phone' => 'required_without:email|unique:users,phone',
            'country_code' => 'required_with:phone|string|max:10',
            'password' => ['required', 'min:8', 'confirmed', 'regex:/^(?=.*[A-Za-z])(?=.*\d).+$/'],
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'gender' => 'nullable|in:male,female,other',
            'locale' => 'nullable|in:ru,kk,en,zh-CN',
            'invite_code' => 'required_if:user_type,expert|string',
            'profession_ids' => 'nullable|array',
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
            'user_type.required' => __('request_validation.register.user_type_required'),
            'user_type.in' => __('request_validation.register.user_type_in'),
            'email.required_without' => __('request_validation.register.email_required_without'),
            'email.unique' => __('request_validation.register.email_unique'),
            'phone.required_without' => __('request_validation.register.phone_required_without'),
            'phone.unique' => __('request_validation.register.phone_unique'),
            'password.min' => __('request_validation.register.password_min'),
            'password.confirmed' => __('request_validation.register.password_confirmed'),
            'password.regex' => __('request_validation.register.password_weak'),
            'country_code.required_with' => __('request_validation.register.country_code_required_with'),
            'invite_code.required_if' => __('request_validation.register.invite_code_required_if'),
            'profession_ids.array' => __('request_validation.register.profession_ids_array'),
            'profession_ids.*.exists' => __('request_validation.register.profession_ids_exists')
        ];
    }
}
