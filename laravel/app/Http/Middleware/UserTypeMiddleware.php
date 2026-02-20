<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class UserTypeMiddleware
{
    /**
     * Handle an incoming request.
     * 
     * This middleware restricts access based on user type.
     * Usage: Route::middleware('user.type:company_admin,expert')
     * 
     * User Types:
     * - company_admin: Full organization management access
     * - expert: Limited organization member access
     * - lawyer: No organization management access
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @param  string  ...$types Allowed user types (e.g., 'company_admin', 'expert', 'lawyer')
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next, ...$types)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }
        
        if (!in_array($user->user_type, $types)) {
            return response()->json(['error' => 'Forbidden - Insufficient permissions'], 403);
        }
        
        return $next($request);
    }
}
