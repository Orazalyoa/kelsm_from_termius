<?php

namespace App\Admin\Controllers;

use Dcat\Admin\Http\Controllers\AdminController;
use Dcat\Admin\Layout\Content;
use Dcat\Admin\Layout\Row;
use Dcat\Admin\Layout\Column;
use Dcat\Admin\Widgets\Card;
use Dcat\Admin\Widgets\Alert;
use App\Models\User;
use App\Models\Organization;
use App\Models\InviteCode;
use App\Models\Profession;

class DashboardController extends AdminController
{
    public function index(Content $content)
    {
        return $content
            ->header('Dashboard')
            ->description('System Overview')
            ->body(function (Row $row) {
                // 用户统计
                $row->column(3, function (Column $column) {
                    $totalUsers = User::count();
                    $activeUsers = User::where('status', 'active')->count();
                    
                    $column->row(
                        Card::make()
                            ->withHeader()
                            ->header('Total Users')
                            ->content($totalUsers)
                            ->icon('fa-users')
                    );
                    
                    $column->row(
                        Card::make()
                            ->withHeader()
                            ->header('Active Users')
                            ->content($activeUsers)
                            ->icon('fa-user-check')
                    );
                });

                // 用户类型统计
                $row->column(3, function (Column $column) {
                    $companyAdmins = User::where('user_type', 'company_admin')->count();
                    $experts = User::where('user_type', 'expert')->count();
                    $lawyers = User::where('user_type', 'lawyer')->count();
                    
                    $column->row(
                        Card::make()
                            ->withHeader()
                            ->header('Company Admins')
                            ->content($companyAdmins)
                            ->icon('fa-user-tie')
                    );
                    
                    $column->row(
                        Card::make()
                            ->withHeader()
                            ->header('Experts')
                            ->content($experts)
                            ->icon('fa-user-graduate')
                    );
                    
                    $column->row(
                        Card::make()
                            ->withHeader()
                            ->header('Lawyers')
                            ->content($lawyers)
                            ->icon('fa-gavel')
                    );
                });

                // 组织统计
                $row->column(3, function (Column $column) {
                    $totalOrgs = Organization::count();
                    $activeOrgs = Organization::where('status', 'active')->count();
                    
                    $column->row(
                        Card::make()
                            ->withHeader()
                            ->header('Total Organizations')
                            ->content($totalOrgs)
                            ->icon('fa-building')
                    );
                    
                    $column->row(
                        Card::make()
                            ->withHeader()
                            ->header('Active Organizations')
                            ->content($activeOrgs)
                            ->icon('fa-building')
                    );
                });

                // 邀请码统计
                $row->column(3, function (Column $column) {
                    $totalCodes = InviteCode::count();
                    $activeCodes = InviteCode::where('status', 'active')->count();
                    $usedCodes = InviteCode::where('used_count', '>', 0)->count();
                    
                    $column->row(
                        Card::make()
                            ->withHeader()
                            ->header('Total Invite Codes')
                            ->content($totalCodes)
                            ->icon('fa-ticket-alt')
                    );
                    
                    $column->row(
                        Card::make()
                            ->withHeader()
                            ->header('Active Codes')
                            ->content($activeCodes)
                            ->icon('fa-ticket-alt')
                    );
                    
                    $column->row(
                        Card::make()
                            ->withHeader()
                            ->header('Used Codes')
                            ->content($usedCodes)
                            ->icon('fa-ticket-alt')
                    );
                });
            })
            ->body(function (Row $row) {
                // 最近活动
                $row->column(12, function (Column $column) {
                    $recentUsers = User::latest()->take(5)->get();
                    $recentOrgs = Organization::latest()->take(5)->get();
                    
                    $userList = $recentUsers->map(function ($user) {
                        return $user->first_name . ' ' . $user->last_name . ' (' . $user->user_type . ') - ' . $user->created_at->diffForHumans();
                    })->join('<br>');
                    
                    $orgList = $recentOrgs->map(function ($org) {
                        return $org->name . ' - ' . $org->created_at->diffForHumans();
                    })->join('<br>');
                    
                    $column->row(
                        Card::make()
                            ->withHeader()
                            ->header('Recent Users')
                            ->content($userList ?: 'No recent users')
                            ->icon('fa-users')
                    );
                    
                    $column->row(
                        Card::make()
                            ->withHeader()
                            ->header('Recent Organizations')
                            ->content($orgList ?: 'No recent organizations')
                            ->icon('fa-building')
                    );
                });
            });
    }
}
