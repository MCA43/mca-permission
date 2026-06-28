<?php

namespace Mca\Permission\Services;

use Mca\Permission\Contracts\GrantResolver;
use Mca\Permission\Grants\DepartmentGrantResolver;
use Mca\Permission\Grants\RoleGrantResolver;
use Mca\Permission\Grants\UserGrantResolver;
use Mca\Permission\Support\PermissionGrantContext;
use Mca\Permission\Support\PermissionMode;
use Illuminate\Contracts\Auth\Authenticatable;

class GrantResolverRegistry
{
    /** @var list<GrantResolver>|null */
    private ?array $defaultResolvers = null;

    /** @return list<GrantResolver> */
    public function resolvers(?Authenticatable $user = null): array
    {
        if ($user !== null) {
            return $this->resolversFor($user);
        }

        if ($this->defaultResolvers !== null) {
            return $this->defaultResolvers;
        }

        return $this->defaultResolvers = $this->buildDefaultChain();
    }

    /** @return list<GrantResolver> */
    public function resolversFor(Authenticatable $user): array
    {
        if (PermissionGrantContext::isUserExclusive($user)) {
            return PermissionMode::supportsUserGrants()
                ? [app(UserGrantResolver::class)]
                : [app(RoleGrantResolver::class)];
        }

        $chain = $this->buildDefaultChain(skipRole: $this->shouldSkipRoleForUser($user));

        return $chain;
    }

    private function shouldSkipRoleForUser(Authenticatable $user): bool
    {
        if (! PermissionMode::supportsDepartmentGrants()) {
            return false;
        }

        $deptId = PermissionGrantContext::userDepartmentId($user);
        if ($deptId === null) {
            return false;
        }

        $model = config('permission.department.model');
        if (! is_string($model) || ! class_exists($model)) {
            return false;
        }

        $department = $model::query()->find($deptId);

        return PermissionGrantContext::isDepartmentExclusive($department);
    }

    /** @return list<GrantResolver> */
    private function buildDefaultChain(bool $skipRole = false): array
    {
        $chain = [];

        if (PermissionMode::supportsUserGrants()) {
            $chain[] = app(UserGrantResolver::class);
        }

        if (PermissionMode::supportsDepartmentGrants()) {
            $chain[] = app(DepartmentGrantResolver::class);
        }

        if (! $skipRole) {
            $chain[] = app(RoleGrantResolver::class);
        }

        return $chain;
    }

    public function flush(): void
    {
        $this->defaultResolvers = null;
    }
}
