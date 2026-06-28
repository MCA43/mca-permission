<?php

namespace Mca\Permission\Services;

use Illuminate\Support\Str;

class DepartmentService
{
    public function paginated(int $perPage = 20)
    {
        return $this->model()::query()
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function allForSelect()
    {
        return $this->model()::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'slug', 'name']);
    }

    /** @return \Illuminate\Support\Collection<int|string, string> */
    public function nameMap()
    {
        return $this->model()::query()->pluck('name', 'id');
    }

    public function create(array $data): object
    {
        $slug = $data['slug'] ?? Str::slug($data['name']);

        return $this->model()::query()->create([
            'slug' => $slug,
            'name' => $data['name'],
            'is_active' => (bool) ($data['is_active'] ?? true),
        ]);
    }

    public function update(object $department, array $data): object
    {
        $department->update([
            'name' => $data['name'],
            'is_active' => (bool) ($data['is_active'] ?? $department->is_active),
        ]);

        return $department->fresh();
    }

    public function delete(object $department): bool
    {
        $userModel = config('permission.user_model');
        $column = config('permission.department.user_column', 'department_id');

        if (class_exists($userModel) && \Illuminate\Support\Facades\Schema::hasColumn((new $userModel)->getTable(), $column)) {
            $inUse = $userModel::query()->where($column, $department->getKey())->exists();
            if ($inUse) {
                return false;
            }
        }

        return (bool) $department->delete();
    }

    public function modelConfigured(): bool
    {
        $model = config('permission.department.model');

        return is_string($model) && $model !== '' && class_exists($model);
    }

    private function model(): string
    {
        $model = config('permission.department.model');
        if (! is_string($model) || $model === '' || ! class_exists($model)) {
            throw new \RuntimeException('Departman modeli tanımlı değil.');
        }

        return $model;
    }
}
