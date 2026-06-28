<?php

namespace Mca\Permission\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Mca\Permission\Support\McaPermissionLocale;
use Symfony\Component\HttpFoundation\Response;

class SetMcaPermissionLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        McaPermissionLocale::apply();

        return $next($request);
    }
}
