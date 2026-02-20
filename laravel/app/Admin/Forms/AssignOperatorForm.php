<?php

namespace App\Admin\Forms;

use App\Models\Consultation;
use App\Models\User;
use App\Services\ConsultationService;
use Dcat\Admin\Contracts\LazyRenderable;
use Dcat\Admin\Traits\LazyWidget;
use Dcat\Admin\Widgets\Form;

class AssignOperatorForm extends Form implements LazyRenderable
{
    use LazyWidget;

    public function handle(array $input)
    {
        $consultationId = $this->payload['consultation_id'] ?? null;
        $operatorIds = $input['operator_ids'] ?? [];

        if (empty($operatorIds)) {
            return $this->response()->error(__('consultation.messages.please_select_operators'));
        }

        try {
            $consultation = Consultation::findOrFail($consultationId);
            $consultationService = app(ConsultationService::class);
            $admin = auth('admin')->user();
            $adminUser = User::where('email', $admin->email)->first() ?? User::first();

            $consultationService->assignOperators($consultation, $operatorIds, $adminUser);

            return $this->response()
                ->success(__('consultation.messages.assign_operator_success'))
                ->refresh();
        } catch (\Exception $e) {
            return $this->response()->error($e->getMessage());
        }
    }

    public function form()
    {
        $consultationId = $this->payload['consultation_id'] ?? null;
        $consultation = Consultation::find($consultationId);

        $operators = User::operators()
            ->get()
            ->pluck('full_name', 'id')
            ->toArray();

        $assignedOperatorIds = [];
        if ($consultation) {
            $assignedOperatorIds = $consultation->operators()->pluck('operator_id')->toArray();
        }

        $this->multipleSelect('operator_ids', __('consultation.assigned_operators'))
            ->options($operators)
            ->default($assignedOperatorIds)
            ->required()
            ->help(__('consultation.help.select_multiple_operators'));
    }
}


