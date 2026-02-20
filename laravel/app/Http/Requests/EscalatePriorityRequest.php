<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Consultation;

class EscalatePriorityRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true; // Authorization handled in service layer
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'priority' => [
                'required',
                'in:' . implode(',', [
                    Consultation::PRIORITY_MEDIUM,
                    Consultation::PRIORITY_HIGH,
                    Consultation::PRIORITY_URGENT,
                ]),
            ],
            'reason' => 'required|string|min:5|max:500',
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
            'priority.required' => '优先级为必填项',
            'priority.in' => '无效的优先级',
            'reason.required' => '提升原因为必填项',
            'reason.min' => '提升原因至少5个字符',
            'reason.max' => '提升原因不能超过500个字符',
        ];
    }
}

