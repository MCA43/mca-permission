<?php

namespace Mca\Permission\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Mca\Permission\Models\Permission;
use Mca\Permission\Services\PermissionService;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    public function __construct(
        private readonly PermissionService $permissions,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        if (! $user) {
            abort(403, 'Giriş yapmalısınız.');
        }

        if ($this->permissions->isRoot($user)) {
            return $next($request);
        }

        $resolved = $this->permissions->resolveFromControllerClass(
            $request->route()?->getControllerClass(),
            $request->route()?->getActionMethod() ?? 'index',
        );

        if ($resolved === null) {
            return $next($request);
        }

        $permission = Permission::query()
            ->forRoute($resolved['folder'], $resolved['controller'], $resolved['method'])
            ->first();

        if (! $permission) {
            abort(403, 'Bu işlem için izin tanımı bulunamadı.');
        }

        if ($permission->is_root_only) {
            abort(403, 'Bu işlem yalnızca root kullanıcı içindir.');
        }

        if (! $this->permissions->canAccess(
            $user,
            $resolved['folder'],
            $resolved['controller'],
            $resolved['method'],
        )) {
            abort(403, 'Bu işlem için yetkiniz yok.');
        }

        return $next($request);
    }
}
