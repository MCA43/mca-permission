<?php

namespace Mca\Permission\Console;

use Illuminate\Console\Command;
use Mca\Permission\Services\PackageAccessService;

class SyncMcaPackagePermissionsCommand extends Command
{
    protected $signature = 'mca:permission:sync-packages';

    protected $description = 'MCA paket / bölüm izin tanımlarını senkronize eder';

    public function handle(PackageAccessService $packages): int
    {
        $result = $packages->syncDefinitions();

        $this->info(sprintf(
            'MCA paket izinleri senkronize edildi (%d yeni, %d güncellendi).',
            $result['created'],
            $result['updated'],
        ));

        return self::SUCCESS;
    }
}
