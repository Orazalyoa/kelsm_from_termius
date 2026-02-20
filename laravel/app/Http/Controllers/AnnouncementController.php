<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    /**
     * Get active announcements for current user.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        $announcements = Announcement::active()
            ->forUserType($user->user_type)
            ->ordered()
            ->get();

        return response()->json([
            'code' => 0,
            'message' => 'success',
            'data' => $announcements
        ]);
    }

    /**
     * Get specific announcement.
     */
    public function show($id)
    {
        $announcement = Announcement::findOrFail($id);

        return response()->json([
            'code' => 0,
            'message' => 'success',
            'data' => $announcement
        ]);
    }
}

