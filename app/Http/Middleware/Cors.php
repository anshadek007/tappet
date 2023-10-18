<?php

namespace App\Http\Middleware;

use Closure;

class Cors
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
{
    $response = $next($request);

    $response->header('Access-Control-Allow-Origin', '*');
    $response->header('Access-Control-Allow-Methods', implode(',', config('cors.allowed_methods')));
    $response->header('Access-Control-Allow-Headers', implode(',', config('cors.allowed_headers')));
    $response->header('Access-Control-Allow-Credentials', config('cors.supports_credentials'));

    return $response;
}
}