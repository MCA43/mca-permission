<?php

namespace Mca\Permission\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Mca\Permission\Services\PackageAccessService;
use Symfony\Component\HttpFoundation\Response;

class EnsureMcaPackageAccess
{
    public function __construct(
        private readonly PackageAccessService $packages,
    ) {}

    public function handle(Request $request, Closure $next, string $package, ?string $ability = null): Response
    {
        $user = $request->user();
        if ($user === null) {
            abort(403);
        }

        $ability ??= $this->packages->abilityForRequest($request);

        if (! $this->packages->allows($user, $package, $ability)) {
            abort(403, 'Bu MCA paketi için yetkiniz yok.');
        }

        if ($package === 'settings' && $ability === 'manage') {
            $group = (string) $request->input('group', $request->query('group', ''));
            if ($group !== '' && ! $this->packages->allowsSettingsGroup($user, $group)) {
                abort(403, 'Bu ayar grubu için yetkiniz yok.');
            }
        }

        return $next($request);
    }
}
