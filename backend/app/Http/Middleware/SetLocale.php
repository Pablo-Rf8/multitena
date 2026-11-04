<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $default = config('locales.default');
        $locale = $request->session()->get('locale', $default);
        app()->setLocale(is_string($locale) && array_key_exists($locale, config('locales.available')) ? $locale : $default);

        return $next($request);
    }
}
