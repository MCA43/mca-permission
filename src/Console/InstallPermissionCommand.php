<?php

namespace Mca\Permission\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Mca\Permission\Database\Seeders\McaPackagePermissionSeeder;
use Mca\Permission\Database\Seeders\McaRoleSeeder;
use Mca\Permission\Support\McaPermissionLocale;
use Mca\Permission\Support\PermissionMode;
use Mca\Permission\Support\PermissionSchema;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'mca:permission:install')]
class InstallPermissionCommand extends Command
{
    protected $signature = 'mca:permission:install
                            {--mode= : basic, user or full}
                            {--upgrade : Upgrade mode; run missing migrations only}
                            {--seed : Seed default roles}
                            {--no-assets : Skip CSS/JS publish}';

    protected $description = 'Install MCA Permission (basic / user / full)';

    public function handle(): int
    {
        McaPermissionLocale::apply();

        $mode = $this->resolveMode();
        if ($mode === null) {
            return self::FAILURE;
        }

        $this->components->info(mca_perm('console.install.start', ['mode' => $mode]));
        $this->line('  '.PermissionMode::label($mode));

        $this->publishConfig($mode);
        $this->setEnvMode($mode);

        if (! $this->option('no-assets')) {
            $this->callSilent('vendor:publish', [
                '--tag' => 'mca-permission-assets',
                '--force' => true,
            ]);
            $this->components->task(mca_perm('console.install.assets_published'), fn () => true);
        }

        Artisan::call('migrate', ['--force' => true]);
        $this->output->write(Artisan::output());
        $this->components->task(mca_perm('console.install.migration_done'), fn () => true);

        if ($this->option('seed') || $this->confirm(mca_perm('console.install.seed_confirm'), true)) {
            $this->callSilent('db:seed', ['--class' => McaRoleSeeder::class, '--force' => true]);
            $this->callSilent('db:seed', ['--class' => McaPackagePermissionSeeder::class, '--force' => true]);
            $this->components->task(mca_perm('console.install.seeder_done'), fn () => true);
        }

        $missing = PermissionSchema::missingTablesForMode($mode);
        if ($missing !== []) {
            $this->components->warn(mca_perm('console.install.missing_tables', [
                'tables' => implode(', ', $missing),
            ]));
            $this->line('  '.mca_perm('console.install.missing_tables_hint'));
        }

        $exclusiveMissing = PermissionSchema::missingExclusiveSetupForMode($mode);
        if ($exclusiveMissing !== []) {
            $this->components->warn(mca_perm('console.install.missing_exclusive', [
                'columns' => implode(', ', $exclusiveMissing),
            ]));
            $this->line('  '.mca_perm('console.install.missing_exclusive_hint'));
        }

        if ($mode === PermissionMode::FULL && ! PermissionSchema::departmentTableExists()) {
            $this->components->warn(mca_perm('console.install.dept_table_missing'));
            $this->line('  '.mca_perm('console.install.dept_stub_hint'));
            $this->line('  '.mca_perm('console.install.dept_migrate_hint'));
            $this->line('  '.mca_perm('console.install.dept_config_hint'));
        }

        $this->newLine();
        $this->components->info(mca_perm('console.install.done'));
        $this->line('  '.mca_perm('console.install.web_ui', [
            'prefix' => config('permission.routes.web.prefix', 'mca/permission'),
        ]));
        $this->line('  '.mca_perm('console.install.run_doctor'));

        return self::SUCCESS;
    }

    private function resolveMode(): ?string
    {
        $mode = $this->option('mode');

        if ($mode !== null) {
            if (! PermissionMode::isValid($mode)) {
                $this->components->error(mca_perm('console.install.invalid_mode'));

                return null;
            }

            return $mode;
        }

        if ($this->option('no-interaction')) {
            return PermissionMode::current();
        }

        return $this->choice(
            mca_perm('console.install.mode_choice'),
            [
                PermissionMode::BASIC => PermissionMode::label(PermissionMode::BASIC),
                PermissionMode::USER => PermissionMode::label(PermissionMode::USER),
                PermissionMode::FULL => PermissionMode::label(PermissionMode::FULL),
            ],
            PermissionMode::BASIC,
        );
    }

    private function publishConfig(string $mode): void
    {
        $configPath = config_path('permission.php');

        if (! file_exists($configPath)) {
            $this->callSilent('vendor:publish', ['--tag' => 'mca-permission-config']);
        }

        if (file_exists($configPath)) {
            $content = file_get_contents($configPath);
            if (is_string($content) && preg_match("/'mode'\s*=>\s*env\([^)]+\)/", $content)) {
                $content = preg_replace(
                    "/'mode'\s*=>\s*env\([^)]+\)/",
                    "'mode' => env('MCA_PERMISSION_MODE', '{$mode}')",
                    $content,
                );
                file_put_contents($configPath, $content);
            }
        }

        $this->components->task(mca_perm('console.install.config_ready', ['mode' => $mode]), fn () => true);
    }

    private function setEnvMode(string $mode): void
    {
        $path = base_path('.env');
        if (! file_exists($path)) {
            return;
        }

        $content = file_get_contents($path);
        if (! is_string($content)) {
            return;
        }

        $line = 'MCA_PERMISSION_MODE='.$mode;
        if (preg_match('/^MCA_PERMISSION_MODE=.*/m', $content)) {
            $content = preg_replace('/^MCA_PERMISSION_MODE=.*/m', $line, $content);
        } else {
            $content = rtrim($content).PHP_EOL.$line.PHP_EOL;
        }

        file_put_contents($path, $content);
        $this->callSilent('config:clear');
    }
}
