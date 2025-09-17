<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyLmwApiToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $apiToken = $request->header('X-LMW-API-KEY');

        if ($apiToken !== config('lmw.api_token')) {
            return response()->json(['message' => 'Unauthorized LMW API key.'], 401);
        }

        return $next($request);
    }
}
