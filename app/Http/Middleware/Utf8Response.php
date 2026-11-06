<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Utf8Response
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        
        // Only modify text/html responses
        if ($response->headers->get('Content-Type') === 'text/html; charset=UTF-8' || 
            str_contains($response->headers->get('Content-Type', ''), 'text/html')) {
            $response->header('Content-Type', 'text/html; charset=UTF-8');
            
            // For HTML responses, ensure the meta charset is present
            $content = $response->getContent();
            if (strpos($content, '<meta charset="UTF-8"') === false && 
                strpos($content, '<meta http-equiv="Content-Type"') === false) {
                $content = str_replace(
                    '<head>',
                    '<head>\n    <meta charset="UTF-8">\n    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">',
                    $content
                );
                $response->setContent($content);
            }
        }
        
        return $response;
    }
}
