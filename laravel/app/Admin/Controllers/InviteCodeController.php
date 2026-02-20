<?php

namespace App\Admin\Controllers;

use App\Admin\Repositories\InviteCode;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;

/**
 * Invite Code Management Controller for Admin Backend
 * 
 * Permissions:
 * - Only company_admin users can create invite codes
 * - Invite codes can be created for expert or company_admin user types
 * - Experts and lawyers cannot create invite codes
 * 
 * This controller is for system administrators to manage all invite codes.
 */
class InviteCodeController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new InviteCode(), function (Grid $grid) {
            // 预加载关联关系
            $grid->model()->with(['organization', 'creator', 'uses']);
            
            $grid->column('id')->sortable();
            $grid->column('code')->label('primary');
            $grid->column('organization.name', __('invite_code.fields.organization'));
            $grid->column('creator.full_name', __('invite_code.fields.created_by'));
            $grid->column('user_type')->using(__('invite_code.options.user_types'))->label([
                'expert' => 'info',
                'company_admin' => 'primary'
            ]);
            $grid->column('max_uses');
            $grid->column('used_count');
            $grid->column('expires_at')->display(function ($time) {
                return $time ? date('Y-m-d H:i:s', strtotime($time)) : '-';
            })->sortable();
            $grid->column('status_display', 'Status')->display(function () {
                // 1. 检查是否已使用完
                if ($this->max_uses && $this->used_count >= $this->max_uses) {
                    $status = __('invite_code.statuses.used');
                    $label = 'info';
                }
                // 2. 检查是否过期
                elseif ($this->expires_at && strtotime($this->expires_at) < time()) {
                    $status = __('invite_code.statuses.expired');
                    $label = 'default';
                }
                // 3. 激活状态
                elseif ($this->status === 'active') {
                    $status = __('invite_code.statuses.active');
                    $label = 'success';
                }
                else {
                    $status = $this->status;
                    $label = 'default';
                }
                
                return "<span class='label label-{$label}'>{$status}</span>";
            });
            $grid->column('created_at')->display(function ($time) {
                return $time ? date('Y-m-d H:i:s', strtotime($time)) : '-';
            })->sortable();

            // 操作按钮显示为直接按钮而不是下拉菜单
            $grid->setActionClass(\Dcat\Admin\Grid\Displayers\Actions::class);

            // 过滤器
            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('status')->select(__('invite_code.options.status'));
                $filter->like('code');
                $filter->equal('organization_id', __('invite_code.fields.organization'))->select(\App\Models\Organization::pluck('name', 'id'));
                $filter->equal('created_by', __('invite_code.fields.created_by'))->select(\App\Models\User::companyAdmins()->pluck('first_name', 'id'));
                $filter->date('expires_at');
                $filter->date('created_at');
            });

            // 批量操作
            $grid->batchActions(function ($batch) {
                $batch->add(new class(__('invite_code.actions.delete')) extends \Dcat\Admin\Grid\BatchAction {
                    public function handle()
                    {
                        \App\Models\InviteCode::whereIn('id', $this->getKey())->delete();
                        return $this->response()->success(__('invite_code.actions.deleted_success'))->refresh();
                    }
                });
            });

            // 导出功能
            $grid->export();

            // 快速搜索
            $grid->quickSearch(['code', 'organization.name']);
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
        return Show::make($id, new InviteCode(['organization', 'creator', 'uses.user']), function (Show $show) {
            $show->field('id');
            $show->field('code');
            $show->field('organization.name', __('invite_code.fields.organization'));
            $show->field('creator.full_name', __('invite_code.fields.created_by'));
            $show->field('user_type')->using(__('invite_code.options.user_types'));
            $show->field('permissions')->as(function ($permissions) {
                if (!$permissions) return '-';
                
                $list = [];
                if (isset($permissions['can_apply_consultation'])) {
                    $list[] = $permissions['can_apply_consultation'] ? __('invite_code.labels.can_apply_consultation_yes') : __('invite_code.labels.can_apply_consultation_no');
                }
                if (isset($permissions['can_manage_organization'])) {
                    $list[] = $permissions['can_manage_organization'] ? __('invite_code.labels.can_manage_organization_yes') : __('invite_code.labels.can_manage_organization_no');
                }
                if (isset($permissions['can_view_all_consultations'])) {
                    $list[] = $permissions['can_view_all_consultations'] ? __('invite_code.labels.can_view_all_consultations_yes') : __('invite_code.labels.can_view_all_consultations_no');
                }
                
                return implode('<br>', $list);
            });
            $show->field('max_uses');
            $show->field('used_count');
            $show->field('expires_at');
            $show->field('status_display', 'Status')->as(function () {
                // 1. 检查是否已使用完
                if ($this->max_uses && $this->used_count >= $this->max_uses) {
                    return __('invite_code.statuses.used');
                }
                // 2. 检查是否过期
                elseif ($this->expires_at && $this->expires_at->isPast()) {
                    return __('invite_code.statuses.expired');
                }
                // 3. 激活状态
                elseif ($this->status === 'active') {
                    return __('invite_code.statuses.active');
                }
                
                return $this->status;
            });
            $show->field('created_at');
            $show->field('updated_at');

            // 显示使用记录
            $show->field('uses')->as(function ($uses) {
                if (!$uses || $uses->isEmpty()) return __('invite_code.labels.no_uses');
                $useList = $uses->map(function ($use) {
                    return $use->user->first_name . ' ' . $use->user->last_name . ' (' . $use->used_at . ')';
                });
                return $useList->join('<br>');
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
        return Form::make(new InviteCode(), function (Form $form) {
            $form->display('id');
            
            // 基本信息
            $form->text('code')->required()->help(__('invite_code.help.code'));
            $form->select('organization_id', __('invite_code.fields.organization'))
                ->options(\App\Models\Organization::pluck('name', 'id'))
                ->required();
            $form->select('created_by', __('invite_code.fields.created_by'))
                ->options(\App\Models\User::companyAdmins()->pluck('first_name', 'id'))
                ->required();
            
            $form->select('user_type')
                ->options(__('invite_code.options.user_types'))
                ->default('expert')
                ->required()
                ->help(__('invite_code.help.user_type'));
            
            // 权限设置（仅专家类型）
            $form->divider();
            $form->switch('permissions.can_apply_consultation', __('invite_code.labels.can_apply_consultation_yes'))
                ->default(1)
                ->help(__('invite_code.help.can_apply_consultation'));

            $form->divider();
            $form->number('max_uses')
                ->min(1)
                ->max(100)
                ->default(1)
                ->help(__('invite_code.help.max_uses'));

            $form->datetime('expires_at')
                ->help(__('invite_code.help.expires_at'));

            $form->select('status')
                ->options(__('invite_code.options.status'))
                ->default('active');

            // 表单保存前处理
            $form->saving(function (Form $form) {
                // 自动生成邀请码
                if ($form->isCreating() && !$form->code) {
                    $form->code = strtoupper(\Illuminate\Support\Str::random(10));
                }
                
                // 如果是公司管理员，设置完整权限
                if ($form->user_type === 'company_admin') {
                    $form->permissions = [
                        'can_apply_consultation' => true,
                        'can_manage_organization' => true,
                        'can_view_all_consultations' => true
                    ];
                }
                // 如果是专家，只保留 can_apply_consultation
                elseif ($form->user_type === 'expert') {
                    $canApply = $form->input('permissions.can_apply_consultation', true);
                    $form->permissions = [
                        'can_apply_consultation' => (bool) $canApply
                    ];
                }
            });
        });
    }
}
