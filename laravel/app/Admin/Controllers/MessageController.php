<?php

namespace App\Admin\Controllers;

use App\Admin\Repositories\Message;
use App\Models\Chat;
use App\Models\User;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;
use Illuminate\Http\Request;

class MessageController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new Message(), function (Grid $grid) {
            $grid->model()->orderBy('created_at', 'desc');
            
            $grid->column('id')->sortable();
            
            $grid->column('chat.title', __('message.chat'))->display(function ($title) {
                $escapedTitle = htmlspecialchars($title ?? '', ENT_QUOTES, 'UTF-8');
                $escapedChatId = htmlspecialchars($this->chat_id, ENT_QUOTES, 'UTF-8');
                return "<a href='/admin/chats/{$escapedChatId}'>{$escapedTitle}</a>";
            });
            
            $grid->column('sender.first_name', __('message.sender'))->display(function () {
                if ($this->sender) {
                    return $this->sender->first_name . ' ' . $this->sender->last_name;
                }
                return '<span class="badge badge-secondary">' . __('message.system') . '</span>';
            });
            
            $grid->column('type')->using(__('message.types'))->label([
                'text' => 'primary',
                'image' => 'success',
                'document' => 'info',
                'video' => 'warning',
                'system' => 'secondary'
            ]);
            
            $grid->column('content', __('message.content'))->display(function ($content) {
                // 如果是图片类型，显示缩略图
                if ($this->type === 'image' && $this->file_url) {
                    $escapedUrl = htmlspecialchars($this->file_url, ENT_QUOTES, 'UTF-8');
                    return "<a href='{$escapedUrl}' target='_blank'><img src='{$escapedUrl}' style='max-width:200px;max-height:150px;' /></a>";
                }
                // 如果是其他文件类型，显示文件信息
                if (in_array($this->type, ['document', 'video']) && $this->file_url) {
                    $fileName = $this->file_name ?: __('message.file');
                    return htmlspecialchars($fileName, ENT_QUOTES, 'UTF-8');
                }
                // 普通文本消息
                if ($content) {
                    $text = strip_tags($content);
                    $preview = mb_substr($text, 0, 50) . (mb_strlen($text) > 50 ? '...' : '');
                    return htmlspecialchars($preview, ENT_QUOTES, 'UTF-8');
                }
                return '-';
            })->width('25%');
            
            $grid->column('file_name', __('message.file_name'))->display(function ($fileName) {
                if (!$fileName) return '-';
                if ($this->file_url) {
                    $escapedUrl = htmlspecialchars($this->file_url, ENT_QUOTES, 'UTF-8');
                    $escapedFileName = htmlspecialchars($fileName, ENT_QUOTES, 'UTF-8');
                    return "<a href='{$escapedUrl}' target='_blank'>{$escapedFileName}</a>";
                }
                return htmlspecialchars($fileName, ENT_QUOTES, 'UTF-8');
            });
            
            $grid->column('file_size', __('message.file_size'))->display(function ($size) {
                if (!$size) return '-';
                
                $units = ['B', 'KB', 'MB', 'GB'];
                $unit = 0;
                
                while ($size >= 1024 && $unit < count($units) - 1) {
                    $size /= 1024;
                    $unit++;
                }
                
                return round($size, 2) . ' ' . $units[$unit];
            });
            
            $grid->column('created_at', __('message.send_time'))->display(function ($time) {
                return $time ? date('Y-m-d H:i:s', strtotime($time)) : '-';
            })->sortable();

            // 操作按钮显示为直接按钮而不是下拉菜单
            $grid->setActionClass(\Dcat\Admin\Grid\Displayers\Actions::class);

