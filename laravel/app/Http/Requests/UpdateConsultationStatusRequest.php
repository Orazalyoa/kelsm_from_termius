<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Consultation;

class UpdateConsultationStatusRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $consultation = $this->route('consultation');
        
        // User must be creator, assigned lawyer, or admin
        return $consultation->canBeAccessedBy($this->user());
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $statuses = implode(',', array_keys(Consultation::getStatuses()));

        return [
            'status' => "required|string|in:{$statuses}",
            'reason' => 'nullable|string|max:500',
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
            'status.required' => __('request_validation.update_consultation_status.status_required'),
            'status.in' => __('request_validation.update_consultation_status.status_in'),
            'reason.max' => __('request_validation.update_consultation_status.reason_max'),
        ];
    }
}

