<?php

namespace App\Admin\Controllers;

use App\Admin\Repositories\User;
use App\Models\Profession;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;
use Dcat\Admin\Widgets\Alert;

class AppUserController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new User(), function (Grid $grid) {
            $grid->column('id')->sortable();
            $grid->column('avatar')->image('', 50, 50);
            $grid->column('first_name');
            $grid->column('last_name');
            $grid->column('email');
            $grid->column('phone');
            $grid->column('user_type')->using(__('user.options.user_types'))->label([
                'company_admin' => 'success',
                'expert' => 'primary',
                'lawyer' => 'warning'
            ]);
            $grid->column('status')->using(__('user.options.status'))->label([
                'active' => 'success',
                'inactive' => 'default',
                'suspended' => 'danger'
            ]);
            $grid->column('last_login_at')->display(function ($time) {
                return $time ? date('Y-m-d H:i:s', strtotime($time)) : '-';
            })->sortable();
            $grid->column('created_at')->display(function ($time) {
                return $time ? date('Y-m-d H:i:s', strtotime($time)) : '-';
            })->sortable();

            // 操作按钮显示为直接按钮而不是下拉菜单
            $grid->setActionClass(\Dcat\Admin\Grid\Displayers\Actions::class);

            // 过滤器
            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('user_type')->select(__('user.options.user_types'));
                $filter->equal('status')->select(__('user.options.status'));
                $filter->like('email');
                $filter->like('phone');
                $filter->like('first_name');
                $filter->like('last_name');
            });

            // 批量操作
            $grid->batchActions(function ($batch) {
                $batch->add(new class(__('user.actions.activate')) extends \Dcat\Admin\Grid\BatchAction {
                    public function handle()
                    {
                        \App\Models\User::whereIn('id', $this->getKey())->update(['status' => 'active']);
                        return $this->response()->success(__('user.actions.activated_success'))->refresh();
                    }
                });
                
                $batch->add(new class(__('user.actions.deactivate')) extends \Dcat\Admin\Grid\BatchAction {
                    public function handle()
                    {
                        \App\Models\User::whereIn('id', $this->getKey())->update(['status' => 'inactive']);
                        return $this->response()->success(__('user.actions.deactivated_success'))->refresh();
                    }
                });
                
                $batch->add(new class(__('user.actions.suspend')) extends \Dcat\Admin\Grid\BatchAction {
                    public function handle()
                    {
                        \App\Models\User::whereIn('id', $this->getKey())->update(['status' => 'suspended']);
                        return $this->response()->success(__('user.actions.suspended_success'))->refresh();
                    }
                });
            });

            // 导出功能
            $grid->export();

            // 快速搜索
            $grid->quickSearch(['first_name', 'last_name', 'email', 'phone']);
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
        return Show::make($id, new User(), function (Show $show) {
            $show->field('id');
            $show->field('avatar')->image('', 100, 100);
            $show->field('first_name');
            $show->field('last_name');
            $show->field('email');
            $show->field('phone');
            $show->field('country_code');
            $show->field('user_type')->using(__('user.options.user_types'));
            $show->field('gender')->using(__('user.options.gender'));
            $show->field('locale');
            $show->field('status')->using(__('user.options.status'));
            $show->field('email_verified_at');
            $show->field('phone_verified_at');
            $show->field('last_login_at');
            $show->field('last_login_ip');
            $show->field('created_at');
            $show->field('updated_at');

            // 显示职业信息
            $show->field('professions')->as(function ($professions) {
                if (!$professions) return __('user.labels.none');
                return $professions->pluck('name_ru')->join(', ');
            });

            // 显示组织信息
            $show->field('organizations')->as(function ($organizations) {
                if (!$organizations) return __('user.labels.none');
                return $organizations->pluck('name')->join(', ');
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
        return Form::make(new User(), function (Form $form) {
            $form->display('id');
            
            // 在编辑时加载当前的主要职业
            if ($form->isEditing()) {
                $user = $form->model();
                $primaryProfession = $user->professions()->wherePivot('is_primary', true)->first();
                if ($primaryProfession) {
                    $form->model()->profession_id = $primaryProfession->id;
                }
            }
            
            // 头像上传
            $form->image('avatar')
                ->uniqueName()
                ->move('avatars')
                ->autoUpload();

            // 基本信息
            $form->text('first_name')->required();
            $form->text('last_name')->required();
            $form->email('email')->rules('email|unique:users,email,{{id}}');
            $form->text('phone')->rules('unique:users,phone,{{id}}');
            $form->text('country_code')->placeholder('+7');
            
            // 用户类型（后台可创建律师或客服）
            $form->select('user_type')
                ->options([
                    'lawyer' => __('user.options.user_types.lawyer'),
                    'operator' => __('user.options.user_types.operator'),
                ])
                ->default('lawyer')
                ->required()
                ->help(__('user.help.user_type'));

            // 密码 - 创建时必填，编辑时可选
            $form->password('password')
                ->minLength(8)
                ->required($form->isCreating())
                ->help($form->isCreating() ? __('user.help.password_create') : __('user.help.password_edit'));

            // 个人信息
            $form->select('gender')
                ->options(__('user.options.gender'));
            
            $form->select('locale')
                ->options(__('user.options.locales'))
                ->default('ru');

            $form->select('status')
                ->options(__('user.options.status'))
                ->default('active');

            // 职业选择 - 仅当用户类型为律师时需要
            $form->select('profession_id')
                ->options(Profession::where('is_for_lawyer', true)->pluck('name_ru', 'id'))
                ->rules('required_if:user_type,lawyer')
                ->help(__('user.help.profession'));

            // 隐藏字段
            $form->hidden('email_verified_at')->default(now());
            $form->hidden('phone_verified_at')->default(now());
            
            // 忽略 profession_id 字段，不直接写入 users 表
            $form->ignore(['profession_id']);

            // 表单保存前处理 - 处理密码及类型差异
            $form->saving(function (Form $form) {
                $password = $form->input('password');

                if ($form->isCreating() && $password) {
                    $form->input('password', bcrypt($password));
                } elseif ($form->isEditing()) {
                    if ($password) {
                        $form->input('password', bcrypt($password));
                    } else {
                        $form->deleteInput('password');
                    }
                }

                $userType = $form->input('user_type') ?? ($form->model()->user_type ?? null);
                if ($userType !== 'lawyer') {
                    // 非律师无需选择专业
                    $form->deleteInput('profession_id');
                }
            });

            // 表单保存后处理职业关联
            $form->saved(function (Form $form, $result) {
                $user = $form->model();
                $professionId = request()->input('profession_id');

                if ($user->user_type === 'lawyer' && $professionId) {
                    $user->professions()->sync([
                        $professionId => ['is_primary' => true],
                    ]);
                } elseif ($user->user_type !== 'lawyer') {
                    $user->professions()->detach();
                }
            });
        });
    }
}
