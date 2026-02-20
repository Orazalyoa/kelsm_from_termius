<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateOrganizationRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'company_id' => 'nullable|string|max:255|unique:organizations,company_id',
            'description' => 'nullable|string|max:1000',
            'contact_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
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
            'name.required' => __('request_validation.create_organization.name_required'),
            'name.max' => __('request_validation.create_organization.name_max'),
            'company_id.unique' => __('request_validation.create_organization.company_id_unique'),
            'company_id.max' => __('request_validation.create_organization.company_id_max'),
            'description.max' => __('request_validation.create_organization.description_max'),
            'contact_name.max' => __('request_validation.create_organization.contact_name_max'),
            'phone.max' => __('request_validation.create_organization.phone_max'),
            'email.email' => __('request_validation.create_organization.email_email'),
            'email.max' => __('request_validation.create_organization.email_max'),
            'logo.image' => __('request_validation.create_organization.logo_image'),
            'logo.mimes' => __('request_validation.create_organization.logo_mimes'),
            'logo.max' => __('request_validation.create_organization.logo_max')
        ];
    }
}
