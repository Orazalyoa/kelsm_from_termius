<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Consultation;

class StoreConsultationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $user = $this->user();
        
        // Only non-lawyers can create consultations
        if ($user->isLawyer()) {
            return false;
        }
        
        // Check if user has permission to create consultations
        // Company admin and owner have full permissions
        return $user->canCreateConsultations();
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
            'title' => 'nullable|string|max:255', // 改为可选，会自动生成
            'description' => 'required|string',
            'topic_type' => "required|string|in:{$topics}", // 改为必填
            'priority' => "nullable|string|in:{$priorities}",
            'files' => 'nullable|array',
            'files.*' => 'file|max:' . (config('consultation.max_file_size') / 1024), // Convert to KB
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages()
    {
        $maxFileSizeMB = config('consultation.max_file_size') / 1024 / 1024;
        
        return [
            'description.required' => __('request_validation.store_consultation.description_required'),
            'topic_type.required' => __('request_validation.store_consultation.topic_type_required'),
            'topic_type.in' => __('request_validation.store_consultation.topic_type_in'),
            'priority.in' => __('request_validation.store_consultation.priority_in'),
            'files.*.file' => __('request_validation.store_consultation.files_file'),
            'files.*.max' => __('request_validation.store_consultation.files_max', ['max' => $maxFileSizeMB]),
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Check if user is a lawyer
            if ($this->user()->isLawyer()) {
                $validator->errors()->add('user_type', __('request_validation.store_consultation.user_type_lawyers_cannot_create'));
            }
        });
    }
}

