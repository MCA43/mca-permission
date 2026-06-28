<?php

namespace Mca\Permission\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Mca\Permission\Services\PermissionService;
use Symfony\Component\HttpFoundation\Response;

class EnsureMcaRoot
{
    public function __construct(
        private readonly PermissionService $permissions,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->permissions->isRoot($request->user())) {
            abort(403, 'Bu alan yalnızca root kullanıcı içindir.');
        }

        return $next($request);
    }
}
