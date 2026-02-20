<?php

namespace App\Admin\Controllers;

use App\Admin\Repositories\Chat;
use App\Models\User;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;

class ChatController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new Chat(), function (Grid $grid) {
            // 预加载关联关系，并添加消息数量统计
            $grid->model()->with(['creator', 'participants', 'lastMessage'])
                ->withCount('messages')
                ->orderBy('updated_at', 'desc');
            
            $grid->column('id')->sortable();
            
            $grid->column('title')->display(function ($title) {
                $escapedTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
                $escapedId = htmlspecialchars($this->id, ENT_QUOTES, 'UTF-8');
                return "<a href='/admin/chats/{$escapedId}'>{$escapedTitle}</a>";
            });
            
            $grid->column('type')->using(__('chat.types'))->label([
                'private' => 'primary',
                'group' => 'success'
            ]);
            
            $grid->column('creator.full_name', __('chat.creator'))->display(function () {
                if ($this->creator) {
                    return $this->creator->full_name;
                }
                return '-';
            });
            
            $grid->column('participants_count', __('chat.participants_count'))->display(function () {
                return $this->participants->count();
            });
            
            $grid->column('messages_count', __('chat.messages_count'))
                ->display(function () {
                    // 使用预加载的关联，避免 N+1 查询
                    return $this->messages_count ?? $this->messages()->count();
                })
                ->sortable();
            
            $grid->column('lastMessage.content', __('chat.last_message'))->display(function () {
                if ($this->lastMessage) {
                    $content = $this->lastMessage->content ?: '[' . $this->lastMessage->type . ']';
                    $preview = mb_substr($content, 0, 30) . (mb_strlen($content) > 30 ? '...' : '');
                    return htmlspecialchars($preview, ENT_QUOTES, 'UTF-8');
                }
                return '-';
            });
            
            $grid->column('updated_at', __('chat.last_active'))->display(function ($time) {
                return $time ? date('Y-m-d H:i:s', strtotime($time)) : '-';
            })->sortable();
            $grid->column('created_at', __('chat.created_at'))->display(function ($time) {
                return $time ? date('Y-m-d H:i:s', strtotime($time)) : '-';
            })->sortable();

            // 操作按钮显示为直接按钮而不是下拉菜单
            $grid->setActionClass(\Dcat\Admin\Grid\Displayers\Actions::class);

            // 过滤器
            $grid->filter(function (Grid\Filter $filter) {
                $filter->like('title', __('chat.title'));
                $filter->equal('type', __('chat.type'))->select(__('chat.types'));
                $filter->equal('created_by', __('chat.creator'))->select(
                    User::query()->pluck('email', 'id')
                );
                $filter->between('created_at', __('chat.created_at'))->datetime();
            });

            // 快速搜索
            $grid->quickSearch(['title']);

            // 行操作
            $grid->actions(function (Grid\Displayers\Actions $actions) {
                $chatId = htmlspecialchars($actions->getKey(), ENT_QUOTES, 'UTF-8');
                $linkText = htmlspecialchars(__('chat.actions.view_messages'), ENT_QUOTES, 'UTF-8');
                $actions->append('<a href="/admin/messages?chat_id=' . $chatId . '" class="btn btn-sm btn-primary">' . $linkText . '</a>');
            });

