<?php

namespace App\Admin\Controllers;

use App\Admin\Repositories\ChatFile;
use App\Models\Chat;
use App\Models\Message;
use App\Models\User;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;

class ChatFileController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new ChatFile(), function (Grid $grid) {
            $grid->model()->orderBy('created_at', 'desc');
            
            $grid->column('id')->sortable();
            
            $grid->column('chat.title', __('chat_file.chat'))->display(function ($title) {
                return "<a href='/admin/chats/{$this->chat_id}'>{$title}</a>";
            });
            
            $grid->column('file_type', __('chat_file.file_type'))->using(__('chat_file.file_types'))->label([
                'document' => 'info',
                'image' => 'success',
                'video' => 'warning'
            ]);
            
            $grid->column('file_name', __('chat_file.file_name'))->display(function ($fileName) {
                if ($this->file_url) {
                    $icon = [
                        'document' => 'fa-file-alt',
                        'image' => 'fa-file-image',
                        'video' => 'fa-file-video'
                    ][$this->file_type] ?? 'fa-file';
                    
                    return "<i class='fa {$icon}'></i> <a href='{$this->file_url}' target='_blank'>{$fileName}</a>";
                }
                return $fileName;
            })->width('30%');
            
            $grid->column('file_size', __('chat_file.file_size'))->display(function ($size) {
                if (!$size) return '-';
                
                $units = ['B', 'KB', 'MB', 'GB'];
                $unit = 0;
                
                while ($size >= 1024 && $unit < count($units) - 1) {
                    $size /= 1024;
                    $unit++;
                }
                
                return round($size, 2) . ' ' . $units[$unit];
            });
            
            $grid->column('uploader', __('chat_file.uploader'))->display(function () {
                if ($this->uploader) {
                    return $this->uploader->first_name . ' ' . $this->uploader->last_name;
                }
                return '-';
            });
            
            $grid->column('created_at', __('chat_file.upload_time'))->display(function ($time) {
                return $time ? date('Y-m-d H:i:s', strtotime($time)) : '-';
            })->sortable();

            // 操作按钮显示为直接按钮而不是下拉菜单
            $grid->setActionClass(\Dcat\Admin\Grid\Displayers\Actions::class);

            // 过滤器
            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('chat_id', __('chat_file.chat'))->select(
                    Chat::query()->pluck('title', 'id')
                );
                $filter->equal('file_type', __('chat_file.file_type'))->select(__('chat_file.file_types'));
                $filter->equal('uploaded_by', __('chat_file.uploaded_by'))->select(
                    User::query()->get()->mapWithKeys(function ($user) {
                        return [$user->id => $user->first_name . ' ' . $user->last_name . ' (' . $user->email . ')'];
                    })
                );
                $filter->like('file_name', __('chat_file.file_name'));
                $filter->between('created_at', __('chat_file.upload_time'))->datetime();
            });

            // 快速搜索
            $grid->quickSearch(['file_name']);

            // 批量操作
            $grid->batchActions(function ($batch) {
                $batch->add(new class(__('chat_file.actions.batch_delete')) extends \Dcat\Admin\Grid\BatchAction {
                    public function handle()
                    {
                        \App\Models\ChatFile::whereIn('id', $this->getKey())->delete();
                        return $this->response()->success(__('chat_file.actions.deleted_success'))->refresh();
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
        return Show::make($id, new ChatFile(), function (Show $show) {
            $show->field('id');
            
            $show->field('chat.title', __('chat_file.chat'));
            
            $show->field('message_id', __('chat_file.message'))->as(function () {
                return "<a href='/admin/messages/{$this->message_id}'>" . __('chat_file.actions.view_message') . "</a>";
            });
            
            $show->field('file_type')->using(__('chat_file.file_types'));
            
            $show->field('file_url', __('chat_file.file_link'))->link();
            $show->field('file_name', __('chat_file.file_name'));
            
            $show->field('file_size', __('chat_file.file_size'))->as(function ($size) {
                if (!$size) return '-';
                
                $units = ['B', 'KB', 'MB', 'GB'];
                $unit = 0;
                
                while ($size >= 1024 && $unit < count($units) - 1) {
                    $size /= 1024;
                    $unit++;
                }
                
                return round($size, 2) . ' ' . $units[$unit];
            });
            
            $show->field('uploader', __('chat_file.uploader'))->as(function () {
                if ($this->uploader) {
                    return $this->uploader->first_name . ' ' . $this->uploader->last_name . ' (' . $this->uploader->email . ')';
                }
                return '-';
            });
            
            $show->field('created_at', __('chat_file.upload_time'));
        });
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        return Form::make(new ChatFile(), function (Form $form) {
            $form->display('id');
            
            $form->select('chat_id', __('chat_file.chat'))
                ->options(Chat::query()->pluck('title', 'id'))
                ->required();
            
            $form->select('message_id', __('chat_file.message'))
                ->options(Message::query()->pluck('id', 'id'))
                ->required();
            
            $form->select('file_type', __('chat_file.file_type'))
                ->options(__('chat_file.file_types'))
                ->required();
            
            $form->text('file_url', __('chat_file.file_url'))->required();
            $form->text('file_name', __('chat_file.file_name'))->required();
            $form->number('file_size', __('chat_file.file_size_bytes'))->required();
            
            $form->select('uploaded_by', __('chat_file.uploaded_by'))
                ->options(User::query()->get()->mapWithKeys(function ($user) {
                    return [$user->id => $user->first_name . ' ' . $user->last_name . ' (' . $user->email . ')'];
                }))
                ->required();

            $form->display('created_at');
        });
    }
}



