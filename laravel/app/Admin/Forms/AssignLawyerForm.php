<?php

namespace App\Admin\Forms;

use App\Models\Consultation;
use App\Models\User;
use App\Services\ConsultationService;
use Dcat\Admin\Contracts\LazyRenderable;
use Dcat\Admin\Traits\LazyWidget;
use Dcat\Admin\Widgets\Form;

class AssignLawyerForm extends Form implements LazyRenderable
{
    use LazyWidget;

    /**
     * 处理表单提交
     */
    public function handle(array $input)
    {
        $consultationId = $this->payload['consultation_id'] ?? null;
        $lawyerIds = $input['lawyer_ids'] ?? [];

        if (empty($lawyerIds)) {
            return $this->response()->error(__('consultation.messages.please_select_lawyers'));
        }

        try {
            $consultation = Consultation::findOrFail($consultationId);
            
            // 使用服务分配多个律师
            $consultationService = app(ConsultationService::class);
            $admin = auth('admin')->user();
            $adminUser = User::where('email', $admin->email)->first() ?? User::first();
            
            $consultationService->assignLawyers($consultation, $lawyerIds, $adminUser, true);
            
            return $this->response()
                ->success(__('consultation.messages.assign_success'))
                ->refresh();
                
        } catch (\Exception $e) {
            return $this->response()->error($e->getMessage());
        }
    }

    /**
     * 构建表单
     */
    public function form()
    {
        $consultationId = $this->payload['consultation_id'] ?? null;
        $consultation = Consultation::find($consultationId);
        
        // 获取所有律师
        $lawyers = User::lawyers()
            ->get()
            ->pluck('full_name', 'id')
            ->toArray();

        // 获取已分配的律师ID
        $assignedLawyerIds = [];
        if ($consultation) {
            $assignedLawyerIds = $consultation->lawyers()->pluck('lawyer_id')->toArray();
        }

        $this->multipleSelect('lawyer_ids', __('consultation.assigned_lawyers'))
            ->options($lawyers)
            ->default($assignedLawyerIds)
            ->required()
            ->help(__('consultation.help.select_multiple_lawyers'));
    }
}

