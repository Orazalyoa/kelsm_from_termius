<?php

namespace App\Admin\Controllers;

use App\Admin\Repositories\Profession;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;

class ProfessionController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new Profession(), function (Grid $grid) {
            $grid->column('id')->sortable();
            $grid->column('key')->label('primary');
            $grid->column('name_ru', __('profession.labels.russian'));
            $grid->column('name_kk', __('profession.labels.kazakh'));
            $grid->column('is_for_expert')->bool();
            $grid->column('is_for_lawyer')->bool();
            $grid->column('status')->using(__('profession.options.status'))->label([
                'active' => 'success',
                'inactive' => 'default'
            ]);
            $grid->column('created_at')->display(function ($time) {
                return $time ? date('Y-m-d H:i:s', strtotime($time)) : '-';
            })->sortable();

            // 操作按钮显示为直接按钮而不是下拉菜单
            $grid->setActionClass(\Dcat\Admin\Grid\Displayers\Actions::class);

            // 过滤器
            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('status')->select(__('profession.options.status'));
                $filter->equal('is_for_expert')->select(__('profession.options.yes_no'));
                $filter->equal('is_for_lawyer')->select(__('profession.options.yes_no'));
                $filter->like('name_ru');
                $filter->like('key');
            });

            // 批量操作
            $grid->batchActions(function ($batch) {
                $batch->add(new class(__('profession.actions.activate')) extends \Dcat\Admin\Grid\BatchAction {
                    public function handle()
                    {
                        \App\Models\Profession::whereIn('id', $this->getKey())->update(['status' => 'active']);
                        return $this->response()->success(__('profession.actions.activated_success'))->refresh();
                    }
                });
                
                $batch->add(new class(__('profession.actions.deactivate')) extends \Dcat\Admin\Grid\BatchAction {
                    public function handle()
                    {
                        \App\Models\Profession::whereIn('id', $this->getKey())->update(['status' => 'inactive']);
                        return $this->response()->success(__('profession.actions.deactivated_success'))->refresh();
                    }
                });
            });

            // 导出功能
            $grid->export();

            // 快速搜索
            $grid->quickSearch(['name_ru', 'name_en', 'key']);
        });
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     *
     * @return Show
     */
    protected function detail($id)
    {
        return Show::make($id, new Profession(), function (Show $show) {
            $show->field('id');
            $show->field('key');
            $show->field('name_ru', __('profession.labels.russian'));
            $show->field('name_kk', __('profession.labels.kazakh'));
            $show->field('name_en', __('profession.fields.name_en'));
            $show->field('name_zh', __('profession.fields.name_zh'));
            $show->field('description');
            $show->field('is_for_expert')->bool();
            $show->field('is_for_lawyer')->bool();
            $show->field('status')->using(__('profession.options.status'));
            $show->field('created_at');
            $show->field('updated_at');
        });
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        return Form::make(new Profession(), function (Form $form) {
            $form->display('id');
            
            // 基本信息
            $form->text('key')->required()->help(__('profession.help.key'));
            $form->text('name_ru', __('profession.fields.name_ru'))->required();
            $form->text('name_kk', __('profession.fields.name_kk'))->required();
            $form->text('name_en', __('profession.fields.name_en'))->required();
            $form->text('name_zh', __('profession.fields.name_zh'))->required();
            $form->textarea('description')->rows(3);
            
            // 适用用户类型
            $form->switch('is_for_expert', __('profession.fields.is_for_expert'))
                ->default(true);
            $form->switch('is_for_lawyer', __('profession.fields.is_for_lawyer'))
                ->default(true);
            
            $form->select('status')
                ->options(__('profession.options.status'))
                ->default('active');

            // 表单保存前处理
            $form->saving(function (Form $form) {
                // 确保key是唯一的
                if ($form->key) {
                    $form->key = strtolower($form->key);
                }
            });
        });
    }
}
