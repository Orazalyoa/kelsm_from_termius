<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProfessionResource;
use App\Models\Profession;
use Illuminate\Http\Request;

class ProfessionController extends Controller
{
    /**
     * Get all professions
     */
    public function index(Request $request)
    {
        $locale = $request->get('locale', 'ru');
        $userType = $request->get('user_type');
        
        $query = Profession::active();
        
        if ($userType === 'expert') {
            $query->forExperts();
        } elseif ($userType === 'lawyer') {
            $query->forLawyers();
        }
        
        $professions = $query->get();
        
        return response()->json([
            'professions' => ProfessionResource::collection($professions)
        ]);
    }
}