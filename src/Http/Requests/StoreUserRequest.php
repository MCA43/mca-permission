<?php

namespace Mca\Permission\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Mca\Permission\Models\Role;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $roleColumn = config('permission.user_role_column', 'role');
        $roleSlugs = Role::query()->where('is_active', true)->where('is_root', false)->pluck('slug')->all();

        $userModel = config('permission.user_model');
        $table = (new $userModel)->getTable();

        $rules = [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', Rule::unique($table, 'email')],
            'password' => ['required', 'string', Password::defaults()],
            $roleColumn => ['required', 'string', Rule::in($roleSlugs)],
            'is_active' => ['sometimes', 'boolean'],
        ];

        if ($this->userHasDepartmentField()) {
            $rules['department_id'] = ['nullable', 'integer', Rule::exists(config('permission.department.table', 'departments'), 'id')];
        }

        return $rules;
    }

    private function userHasDepartmentField(): bool
    {
        return \Mca\Permission\Support\PermissionMode::supportsDepartmentGrants()
            && \Illuminate\Support\Facades\Schema::hasColumn('users', config('permission.department.user_column', 'department_id'));
    }
}
