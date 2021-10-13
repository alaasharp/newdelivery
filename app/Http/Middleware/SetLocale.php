<?php

namespace App\Http\Middleware;

use Closure;

class SetLocale
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $lang = $request->input('setlocale');
        if ($lang == null)
            $lang = $request->session()->get('lang');
        else
            $request->session()->put('lang', $lang);
        if ($lang != null)
            \App::setLocale($lang);
        return $next($request);
    }
}
