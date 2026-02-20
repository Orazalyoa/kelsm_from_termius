<?php

namespace App\Admin\Controllers;

use App\Admin\Repositories\Organization;
use App\Models\User;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;

/**
 * Organization Management Controller for Admin Backend
 * 
 * Permissions:
 * - Only company_admin users can create/manage organizations
 * - Experts and lawyers cannot manage organizations
 * 
 * This controller is for system administrators to oversee all organizations.
 */
class OrganizationController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new Organization(), function (Grid $grid) {
            // 预加载关联关系
            $grid->model()->with(['creator']);
            
            $grid->column('id')->sortable();
            $grid->column('logo')->image('', 50, 50);
            $grid->column('name');
            $grid->column('company_id')->label('primary');
            $grid->column('description')->limit(50);
            $grid->column('contact_name');
            $grid->column('phone');
            $grid->column('email');
            $grid->column('status')->using(__('organization.options.status'))->label([
                'active' => 'success',
                'inactive' => 'default'
            ]);
            $grid->column('creator.full_name', __('organization.labels.created_by'));
            $grid->column('members_count', __('organization.labels.members'))->display(function () {
                return $this->members()->count();
            });
            $grid->column('created_at')->display(function ($time) {
                return $time ? date('Y-m-d H:i:s', strtotime($time)) : '-';
            })->sortable();

            // 操作按钮显示为直接按钮而不是下拉菜单
            $grid->setActionClass(\Dcat\Admin\Grid\Displayers\Actions::class);

            // 过滤器
            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('status')->select(__('organization.options.status'));
                $filter->like('name');
                $filter->like('company_id');
                $filter->like('description');
                $filter->like('contact_name');
                $filter->like('phone');
                $filter->like('email');
            });

            // 批量操作
            $grid->batchActions(function ($batch) {
                $batch->add(new class(__('organization.actions.activate')) extends \Dcat\Admin\Grid\BatchAction {
                    public function handle()
                    {
                        \App\Models\Organization::whereIn('id', $this->getKey())->update(['status' => 'active']);
                        return $this->response()->success(__('organization.actions.activated_success'))->refresh();
                    }
                });
                
                $batch->add(new class(__('organization.actions.deactivate')) extends \Dcat\Admin\Grid\BatchAction {
                    public function handle()
                    {
                        \App\Models\Organization::whereIn('id', $this->getKey())->update(['status' => 'inactive']);
                        return $this->response()->success(__('organization.actions.deactivated_success'))->refresh();
                    }
                });
            });

            // 导出功能
            $grid->export();

            // 快速搜索
            $grid->quickSearch(['name', 'company_id', 'description', 'contact_name', 'phone', 'email']);
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
        return Show::make($id, new Organization(['creator', 'members']), function (Show $show) {
            $show->field('id');
            $show->field('logo')->image('', 100, 100);
            $show->field('name');
            $show->field('company_id');
            $show->field('description');
            $show->field('contact_name');
            $show->field('phone');
            $show->field('email');
            $show->field('status')->using(__('organization.options.status'));
            $show->field('creator.full_name', __('organization.labels.created_by'));
            $show->field('created_at');
            $show->field('updated_at');

            // 显示成员信息
            $show->field('members')->as(function ($members) {
                if (!$members) return __('organization.labels.none');
                $memberList = $members->map(function ($member) {
                    return $member->first_name . ' ' . $member->last_name . ' (' . $member->pivot->role . ')';
                });
                return $memberList->join('<br>');
            });
        });
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        return Form::make(new Organization(), function (Form $form) {
            $form->display('id');
            
            // Logo上传
            $form->image('logo')
                ->uniqueName()
                ->move('logos')
                ->autoUpload();

            // 基本信息
            $form->text('name')->required();
            $form->text('company_id')->required()->help(__('organization.help.company_id'));
            $form->textarea('description')->rows(3);
            
            // 联系信息
            $form->text('contact_name');
            $form->text('phone');
            $form->email('email');
            
            $form->select('status')
                ->options(__('organization.options.status'))
                ->default('active');

            // 创建者选择
            $form->select('created_by')
                ->options(User::companyAdmins()->pluck('first_name', 'id'))
                ->required();

            // 表单保存前处理
            $form->saving(function (Form $form) {
                // 确保company_id格式正确
                if ($form->company_id && !str_starts_with($form->company_id, '@')) {
                    $form->company_id = '@' . $form->company_id;
                }
            });
        });
    }
}
