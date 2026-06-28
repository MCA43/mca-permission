<?php

namespace Mca\Permission\Console;

use Illuminate\Console\Command;
use Mca\Permission\Support\McaPermissionLocale;
use Mca\Permission\Support\PermissionGrantContext;
use Mca\Permission\Support\PermissionMode;
use Mca\Permission\Support\PermissionSchema;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'mca:permission:doctor')]
class DoctorPermissionCommand extends Command
{
    protected $signature = 'mca:permission:doctor';

    protected $description = 'Check MCA Permission mode, tables and config';

    public function handle(): int
    {
        McaPermissionLocale::apply();

        $mode = PermissionMode::current();
        $this->components->info(mca_perm('console.doctor.mode_info', [
            'mode' => $mode,
            'label' => PermissionMode::label($mode),
        ]));

        $hasFailure = false;

        $missing = PermissionSchema::missingTablesForMode($mode);
        if ($missing === []) {
            $this->components->task(mca_perm('console.doctor.tables_ok'), fn () => true);
        } else {
            $hasFailure = true;
            $this->components->task(mca_perm('console.doctor.tables_fail'), fn () => false);
            foreach ($missing as $table) {
                $this->line('  ✗ '.$table);
            }
            $this->line('  '.mca_perm('console.doctor.tables_fix', ['mode' => $mode]));
        }

        $exclusiveMissing = PermissionSchema::missingExclusiveSetupForMode($mode);
        if ($exclusiveMissing === []) {
            if (in_array($mode, [PermissionMode::USER, PermissionMode::FULL], true)) {
                $this->components->task(mca_perm('console.doctor.exclusive_ok'), fn () => true);
            }
        } else {
            $this->components->warn(mca_perm('console.doctor.exclusive_missing'));
            foreach ($exclusiveMissing as $column) {
                $this->line('  ✗ '.$column);
            }
            $this->line('  '.mca_perm('console.doctor.exclusive_fix'));
            $this->line('  '.mca_perm('console.doctor.exclusive_fillable', [
                'column' => PermissionGrantContext::userExclusiveColumn(),
            ]));
        }

        if (PermissionMode::supportsDepartmentGrants()) {
            $deptModel = config('permission.department.model');
            if ($deptModel === null || $deptModel === '') {
                $this->components->warn(mca_perm('console.doctor.dept_model_missing'));
            } elseif (! class_exists($deptModel)) {
                $this->components->warn(mca_perm('console.doctor.dept_class_missing', ['class' => $deptModel]));
            } else {
                $this->components->task(mca_perm('console.doctor.dept_model_ok', ['class' => $deptModel]), fn () => true);
            }

            if (! PermissionSchema::departmentTableExists()) {
                $this->components->warn(mca_perm('console.doctor.dept_table_warn'));
                $this->line('  '.mca_perm('console.doctor.dept_table_fix'));
            }
        }

        if (PermissionMode::supportsUserGrants() && ! PermissionSchema::hasUserPermissionTable()) {
            $this->components->warn(mca_perm('console.doctor.user_table_warn'));
        }

        $this->newLine();
        $this->line(mca_perm('console.doctor.grant_chain', ['chain' => $this->grantChainDescription()]));

        return $hasFailure ? self::FAILURE : self::SUCCESS;
    }

    private function grantChainDescription(): string
    {
        $parts = [];
        if (PermissionMode::supportsUserGrants()) {
            $parts[] = mca_perm('console.doctor.grant_user');
        }
        if (PermissionMode::supportsDepartmentGrants()) {
            $parts[] = mca_perm('console.doctor.grant_department');
        }
        $parts[] = mca_perm('console.doctor.grant_role');

        return implode(' → ', $parts);
    }
}
