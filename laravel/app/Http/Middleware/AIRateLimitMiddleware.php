<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AIRateLimitMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  int  $maxAttempts  Maximum attempts per time window
     * @param  int  $decayMinutes  Time window in minutes
     * @return mixed
     */
    public function handle(Request $request, Closure $next, $maxAttempts = 10, $decayMinutes = 1)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated',
            ], 401);
        }

        $key = $this->resolveRequestSignature($request, $user->id);
        
        $attempts = Cache::get($key, 0);
        
        if ($attempts >= $maxAttempts) {
            Log::warning('AI API rate limit exceeded', [
                'user_id' => $user->id,
                'endpoint' => $request->path(),
                'attempts' => $attempts,
            ]);
            
            return response()->json([
                'message' => 'Too many AI requests. Please try again later.',
                'retry_after' => $decayMinutes * 60,
            ], 429);
        }

        // Increment attempts
        if ($attempts === 0) {
            Cache::put($key, 1, now()->addMinutes($decayMinutes));
        } else {
            Cache::increment($key);
        }

        return $next($request);
    }

    /**
     * Resolve request signature.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $userId
     * @return string
     */
    protected function resolveRequestSignature(Request $request, $userId)
    {
        return 'ai_ratelimit:' . $userId . ':' . $request->path();
    }
}

