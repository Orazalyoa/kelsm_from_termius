<?php

namespace App\Admin\Controllers;

use App\Admin\Repositories\Announcement;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;

class AnnouncementController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new Announcement(), function (Grid $grid) {
            $grid->column('id')->sortable();
            $grid->column('title')->limit(50);
            $grid->column('thumbnail')->display(function ($thumbnail) {
                if (!$thumbnail) return '-';
                $url = asset('storage/' . $thumbnail);
                return "<img src='{$url}' style='max-width:60px;max-height:60px;border-radius:4px;object-fit:cover;' />";
            })->label(__('announcement.fields.thumbnail_label'));
            $grid->column('image')->display(function ($image) {
                if (!$image) return '-';
                $url = asset('storage/' . $image);
                return "<img src='{$url}' style='max-width:60px;max-height:60px;border-radius:4px;object-fit:cover;' />";
            })->label(__('announcement.fields.full_image_label'));
            $grid->column('order')->sortable()->editable();
            $grid->column('is_active')->switch();
            $grid->column('target_user_types')->display(function ($types) {
                if (!$types) return '<span class="label label-default">' . __('announcement.options.user_types.all') . '</span>';
                
                $userTypes = __('announcement.options.user_types');
                $labels = [
                    'company_admin' => '<span class="label label-primary">' . $userTypes['company_admin'] . '</span>',
                    'expert' => '<span class="label label-info">' . $userTypes['expert'] . '</span>',
                    'lawyer' => '<span class="label label-success">' . $userTypes['lawyer'] . '</span>',
                ];
                
                return collect($types)->map(fn($type) => $labels[$type] ?? $type)->join(' ');
            });
            $grid->column('start_date')->display(function ($time) {
                return $time ? date('Y-m-d H:i', strtotime($time)) : __('announcement.time.now');
            })->sortable();
            $grid->column('end_date')->display(function ($time) {
                return $time ? date('Y-m-d H:i', strtotime($time)) : __('announcement.time.never');
            })->sortable();
            $grid->column('created_at')->display(function ($time) {
                return date('Y-m-d H:i', strtotime($time));
            })->sortable();

            // 过滤器
            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('is_active')->select(__('announcement.options.status'));
                $filter->like('title');
                $filter->between('start_date')->datetime();
                $filter->between('end_date')->datetime();
            });

            // 默认排序
            $grid->model()->orderBy('order', 'asc')->orderBy('created_at', 'desc');

            // 批量操作
            $grid->batchActions(function ($batch) {
                $batch->add(new class(__('announcement.actions.batch_delete')) extends \Dcat\Admin\Grid\BatchAction {
                    public function handle()
                    {
                        \App\Models\Announcement::whereIn('id', $this->getKey())->delete();
                        return $this->response()->success(__('announcement.actions.deleted_success'))->refresh();
                    }
                });
                
                $batch->add(new class(__('announcement.actions.enable')) extends \Dcat\Admin\Grid\BatchAction {
                    public function handle()
                    {
                        \App\Models\Announcement::whereIn('id', $this->getKey())->update(['is_active' => true]);
                        return $this->response()->success(__('announcement.actions.enabled_success'))->refresh();
                    }
                });
                
                $batch->add(new class(__('announcement.actions.disable')) extends \Dcat\Admin\Grid\BatchAction {
                    public function handle()
                    {
                        \App\Models\Announcement::whereIn('id', $this->getKey())->update(['is_active' => false]);
                        return $this->response()->success(__('announcement.actions.disabled_success'))->refresh();
                    }
                });
            });

            // 快速搜索
            $grid->quickSearch(['title', 'description']);
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
        return Show::make($id, new Announcement(), function (Show $show) {
            $show->field('id');
            $show->field('title');
            $show->field('description');
            $show->field('thumbnail')->as(function ($thumbnail) {
                if (!$thumbnail) return '-';
                $url = asset('storage/' . $thumbnail);
                return "<img src='{$url}' style='max-width:400px;border-radius:8px;' />";
            })->unescape();
            $show->field('image')->as(function ($image) {
                if (!$image) return '-';
                $url = asset('storage/' . $image);
                return "<img src='{$url}' style='max-width:400px;border-radius:8px;' />";
            })->unescape();
            $show->field('link');
            $show->field('order');
            $show->field('is_active')->using(__('announcement.options.status'))->label([
                0 => 'danger',
                1 => 'success'
            ]);
            $show->field('target_user_types')->as(function ($types) {
                if (!$types) return __('announcement.options.user_types.all');
                $userTypes = __('announcement.options.user_types');
                return implode(', ', array_map(fn($type) => $userTypes[$type] ?? $type, $types));
            });
            $show->field('start_date');
            $show->field('end_date');
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
        return Form::make(new Announcement(), function (Form $form) {
            $form->display('id');
            
            $form->text('title')->required()->help(__('announcement.help.title'));
            $form->textarea('description')->help(__('announcement.help.description'));
            
            $form->image('thumbnail')
                ->autoUpload()
                ->uniqueName()
                ->removable()
                ->help(__('announcement.help.thumbnail'));
            
            $form->image('image')
                ->autoUpload()
                ->uniqueName()
                ->removable()
                ->help(__('announcement.help.image'));
            
            $form->url('link')->help(__('announcement.help.link'));
            
            $form->number('order')
                ->default(0)
                ->help(__('announcement.help.order'));
            
            $form->switch('is_active')
                ->default(1)
                ->help(__('announcement.help.is_active'));
            
            $form->multipleSelect('target_user_types', __('announcement.fields.target_user_types'))
                ->options(__('announcement.options.user_types'))
                ->help(__('announcement.help.target_users'));
            
            $form->datetime('start_date')
                ->help(__('announcement.help.start_date'));
            
            $form->datetime('end_date')
                ->help(__('announcement.help.end_date'));
        });
    }
}

