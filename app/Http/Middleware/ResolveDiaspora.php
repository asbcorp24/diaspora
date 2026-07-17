<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class ResolveDiaspora
{
    public function handle(Request $request, Closure $next): Response
    {
        $host = strtolower(preg_replace('/:\d+$/', '', $request->getHost()));
        $diaspora = Cache::remember('diaspora-domain:'.$host, now()->addMinutes(10), function () use ($host) {
            return DB::table('diaspora_domains')
                ->join('diasporas', 'diasporas.id', '=', 'diaspora_domains.diaspora_id')
                ->where('diaspora_domains.domain', $host)
                ->where('diaspora_domains.is_active', true)
                ->where('diasporas.is_active', true)
                ->select('diasporas.*')
                ->first();
        });

        if (!$diaspora && App::environment('local')) {
            $diaspora = DB::table('diasporas')->where('is_active', true)->orderBy('id')->first();
        }

        abort_unless($diaspora, 404, 'Для этого домена диаспора не настроена.');

        $diaspora->supported_locales = json_decode($diaspora->supported_locales, true) ?: ['ru'];
        $diaspora->theme = json_decode($diaspora->theme ?: '{}', true) ?: [];

        App::instance('currentDiaspora', $diaspora);
        view()->share('currentDiaspora', $diaspora);

        $requestedLocale = $request->query('lang');
        if ($requestedLocale && in_array($requestedLocale, $diaspora->supported_locales, true)) {
            $request->session()->put('locale', $requestedLocale);
        }

        $locale = $request->session()->get('locale', $diaspora->default_locale);
        if (!in_array($locale, $diaspora->supported_locales, true)) {
            $locale = $diaspora->default_locale;
        }

        App::setLocale($locale);

        return $next($request);
    }
}
