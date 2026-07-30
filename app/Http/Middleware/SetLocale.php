<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = 'ru'; // Default locale

        // 1. Accept-Language header check
        if ($request->hasHeader('Accept-Language')) {
            $headerLocale = strtolower($request->header('Accept-Language'));
            if (in_array($headerLocale, ['ru', 'kk'])) {
                $locale = $headerLocale;
            }
        }
        // 2. Check authenticated user's language setting
        elseif ($request->user() && in_array($request->user()->language, ['ru', 'kk'])) {
            $locale = $request->user()->language;
        }
        // 3. Session preference check
        elseif (session()->has('locale') && in_array(session()->get('locale'), ['ru', 'kk'])) {
            $locale = session()->get('locale');
        }

        App::setLocale($locale);

        return $next($request);
    }
}
