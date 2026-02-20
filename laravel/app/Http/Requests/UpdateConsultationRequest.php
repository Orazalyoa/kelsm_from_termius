<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Consultation;

class UpdateConsultationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $consultation = $this->route('consultation');
        
        // User must be the creator and consultation must be pending
        return $consultation->created_by === $this->user()->id 
            && $consultation->status === Consultation::STATUS_PENDING;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $topics = implode(',', array_keys(Consultation::getTopics()));
        $priorities = implode(',', array_keys(Consultation::getPriorities()));

        return [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'topic_type' => "sometimes|string|in:{$topics}",
            'priority' => "sometimes|string|in:{$priorities}",
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
            'title.required' => __('request_validation.update_consultation.title_required'),
            'description.required' => __('request_validation.update_consultation.description_required'),
            'topic_type.in' => __('request_validation.update_consultation.topic_type_in'),
            'priority.in' => __('request_validation.update_consultation.priority_in'),
        ];
    }
}

