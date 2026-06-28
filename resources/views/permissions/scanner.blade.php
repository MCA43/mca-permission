@extends('mca-permission::layouts.app')

@section('title', mca_perm('scanner.title').' — '.($mcaPermTitle ?? mca_perm('app.title')))

@section('content')
    @php
        $np = config('permission.routes.name_prefix');
        $scannerI18n = [
            'no_missing' => mca_perm('scanner.no_missing'),
            'empty_dash' => mca_perm('common.empty_dash'),
            'scanning' => mca_perm('scanner.scanning'),
            'scan_error' => mca_perm('scanner.scan_error'),
            'syncing' => mca_perm('scanner.syncing'),
            'sync_error' => mca_perm('scanner.sync_error'),
            'status_counts' => mca_perm('scanner.status_counts'),
            'sync_result' => mca_perm('scanner.sync_result'),
            'labels_updated' => mca_perm('scanner.labels_updated'),
        ];
    @endphp

    <div class="mca-perm-toolbar">
        <div>
            <h1 class="mca-perm-toolbar__title">{{ mca_perm('scanner.title') }}</h1>
            <p class="mca-perm-toolbar__subtitle">{{ mca_perm('scanner.subtitle') }}</p>
        </div>
        <div class="mca-perm-toolbar__actions">
            <a href="{{ route($np.'index') }}" class="mca-perm-btn mca-perm-btn--secondary">
                @include('mca-permission::partials.icon', ['name' => 'key', 'class' => 'mca-ui-icon mca-ui-icon--sm'])
                {{ mca_perm('common.permission_list') }}
            </a>
        </div>
    </div>

    <div id="mcaPermScannerRoot"
         class="mca-perm-card mca-perm-card__body"
         data-scan-url="{{ $apiScanUrl }}"
         data-bulk-url="{{ $apiBulkUrl }}"
         data-sync-labels-url="{{ $apiSyncLabelsUrl }}"
         data-sync-all-url="{{ $apiSyncAllUrl }}"
         data-csrf="{{ csrf_token() }}"
         data-i18n='@json($scannerI18n)'>

        <div class="mca-perm-scanner-actions">
            <button type="button" id="mcaPermBtnScan" class="mca-perm-btn mca-perm-btn--primary">
                @include('mca-permission::partials.icon', ['name' => 'scan', 'class' => 'mca-ui-icon mca-ui-icon--sm'])
                {{ mca_perm('scanner.scan') }}
            </button>
            <button type="button" id="mcaPermBtnAddSelected" class="mca-perm-btn mca-perm-btn--secondary" disabled>
                @include('mca-permission::partials.icon', ['name' => 'plus', 'class' => 'mca-ui-icon mca-ui-icon--sm'])
                {{ mca_perm('scanner.add_selected') }}
            </button>
            <button type="button" id="mcaPermBtnSyncLabels" class="mca-perm-btn mca-perm-btn--secondary">
                @include('mca-permission::partials.icon', ['name' => 'pencil', 'class' => 'mca-ui-icon mca-ui-icon--sm'])
                {{ mca_perm('scanner.sync_labels') }}
            </button>
            <button type="button" id="mcaPermBtnSyncAll" class="mca-perm-btn mca-perm-btn--secondary">
                @include('mca-permission::partials.icon', ['name' => 'save', 'class' => 'mca-ui-icon mca-ui-icon--sm'])
                {{ mca_perm('scanner.sync_all') }}
            </button>
            <span id="mcaPermScanStatus" class="mca-perm-status"></span>
        </div>

        <div class="mca-perm-grid-2">
            <div class="mca-perm-card">
                <div class="mca-perm-card__header">{{ mca_perm('scanner.missing') }}</div>
                <div class="mca-perm-table-wrap">
                    <table class="mca-perm-table">
                        <thead>
                            <tr>
                                <th style="width:2.5rem"><input type="checkbox" id="mcaPermCheckAll" title="{{ mca_perm('scanner.check_all') }}"></th>
                                <th>{{ mca_perm('permissions.col_name') }}</th>
                                <th>{{ mca_perm('scanner.col_module') }}</th>
                                <th>{{ mca_perm('scanner.col_method') }}</th>
                            </tr>
                        </thead>
                        <tbody id="mcaPermMissingBody">
                            <tr><td colspan="4" class="mca-perm-empty">{{ mca_perm('scanner.prompt_scan') }}</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mca-perm-card">
                <div class="mca-perm-card__header">{{ mca_perm('scanner.existing') }}</div>
                <div class="mca-perm-table-wrap">
                    <table class="mca-perm-table">
                        <thead>
                            <tr>
                                <th>{{ mca_perm('permissions.col_name') }}</th>
                                <th>{{ mca_perm('scanner.col_assigned_roles') }}</th>
                            </tr>
                        </thead>
                        <tbody id="mcaPermExistingBody">
                            <tr><td colspan="2" class="mca-perm-empty">{{ mca_perm('common.empty_dash') }}</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
