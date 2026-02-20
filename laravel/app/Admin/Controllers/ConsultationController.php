<?php

namespace App\Admin\Controllers;

use App\Admin\Actions\Grid\AssignLawyer;
use App\Admin\Actions\Grid\AssignOperator;
use App\Admin\Repositories\Consultation;
use App\Models\Consultation as ConsultationModel;
use App\Models\User;
use App\Services\ConsultationService;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;
use Illuminate\Http\Request;

class ConsultationController extends AdminController
{
    protected $consultationService;

    public function __construct(ConsultationService $consultationService)
    {
        $this->consultationService = $consultationService;
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new Consultation(), function (Grid $grid) {
            // 预加载关联关系
            $grid->model()->with(['creator', 'assignedLawyer', 'lawyers', 'operators', 'files'])->orderBy('created_at', 'desc');
            
            // 设置表格为响应式，允许水平滚动
            $grid->scrollbarX();
            
            $grid->column('id')->sortable()->width('60px');
            
            $grid->column('title')->display(function ($title) {
                $escapedTitle = htmlspecialchars($title ?? '', ENT_QUOTES, 'UTF-8');
                $shortTitle = mb_strlen($escapedTitle) > 30 ? mb_substr($escapedTitle, 0, 30) . '...' : $escapedTitle;
                $escapedId = htmlspecialchars($this->id, ENT_QUOTES, 'UTF-8');
                return "<a href='/admin/consultations/{$escapedId}' title='{$escapedTitle}'>{$shortTitle}</a>";
            })->width('200px');
            
            $grid->column('topic_type', __('consultation.topic_type'))->using(__('consultation.topic_types'))->label([
                'legal_consultation' => 'info',
                'contracts_deals' => 'success',
                'legal_services' => 'warning',
                'other' => 'default'
            ])->width('120px');
            
            $grid->column('status', __('consultation.status'))->using(__('consultation.statuses'))->label([
                'pending' => 'warning',
                'in_progress' => 'primary',
                'archived' => 'success',
                'cancelled' => 'danger'
            ])->width('120px');
            
            $grid->column('priority', __('consultation.priority'))->using(__('consultation.priorities'))->label([
                'low' => 'default',
                'medium' => 'info',
                'high' => 'warning',
                'urgent' => 'danger'
            ])->width('80px');
            
            $grid->column('creator.full_name', __('consultation.creator'))->display(function () {
                if ($this->creator) {
                    $name = htmlspecialchars($this->creator->full_name ?? '', ENT_QUOTES, 'UTF-8');
                    $shortName = mb_strlen($name) > 20 ? mb_substr($name, 0, 20) . '...' : $name;
                    return "<span title='{$name}'>{$shortName}</span>";
                }
                return '-';
            })->width('120px');
            
            $grid->column('lawyers', __('consultation.assigned_lawyers'))->display(function () {
                $lawyers = $this->lawyers;
                if ($lawyers && $lawyers->count() > 0) {
                    $names = $lawyers->map(function ($lawyer) {
                        $isPrimary = $lawyer->pivot->is_primary ?? false;
                        $name = htmlspecialchars($lawyer->full_name ?? '', ENT_QUOTES, 'UTF-8');
                        return $isPrimary ? "<strong>{$name}</strong>" : $name;
                    })->implode(', ');
                    
                    $title = strip_tags($names);
                    return "<span title='{$title}'>{$names}</span>";
                }
                return '<span class="label label-warning">' . htmlspecialchars(__('consultation.unassigned'), ENT_QUOTES, 'UTF-8') . '</span>';
            })->width('150px');

            $grid->column('operators', __('consultation.assigned_operators'))->display(function () {
                $operators = $this->operators;
                if ($operators && $operators->count() > 0) {
                    $names = $operators->map(function ($operator) {
                        return htmlspecialchars($operator->full_name ?? '', ENT_QUOTES, 'UTF-8');
                    })->implode(', ');
                    $title = strip_tags($names);
                    return "<span title='{$title}'>{$names}</span>";
                }
                $noOperatorsText = htmlspecialchars(__('consultation.no_operators'), ENT_QUOTES, 'UTF-8');
                return '<span class="label label-default">' . $noOperatorsText . '</span>';
            })->width('150px');
            
            $grid->column('files_count', __('consultation.files_count'))->display(function () {
                return $this->files()->rootFiles()->count();
            })->width('60px');
            
            $grid->column('chat_id', __('consultation.chat_room'))->display(function ($chatId) {
                if ($chatId) {
                    $escapedChatId = htmlspecialchars($chatId, ENT_QUOTES, 'UTF-8');
                    return "<a href='/admin/chats/{$escapedChatId}' class='btn btn-xs btn-success'><i class='fa fa-comments'></i></a>";
                }
                return '<span class="label label-default">-</span>';
            })->width('80px');
            
            $grid->column('created_at', __('consultation.created_at'))->display(function ($time) {
                return $time ? date('m-d H:i', strtotime($time)) : '-';
            })->sortable()->width('100px');
            
            $grid->column('assigned_at', __('consultation.assigned_at'))->display(function ($time) {
                return $time ? date('m-d H:i', strtotime($time)) : '-';
            })->width('100px');

            // 操作按钮显示为下拉菜单以节省空间
            $grid->setActionClass(\Dcat\Admin\Grid\Displayers\DropdownActions::class);

            // 过滤器
            $grid->filter(function (Grid\Filter $filter) {
                $filter->like('title', __('consultation.title'));
                
                $filter->equal('status', __('consultation.status'))->select(__('consultation.statuses'));
                
                $filter->equal('topic_type', __('consultation.topic_type'))->select(__('consultation.topic_types'));
                
                $filter->equal('priority', __('consultation.priority'))->select(__('consultation.priorities'));
                
                $filter->equal('created_by', __('consultation.creator'))->select(
                    User::where('user_type', '!=', 'lawyer')
                        ->get()
                        ->pluck('full_name', 'id')
                );
                
                $filter->equal('assigned_lawyer_id', __('consultation.assigned_lawyer'))->select(
                    User::lawyers()->get()->pluck('full_name', 'id')
                );
                
                $filter->between('created_at', __('consultation.created_at'))->datetime();
            });

            // 快速搜索
            $grid->quickSearch(['title', 'description']);

            // 行操作
            $grid->actions(function (Grid\Displayers\Actions $actions) {
                $actions->append(new AssignLawyer());
                $actions->append(new AssignOperator());
            });

            // 批量操作
            $grid->batchActions(function ($batch) {
                $batch->disableDelete(); // 禁用批量删除
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
        return Show::make($id, new Consultation(['creator', 'assignedLawyer', 'lawyers', 'operators', 'files', 'chat']), function (Show $show) {
            $show->field('id');
            $show->field('title', __('consultation.title'));
            $show->field('description', __('consultation.description'));
            
            $show->field('topic_type', __('consultation.topic_type'))->using(__('consultation.topic_types'));
            
            $show->field('status', __('consultation.status'))->using(__('consultation.statuses'));
            
            $show->field('priority', __('consultation.priority'))->using(__('consultation.priorities'));
            
            $show->field('creator.full_name', __('consultation.creator'));
            $show->field('creator.email', __('consultation.creator_email'));
            
            $show->field('assignedLawyer.full_name', __('consultation.primary_lawyer'));
            $show->field('assignedLawyer.email', __('consultation.lawyer_email'));
            
            // 显示所有分配的律师
            $show->field('lawyers', __('consultation.all_assigned_lawyers'))->as(function ($lawyers) {
                if ($lawyers && $lawyers->count() > 0) {
                    $html = '<ul>';
                    foreach ($lawyers as $lawyer) {
                        $isPrimary = $lawyer->pivot->is_primary ?? false;
                        $assignedAt = $lawyer->pivot->assigned_at ? date('Y-m-d H:i', strtotime($lawyer->pivot->assigned_at)) : '-';
                        $badge = $isPrimary ? ' <span class="label label-primary">' . htmlspecialchars(__('consultation.primary_label'), ENT_QUOTES, 'UTF-8') . '</span>' : '';
                        $lawyerName = htmlspecialchars($lawyer->full_name ?? '', ENT_QUOTES, 'UTF-8');
                        $lawyerEmail = htmlspecialchars($lawyer->email ?? '', ENT_QUOTES, 'UTF-8');
                        $assignedTimeText = htmlspecialchars(__('consultation.assigned_time'), ENT_QUOTES, 'UTF-8');
                        $html .= "<li>{$lawyerName} ({$lawyerEmail}){$badge} - {$assignedTimeText}: {$assignedAt}</li>";
                    }
                    $html .= '</ul>';
                    return $html;
                }
                return htmlspecialchars(__('consultation.unassigned'), ENT_QUOTES, 'UTF-8');
            })->unescape();

            $show->field('operators', __('consultation.all_assigned_operators'))->as(function ($operators) {
                if ($operators && $operators->count() > 0) {
                    $html = '<ul>';
                    foreach ($operators as $operator) {
                        $assignedAt = $operator->pivot->assigned_at ? date('Y-m-d H:i', strtotime($operator->pivot->assigned_at)) : '-';
                        $operatorName = htmlspecialchars($operator->full_name ?? '', ENT_QUOTES, 'UTF-8');
                        $operatorEmail = htmlspecialchars($operator->email ?? '', ENT_QUOTES, 'UTF-8');
                        $assignedTimeText = htmlspecialchars(__('consultation.assigned_time'), ENT_QUOTES, 'UTF-8');
                        $html .= "<li>{$operatorName} ({$operatorEmail}) - {$assignedTimeText}: {$assignedAt}</li>";
                    }
                    $html .= '</ul>';
                    return $html;
                }
                return htmlspecialchars(__('consultation.no_operators'), ENT_QUOTES, 'UTF-8');
            })->unescape();
            
            $show->field('created_at', __('consultation.created_at'));
            $show->field('assigned_at', __('consultation.assigned_at'));
            $show->field('completed_at', __('consultation.completed_at'));
            
            // 聊天室链接
            $show->field('chat_id', __('consultation.chat_room'))->as(function ($chatId) {
                if ($chatId) {
                    $escapedChatId = htmlspecialchars($chatId, ENT_QUOTES, 'UTF-8');
                    $linkText = htmlspecialchars(__('consultation.actions.view_chat_room'), ENT_QUOTES, 'UTF-8');
                    return "<a href='/admin/chats/{$escapedChatId}' target='_blank' class='btn btn-success'>{$linkText}</a>";
                }
                return htmlspecialchars(__('consultation.not_created'), ENT_QUOTES, 'UTF-8');
            });

            // 状态变更日志
            $show->relation('statusLogs', __('consultation.status_log.title'), function ($model) {
                $grid = new Grid(new \App\Models\ConsultationStatusLog());
                $grid->model()->where('consultation_id', $model->id)->orderBy('created_at', 'desc');
                
                $grid->column('old_status', __('consultation.status_log.old_status'))->display(function ($status) {
                    return $status ? ucwords(str_replace('_', ' ', $status)) : __('consultation.status_log.new_record');
                });
                $grid->column('new_status', __('consultation.status_log.new_status'))->display(function ($status) {
                    return ucwords(str_replace('_', ' ', $status));
                });
                $grid->column('changedBy.full_name', __('consultation.status_log.changed_by'));
                $grid->column('reason', __('consultation.status_log.reason'));
                $grid->column('created_at', __('consultation.time'))->display(function ($time) {
                    return $time ? date('Y-m-d H:i:s', strtotime($time)) : '-';
                });
                
                $grid->disableActions();
                $grid->disableCreateButton();
                $grid->disableFilter();
                $grid->disableExport();
                $grid->disableBatchActions();
                
                return $grid;
            });

            // 文件列表
            $show->relation('files', __('consultation.file.title'), function ($model) {
                $grid = new Grid(new \App\Models\ConsultationFile());
                $grid->model()->where('consultation_id', $model->id)
                    ->whereNull('parent_file_id')
                    ->orderBy('created_at', 'desc');
                
                $grid->column('file_name', __('consultation.file.name'));
                $grid->column('file_type', __('consultation.file.type'));
                $grid->column('file_size', __('consultation.file.size'))->display(function ($size) {
                    $units = ['B', 'KB', 'MB', 'GB'];
                    $unit = 0;
                    while ($size >= 1024 && $unit < count($units) - 1) {
                        $size /= 1024;
                        $unit++;
                    }
                    return round($size, 2) . ' ' . $units[$unit];
                });
                $grid->column('version', __('consultation.file.version'))->display(function ($version) {
                    try {
                        $latestVersion = $this->getLatestVersion();
                        if ($latestVersion && $latestVersion->id === $this->id) {
                            $latestText = htmlspecialchars(__('consultation.file.latest'), ENT_QUOTES, 'UTF-8');
                            return "v{$version} ({$latestText})";
                        }
                        return "v{$version}";
                    } catch (\Exception $e) {
                        return "v{$version}";
                    }
                });
                $grid->column('uploadedBy.full_name', __('consultation.file.uploader'));
                $grid->column('created_at', __('consultation.file.upload_time'))->display(function ($time) {
                    return $time ? date('Y-m-d H:i:s', strtotime($time)) : '-';
                });
                
                $grid->actions(function (Grid\Displayers\Actions $actions) {
                    $escapedPath = htmlspecialchars($this->file_path ?? '', ENT_QUOTES, 'UTF-8');
                    $downloadText = htmlspecialchars(__('consultation.file.download'), ENT_QUOTES, 'UTF-8');
                    $actions->append(
                        '<a href="/storage/' . $escapedPath . '" 
                            target="_blank" 
                            class="btn btn-xs btn-primary">
                            <i class="fa fa-download"></i> ' . $downloadText . '
                        </a>'
                    );
                });
                
                $grid->disableCreateButton();
                $grid->disableFilter();
                $grid->disableExport();
                $grid->disableBatchActions();
                
                return $grid;
            });

            $show->disableEditButton();
            $show->disableDeleteButton();
        });
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        return Form::make(new Consultation(), function (Form $form) {
            $form->display('id');
            $form->text('title', __('consultation.title'))->required();
            $form->textarea('description', __('consultation.description'))->required();
            
            $form->select('topic_type', __('consultation.topic_type'))->options(__('consultation.topic_types'))->required();
            
            $form->select('priority', __('consultation.priority'))->options(__('consultation.priorities'))->default('medium');
            
            $form->select('status', __('consultation.status'))->options(__('consultation.statuses'))->required();
            
            // 分配律师
            $form->select('assigned_lawyer_id', __('consultation.assigned_lawyer'))
                ->options(User::lawyers()->get()->pluck('full_name', 'id'))
                ->help(__('consultation.help.select_lawyer'));
            
            $form->display('created_at', __('consultation.created_at'));
            $form->display('updated_at', __('consultation.updated_at'));

            // 禁用删除和查看按钮
            $form->disableDeleteButton();
        });
    }

}

