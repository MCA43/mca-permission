<?php

namespace Mca\Permission\Http\Requests;

use Mca\Permission\Models\Permission;
use Mca\Permission\Services\PermissionService;

class UpdatePermissionRequest extends StorePermissionRequest
{
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            /** @var Permission|null $permission */
            $permission = $this->route('permission');
            if (! $permission) {
                return;
            }

            $meta = app(PermissionService::class)->buildPermissionMeta(
                (string) $this->input('folder'),
                (string) $this->input('controller'),
                (string) $this->input('method'),
            );

            $exists = Permission::query()
                ->where('name', $meta['name'])
                ->whereKeyNot($permission->getKey())
                ->exists();

            if ($exists) {
                $validator->errors()->add('name', mca_perm('permissions.name_exists', ['name' => $meta['name']]));
            }
        });
    }
}