            // 过滤器
            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('chat_id', __('message.chat'))->select(
                    Chat::query()->pluck('title', 'id')
                );
                $filter->equal('sender_id', __('message.sender'))->select(
                    User::query()->get()->mapWithKeys(function ($user) {
                        return [$user->id => $user->first_name . ' ' . $user->last_name . ' (' . $user->email . ')'];
                    })
                );
                $filter->equal('type', __('message.type'))->select(__('message.types'));
                $filter->like('content', __('message.content'));
                $filter->between('created_at', __('message.send_time'))->datetime();
            });

            // 快速搜索
            $grid->quickSearch(['content', 'file_name']);

            // 批量操作
            $grid->batchActions(function ($batch) {
                $batch->add(new class(__('message.actions.batch_delete')) extends \Dcat\Admin\Grid\BatchAction {
                    public function handle()
                    {
                        try {
                            $ids = $this->getKey();
                            if (empty($ids)) {
                                return $this->response()->error(__('message.actions.no_selection'));
                            }
                            
                            // 使用软删除
                            $count = \App\Models\Message::whereIn('id', $ids)->delete();
                            
                            if ($count > 0) {
                                return $this->response()->success(__('message.actions.deleted_success', ['count' => $count]))->refresh();
                            } else {
                                return $this->response()->warning(__('message.actions.no_messages_deleted'));
                            }
                        } catch (\Exception $e) {
                            return $this->response()->error(__('message.actions.delete_failed') . ': ' . $e->getMessage());
                        }
                    }
                });
            });

            // 导出
            $grid->export()->rows(function ($rows) {
                foreach ($rows as &$row) {
                    if (isset($row['sender.first_name'])) {
                        $row[__('message.sender')] = $row['sender.first_name'];
                        unset($row['sender.first_name']);
                    }
                    if (isset($row['chat.title'])) {
                        $row[__('message.chat')] = $row['chat.title'];
                        unset($row['chat.title']);
                    }
                }
                return $rows;
            });
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
        return Show::make($id, new Message(), function (Show $show) {
            $show->field('id');
            
            $show->field('chat.title', __('message.chat'))->as(function () {
                return $this->chat ? $this->chat->title : '-';
            });
            
            $show->field('sender', __('message.sender'))->as(function () {
                if ($this->sender) {
                    return $this->sender->first_name . ' ' . $this->sender->last_name . ' (' . $this->sender->email . ')';
                }
                return __('message.system_message');
            });
            
            $show->field('type')->using(__('message.types'));
            
            $show->field('content', __('message.content'));
            
            $show->field('file_url', __('message.file_link'))->link();
            $show->field('file_name', __('message.file_name'));
            $show->field('file_size', __('message.file_size'))->as(function ($size) {
                if (!$size) return '-';
                
                $units = ['B', 'KB', 'MB', 'GB'];
                $unit = 0;
                
                while ($size >= 1024 && $unit < count($units) - 1) {
                    $size /= 1024;
                    $unit++;
                }
                
                return round($size, 2) . ' ' . $units[$unit];
            });
            
            // 消息状态
            $show->field('statuses', __('message.read_status'))->as(function () {
                if (!$this->statuses || $this->statuses->isEmpty()) {
                    return __('message.no_status');
                }
                
                $statusTranslations = __('message.statuses');
                return $this->statuses->map(function ($status) use ($statusTranslations) {
                    $user = $status->user;
                    $userName = $user ? $user->first_name . ' ' . $user->last_name : 'Unknown';
                    $statusText = $statusTranslations[$status->status] ?? $status->status;
                    
                    return "{$userName}: {$statusText} (" . $status->updated_at->format('Y-m-d H:i:s') . ")";
                })->join('<br>');
            });
            
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
        return Form::make(new Message(), function (Form $form) {
            $form->display('id');
            
            $form->select('chat_id', __('message.chat'))
                ->options(Chat::query()->pluck('title', 'id'))
                ->required();
            
            $form->select('sender_id', __('message.sender'))
                ->options(User::query()->get()->mapWithKeys(function ($user) {
                    return [$user->id => $user->first_name . ' ' . $user->last_name . ' (' . $user->email . ')'];
                }))
                ->help(__('message.help.system_message_empty'));
            
            $form->select('type', __('message.type'))
                ->options(__('message.types'))
                ->required()
                ->default('text');
            
            $form->textarea('content', __('message.content'))->rows(5);
            
            $form->text('file_url', __('message.file_url'));
            $form->text('file_name', __('message.file_name'));
            $form->number('file_size', __('message.file_size_bytes'));

            $form->display('created_at');
            $form->display('updated_at');
        });
    }
}



