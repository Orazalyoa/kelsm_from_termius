<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrganizationRequest extends FormRequest
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
        $organizationId = $this->route('id');
        
        return [
            'name' => 'sometimes|required|string|max:255',
            'company_id' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('organizations', 'company_id')->ignore($organizationId)
            ],
            'description' => 'nullable|string|max:1000',
            'contact_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'status' => 'sometimes|in:active,inactive'
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
            'name.required' => __('request_validation.update_organization.name_required'),
            'name.max' => __('request_validation.update_organization.name_max'),
            'company_id.required' => __('request_validation.update_organization.company_id_required'),
            'company_id.unique' => __('request_validation.update_organization.company_id_unique'),
            'company_id.max' => __('request_validation.update_organization.company_id_max'),
            'description.max' => __('request_validation.update_organization.description_max'),
            'contact_name.max' => __('request_validation.update_organization.contact_name_max'),
            'phone.max' => __('request_validation.update_organization.phone_max'),
            'email.email' => __('request_validation.update_organization.email_email'),
            'email.max' => __('request_validation.update_organization.email_max'),
            'logo.image' => __('request_validation.update_organization.logo_image'),
            'logo.mimes' => __('request_validation.update_organization.logo_mimes'),
            'logo.max' => __('request_validation.update_organization.logo_max'),
            'status.in' => __('request_validation.update_organization.status_in')
        ];
    }
}
