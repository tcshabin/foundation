<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ValidateAppKey
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if ($request->hasHeader('x-app-key')) {
            if ($request->header('x-app-key') !== config('app.x_app_key')) {
                return response()->json([
                    'error' => 'Invalid App Key',
                ], 403);
            }

            // Add a flag to indicate app request
            $request->attributes->set('is_app_request', true);
        }

        return $next($request);
    }
}
