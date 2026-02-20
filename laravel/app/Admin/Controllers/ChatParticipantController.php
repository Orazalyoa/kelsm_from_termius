<?php

namespace App\Admin\Controllers;

use App\Admin\Repositories\ChatParticipant;
use App\Models\Chat;
use App\Models\User;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;

class ChatParticipantController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new ChatParticipant(), function (Grid $grid) {
            // 预加载关联关系
            $grid->model()->with(['chat', 'user'])->orderBy('joined_at', 'desc');
            
            $grid->column('id')->sortable();
            
            $grid->column('chat.title', __('chat_participant.chat'))->display(function ($title) {
                return "<a href='/admin/chats/{$this->chat_id}'>{$title}</a>";
            });
            
            $grid->column('user', __('chat_participant.participant'))->display(function () {
                if ($this->user) {
                    return $this->user->first_name . ' ' . $this->user->last_name . '<br>' . 
                           '<small class="text-muted">' . $this->user->email . '</small>';
                }
                return '-';
            });
            
            $grid->column('role', __('chat_participant.role'))->using([
                'creator' => __('chat_participant.roles.creator'),
                'admin' => __('chat_participant.roles.admin'),
                'member' => __('chat_participant.roles.member'),
                'client' => __('chat_participant.roles.client'),
                'lawyer' => __('chat_participant.roles.lawyer'),
            ])->label([
                'creator' => 'success',
                'admin' => 'primary',
                'member' => 'default',
                'client' => 'info',
                'lawyer' => 'warning',
            ]);
            
            $grid->column('joined_at', __('chat_participant.joined_at'))->display(function ($time) {
                return $time ? date('Y-m-d H:i:s', strtotime($time)) : '-';
            })->sortable();
            $grid->column('last_read_at', __('chat_participant.last_read_at'))->display(function ($time) {
                return $time ? date('Y-m-d H:i:s', strtotime($time)) : '-';
            })->sortable();

            // 操作按钮显示为直接按钮而不是下拉菜单
            $grid->setActionClass(\Dcat\Admin\Grid\Displayers\Actions::class);

            // 过滤器
            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('chat_id', __('chat_participant.chat'))->select(
                    Chat::query()->pluck('title', 'id')
                );
                $filter->equal('user_id', __('chat_participant.participant'))->select(
                    User::query()->get()->mapWithKeys(function ($user) {
                        return [$user->id => $user->first_name . ' ' . $user->last_name . ' (' . $user->email . ')'];
                    })
                );
                $filter->equal('role', __('chat_participant.role'))->select(__('chat_participant.roles'));
                $filter->between('joined_at', __('chat_participant.joined_at'))->datetime();
            });

            // 快速搜索
            $grid->quickSearch(function ($model, $query) {
                $model->whereHas('user', function ($q) use ($query) {
                    $q->where('first_name', 'like', "%{$query}%")
                      ->orWhere('last_name', 'like', "%{$query}%")
                      ->orWhere('email', 'like', "%{$query}%");
                });
            });

            // 批量操作
            $grid->batchActions(function ($batch) {
                $batch->add(new class(__('chat_participant.actions.remove_participant')) extends \Dcat\Admin\Grid\BatchAction {
                    public function handle()
                    {
                        \App\Models\ChatParticipant::whereIn('id', $this->getKey())->delete();
                        return $this->response()->success(__('chat_participant.actions.removed_success'))->refresh();
                    }
                });
            });

            // 导出
            $grid->export();
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
        return Show::make($id, new ChatParticipant(['chat', 'user']), function (Show $show) {
            $show->field('id');
            
            $show->field('chat.title', __('chat_participant.chat'));
            
            $show->field('user', __('chat_participant.participant'))->as(function () {
                if ($this->user) {
                    return $this->user->first_name . ' ' . $this->user->last_name . ' (' . $this->user->email . ')';
                }
                return '-';
            });
            
            $show->field('role', __('chat_participant.role'))->using([
                'creator' => __('chat_participant.roles.creator'),
                'admin' => __('chat_participant.roles.admin'),
                'member' => __('chat_participant.roles.member'),
                'client' => __('chat_participant.roles.client'),
                'lawyer' => __('chat_participant.roles.lawyer'),
            ]);
            $show->field('joined_at', __('chat_participant.joined_at'));
            $show->field('last_read_at', __('chat_participant.last_read_time'));
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
        return Form::make(new ChatParticipant(), function (Form $form) {
            $form->display('id');
            
            $form->select('chat_id', __('chat_participant.chat'))
                ->options(Chat::query()->pluck('title', 'id'))
                ->required();
            
            $form->select('user_id', __('chat_participant.participant'))
                ->options(User::query()->get()->mapWithKeys(function ($user) {
                    return [$user->id => $user->first_name . ' ' . $user->last_name . ' (' . $user->email . ')'];
                }))
                ->required();
            
            $form->select('role', __('chat_participant.role'))
                ->options(__('chat_participant.roles'))
                ->default('member');
            
            $form->datetime('joined_at', __('chat_participant.joined_at'))->default(now());
            $form->datetime('last_read_at', __('chat_participant.last_read_time'));

            $form->display('created_at');
            $form->display('updated_at');
        });
    }
}