            // 批量操作
            $grid->batchActions(function ($batch) {
                $batch->disableDelete();
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
        return Show::make($id, new Chat(['creator', 'participants.user', 'messages']), function (Show $show) {
            $show->field('id');
            $show->field('title');
            $show->field('type')->using(__('chat.types'));
            
            $show->field('creator.full_name', __('chat.creator'))->as(function () {
                if ($this->creator) {
                    return $this->creator->full_name . ' (' . $this->creator->email . ')';
                }
                return '-';
            });

            // 参与者列表
            $show->field('participants', __('chat.participants'))->as(function () {
                return $this->participants->map(function ($participant) {
                    $user = $participant->user;
                    return $user->first_name . ' ' . $user->last_name . ' (' . $user->email . ')' . 
                           ($participant->role ? ' [' . $participant->role . ']' : '');
                })->join('<br>');
            });

            // 消息统计
            $show->field('messages_count', __('chat.messages_total'))->as(function () {
                return $this->messages()->count();
            });

            $show->field('created_at')->as(function ($time) {
                return $time ? date('Y-m-d H:i:s', strtotime($time)) : '-';
            });
            $show->field('updated_at')->as(function ($time) {
                return $time ? date('Y-m-d H:i:s', strtotime($time)) : '-';
            });

            // 关联消息
            $show->relation('messages', __('chat.messages.title'), function ($model) {
                $grid = new Grid(new \App\Models\Message());
                $grid->model()->where('chat_id', $model->id)->orderBy('created_at', 'desc')->limit(50);
                
                $grid->column('id');
                $grid->column('sender.first_name', __('chat.messages.sender'))->display(function () {
                    return $this->sender ? $this->sender->first_name . ' ' . $this->sender->last_name : __('chat.messages.system');
                });
                $grid->column('type')->using(__('chat.message_types'));
                $grid->column('content')->display(function ($content) {
                    // 如果是图片类型，显示缩略图
                    if ($this->type === 'image' && $this->file_url) {
                        $escapedUrl = htmlspecialchars($this->file_url, ENT_QUOTES, 'UTF-8');
                        return "<a href='{$escapedUrl}' target='_blank'><img src='{$escapedUrl}' style='max-width:200px;max-height:150px;' /></a>";
                    }
                    // 如果是其他文件类型，显示文件信息
                    if (in_array($this->type, ['document', 'video']) && $this->file_url) {
                        $fileName = $this->file_name ?: __('chat.messages.file');
                        $escapedUrl = htmlspecialchars($this->file_url, ENT_QUOTES, 'UTF-8');
                        $escapedFileName = htmlspecialchars($fileName, ENT_QUOTES, 'UTF-8');
                        return "<a href='{$escapedUrl}' target='_blank'>{$escapedFileName}</a>";
                    }
                    // 普通文本消息
                    if ($content) {
                        $text = strip_tags($content);
                        $preview = mb_substr($text, 0, 50) . (mb_strlen($text) > 50 ? '...' : '');
                        return htmlspecialchars($preview, ENT_QUOTES, 'UTF-8');
                    }
                    return '-';
                });
                $grid->column('file_name')->display(function ($fileName) {
                    if (!$fileName) return '-';
                    if ($this->file_url) {
                        $escapedUrl = htmlspecialchars($this->file_url, ENT_QUOTES, 'UTF-8');
                        $escapedFileName = htmlspecialchars($fileName, ENT_QUOTES, 'UTF-8');
                        return "<a href='{$escapedUrl}' target='_blank'>{$escapedFileName}</a>";
                    }
                    return htmlspecialchars($fileName, ENT_QUOTES, 'UTF-8');
                });
                $grid->column('created_at')->display(function ($time) {
                    return $time ? date('Y-m-d H:i:s', strtotime($time)) : '-';
                })->sortable();
                
                $grid->disableCreateButton();
                $grid->disableActions();
                $grid->disableBatchActions();
                
                return $grid;
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
        return Form::make(new Chat(), function (Form $form) {
            $form->display('id');
            
            $form->text('title', __('chat.title'))->required();
            
            $form->select('type', __('chat.type'))
                ->options(__('chat.types'))
                ->required()
                ->default('group');
            
            $form->select('created_by', __('chat.creator'))
                ->options(User::query()->get()->mapWithKeys(function ($user) {
                    return [$user->id => $user->first_name . ' ' . $user->last_name . ' (' . $user->email . ')'];
                }))
                ->required();

            $form->display('created_at');
            $form->display('updated_at');
        });
    }
}



