<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\ApiSetting;

class VerifyLmwApiToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $apiToken = $request->header('X-LMW-API-KEY');

        $dbToken = ApiSetting::where('name', 'lmw_api')
                            ->where('key', 'api_token')
                            ->value('value'); 

        if (empty($dbToken) || $apiToken !== $dbToken) {
            return response()->json(['message' => 'Unauthorized LMW API key.'], 401);
        }

        return $next($request);
    }
}
