<?php

namespace Mca\Permission\Models;

use Illuminate\Database\Eloquent\Model;

class ScanSegment extends Model
{
    protected $table = 'permission_scan_segments';

    protected $fillable = [
        'folder',
        'path',
        'namespace',
        'is_active',
        'from_config',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'from_config' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /** @return array{folder: string, path: string, namespace: string} */
    public function toScanArray(): array
    {
        return [
            'folder' => $this->folder,
            'path' => $this->path,
            'namespace' => rtrim($this->namespace, '\\'),
        ];
    }
}
