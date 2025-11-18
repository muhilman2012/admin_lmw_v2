<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiTrafficGuard
{
    public function handle(Request $request, Closure $next): Response
    {
        if (env('IS_API_SERVER')) {
            $path = $request->path();

            if (!Str::startsWith($path, 'api/')) {
                if ($path === '/') {
                    return response()->view('api.welcome_api');
                }

                return response()->json([
                    'status' => 403, 
                    'message' => 'Forbidden. This server is configured for API access only.'
                ], 403);
            }
        }
        return $next($request);
    }
}
