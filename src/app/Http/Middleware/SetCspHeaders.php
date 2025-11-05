<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetCspHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $csp = "default-src 'self'; ";
        $csp .= "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://*.setneg.go.id; ";
        
        $csp .= "img-src 'self' https://*.setneg.go.id https://ui-avatars.com data: http://localhost:9000; ";
        
        $csp .= "connect-src 'self' data: https://*.setneg.goid http://localhost:9000; ";
        
        $csp .= "style-src 'self' 'unsafe-inline' https://*.setneg.go.id https://fonts.bunny.net; ";
        $csp .= "font-src 'self' https://*.setneg.go.id https://fonts.bunny.net; ";
        $csp .= "frame-src 'self' https://*.setneg.go.id; ";
        $csp .= "object-src 'self'; ";
        $csp .= "worker-src blob:;";

        $response->headers->set('Content-Security-Policy', $csp, false);
        
        $response->headers->set('X-Content-Security-Policy', $csp, false);

        $response->headers->set('X-Content-Type-Options', 'nosniff', false);
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN', false);
        $response->headers->set('X-XSS-Protection', '1; mode=block', false);
        
        return $response;
    }
}