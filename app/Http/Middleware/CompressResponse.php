<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CompressResponse
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! method_exists($response, 'header')) {
            return $response;
        }

        $content = $response->getContent();

        if ($content === '' || $content === false) {
            return $response;
        }

        $accept = (string) $request->headers->get('Accept-Encoding', '');

        if (! str_contains(strtolower($accept), 'gzip')) {
            return $response;
        }

        if (! function_exists('gzencode')) {
            return $response;
        }

        if (strlen($content) < 860) {
            return $response;
        }

        if ($response->headers->has('Content-Encoding')) {
            return $response;
        }

        $compressed = gzencode($content, 6);

        if ($compressed === false) {
            return $response;
        }

        $response->setContent($compressed);
        $response->headers->set('Content-Encoding', 'gzip');
        $response->headers->set('Content-Length', (string) strlen($compressed));
        $response->headers->set('Vary', 'Accept-Encoding');

        return $response;
    }
}
