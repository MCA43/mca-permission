<?php

namespace Mca\Permission\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Mca\Permission\Models\Permission;
use Mca\Permission\Services\PermissionService;

class StorePermissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return $this->fieldRules();
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $meta = app(PermissionService::class)->buildPermissionMeta(
                (string) $this->input('folder'),
                (string) $this->input('controller'),
                (string) $this->input('method'),
            );

            if (Permission::query()->where('name', $meta['name'])->exists()) {
                $validator->errors()->add('name', mca_perm('permissions.name_exists', ['name' => $meta['name']]));
            }
        });
    }

    /** @return array<string, mixed> */
    protected function fieldRules(): array
    {
        return [
            'folder' => ['required', 'string', 'max:64'],
            'controller' => ['required', 'string', 'max:128'],
            'method' => ['required', 'string', 'max:64', 'regex:/^[a-zA-Z][a-zA-Z0-9_]*$/'],
            'module_description' => ['nullable', 'string', 'max:255'],
            'method_description' => ['nullable', 'string', 'max:255'],
            'is_root_only' => ['sometimes', 'boolean'],
        ];
    }
}
