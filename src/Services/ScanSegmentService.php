<?php

namespace Mca\Permission\Services;

use Illuminate\Support\Collection;
use Mca\Permission\Models\ScanSegment;

class ScanSegmentService
{
    /** @return Collection<int, ScanSegment> */
    public function allOrdered(): Collection
    {
        return ScanSegment::query()
            ->orderBy('sort_order')
            ->orderBy('folder')
            ->get();
    }

    /** @return list<array{folder: string, path: string, namespace: string}> */
    public function activeSegments(): array
    {
        if (! $this->tableExists()) {
            return $this->configSegments();
        }

        $this->ensureConfigSegments();

        return ScanSegment::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('folder')
            ->get()
            ->map(fn (ScanSegment $segment) => $segment->toScanArray())
            ->all();
    }

    public function create(array $data): ScanSegment
    {
        $sort = (int) ScanSegment::query()->max('sort_order');

        return ScanSegment::query()->create([
            'folder' => $data['folder'],
            'path' => $this->normalizePath($data['path']),
            'namespace' => rtrim($data['namespace'], '\\'),
            'is_active' => (bool) ($data['is_active'] ?? true),
            'from_config' => false,
            'sort_order' => $sort + 10,
        ]);
    }

    public function update(ScanSegment $segment, array $data): ScanSegment
    {
        $segment->update([
            'folder' => $data['folder'] ?? $segment->folder,
            'path' => isset($data['path']) ? $this->normalizePath($data['path']) : $segment->path,
            'namespace' => isset($data['namespace']) ? rtrim($data['namespace'], '\\') : $segment->namespace,
            'is_active' => array_key_exists('is_active', $data) ? (bool) $data['is_active'] : $segment->is_active,
        ]);

        return $segment->fresh();
    }

    public function delete(ScanSegment $segment): bool
    {
        return (bool) $segment->delete();
    }

    public function ensureConfigSegments(): void
    {
        if (! $this->tableExists()) {
            return;
        }

        $sort = 0;
        foreach ($this->configSegments() as $segment) {
            $sort += 10;
            ScanSegment::query()->updateOrCreate(
                [
                    'path' => $this->normalizePath($segment['path']),
                    'namespace' => rtrim($segment['namespace'], '\\'),
                ],
                [
                    'folder' => $segment['folder'],
                    'is_active' => true,
                    'from_config' => true,
                    'sort_order' => $sort,
                ],
            );
        }
    }

    /** @return list<array{folder: string, path: string, namespace: string}> */
    private function configSegments(): array
    {
        $segments = config('permission.scan.segments', []);

        return array_values(array_map(function (array $segment) {
            return [
                'folder' => (string) ($segment['folder'] ?? 'Panel'),
                'path' => $this->normalizePath((string) ($segment['path'] ?? '')),
                'namespace' => rtrim((string) ($segment['namespace'] ?? ''), '\\'),
            ];
        }, $segments));
    }

    private function normalizePath(string $path): string
    {
        return trim(str_replace('\\', '/', $path), '/');
    }

    private function tableExists(): bool
    {
        return \Illuminate\Support\Facades\Schema::hasTable('permission_scan_segments');
    }
}
