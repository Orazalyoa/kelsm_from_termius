<?php

namespace App\Admin\Actions\Grid;

use App\Admin\Forms\AssignLawyerForm;
use Dcat\Admin\Grid\RowAction;
use Dcat\Admin\Widgets\Modal;

class AssignLawyer extends RowAction
{
    /**
     * @return string
     */
    protected $title = '管理律师';

    /**
     * 渲染按钮
     */
    public function render()
    {
        // 在已归档和已取消状态下不显示
        if (in_array($this->row->status, ['archived', 'cancelled'])) {
            return '';
        }
        
        // 创建工具表单，并传入当前咨询ID
        $form = AssignLawyerForm::make()->payload(['consultation_id' => $this->getKey()]);

        // 根据状态显示不同的按钮文本
        $buttonText = $this->row->status === 'pending' 
            ? __('consultation.actions.assign_lawyer')
            : __('consultation.actions.manage_lawyers');

        return Modal::make()
            ->lg()
            ->title('<i class="fa fa-users"></i> ' . $buttonText)
            ->body($form)
            ->button('<i class="fa fa-users"></i> ' . $buttonText);
    }
}


