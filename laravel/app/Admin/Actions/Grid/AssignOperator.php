<?php

namespace App\Admin\Actions\Grid;

use App\Admin\Forms\AssignOperatorForm;
use Dcat\Admin\Grid\RowAction;
use Dcat\Admin\Widgets\Modal;

class AssignOperator extends RowAction
{
    protected $title = '管理客服';

    public function render()
    {
        if (in_array($this->row->status, ['archived', 'cancelled'])) {
            return '';
        }

        $form = AssignOperatorForm::make()->payload(['consultation_id' => $this->getKey()]);
        $buttonText = __('consultation.actions.manage_operators');

        return Modal::make()
            ->lg()
            ->title('<i class="fa fa-headphones"></i> ' . $buttonText)
            ->body($form)
            ->button('<i class="fa fa-headphones"></i> ' . $buttonText);
    }
}


