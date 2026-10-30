<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CanonicalProductionUrl
{
    public function handle(Request $request, Closure $next): Response
    {
        $url = parse_url((string) config('app.url'));
        if (app()->environment('production') && ($url['scheme'] ?? null) === 'https' && ! empty($url['host'])) {
            $host = $url['host'].(isset($url['port']) ? ':'.$url['port'] : '');
            $request->server->set('HTTPS', 'on');
            $request->server->set('SERVER_PORT', (string) ($url['port'] ?? 443));
            $request->server->set('HTTP_HOST', $host);
            $request->headers->set('host', $host);

            $referer = parse_url((string) $request->headers->get('referer'));
            if ($referer && (($referer['scheme'] ?? null) !== 'https' || ($referer['host'] ?? null) !== $url['host'])) {
                $request->headers->remove('referer');
            }
        }

        return $next($request);
    }
}
