@extends(\Mca\Permission\Support\McaPermissionView::layout())

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
            'segments_empty' => mca_perm('scanner.segments_empty'),
            'segments_saved' => mca_perm('scanner.segments_saved'),
            'segments_deleted' => mca_perm('scanner.segments_deleted'),
            'segments_from_config' => mca_perm('scanner.segments_from_config'),
            'segments_count' => mca_perm('scanner.segments_count'),
            'segments_delete_confirm' => mca_perm('scanner.segments_delete_confirm'),
            'common_delete' => mca_perm('common.delete'),
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
         class="mca-perm-scanner"
         data-scan-url="{{ $apiScanUrl }}"
         data-bulk-url="{{ $apiBulkUrl }}"
         data-sync-labels-url="{{ $apiSyncLabelsUrl }}"
         data-sync-all-url="{{ $apiSyncAllUrl }}"
         data-segments-url="{{ $apiSegmentsUrl }}"
         data-segments-store-url="{{ $apiSegmentsStoreUrl }}"
         data-segments-sync-url="{{ $apiSegmentsSyncUrl }}"
         data-csrf="{{ csrf_token() }}"
         data-i18n='@json($scannerI18n)'>

        <details class="mca-ui-collapse mca-perm-segments-collapse">
            <summary class="mca-ui-collapse__summary">
                <span class="mca-ui-collapse__text">
                    <span class="mca-perm-module-title">{{ mca_perm('scanner.segments_title') }}</span>
                    <span class="mca-perm-help">{{ mca_perm('scanner.segments_subtitle') }}</span>
                </span>
                <span class="mca-ui-collapse__meta">
                    <span id="mcaPermSegmentsCount" class="mca-perm-badge mca-perm-badge--mode"></span>
                    <span class="mca-ui-collapse__chevron" aria-hidden="true">
                        @include('mca-permission::partials.icon', ['name' => 'chevron-down', 'class' => 'mca-ui-icon mca-ui-icon--xs'])
                    </span>
                </span>
            </summary>

            <div class="mca-ui-collapse__body">
                <div class="mca-perm-segments-toolbar">
                    <p class="mca-perm-help" style="margin:0;">{{ mca_perm('scanner.segments_manage_hint') }}</p>
                    <button type="button" id="mcaPermBtnSyncSegments" class="mca-perm-btn mca-perm-btn--secondary mca-perm-btn--sm">
                        {{ mca_perm('scanner.segments_sync_config') }}
                    </button>
                </div>

                <div class="mca-perm-table-wrap mca-perm-segments-table-wrap">
                    <table class="mca-perm-table">
                        <thead>
                            <tr>
                                <th>{{ mca_perm('scanner.segments_folder') }}</th>
                                <th>{{ mca_perm('scanner.segments_path') }}</th>
                                <th>{{ mca_perm('scanner.segments_namespace') }}</th>
                                <th>{{ mca_perm('scanner.segments_active') }}</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="mcaPermSegmentsBody">
                            <tr><td colspan="5" class="mca-perm-empty">{{ mca_perm('scanner.segments_empty') }}</td></tr>
                        </tbody>
                    </table>
                </div>

                <form id="mcaPermSegmentForm" class="mca-perm-grid-3" style="margin-top:1rem;">
                    <div class="mca-perm-field">
                        <label class="mca-perm-label">{{ mca_perm('scanner.segments_folder') }}</label>
                        <input name="folder" class="mca-perm-input" placeholder="Panel" required>
                    </div>
                    <div class="mca-perm-field">
                        <label class="mca-perm-label">{{ mca_perm('scanner.segments_path') }}</label>
                        <input name="path" class="mca-perm-input" placeholder="Http/Controllers/Panel" required>
                    </div>
                    <div class="mca-perm-field">
                        <label class="mca-perm-label">{{ mca_perm('scanner.segments_namespace') }}</label>
                        <input name="namespace" class="mca-perm-input" placeholder="App\Http\Controllers\Panel" required>
                    </div>
                    <div style="grid-column:1/-1;">
                        <button type="submit" class="mca-perm-btn mca-perm-btn--primary mca-perm-btn--sm">
                            @include('mca-permission::partials.icon', ['name' => 'plus', 'class' => 'mca-ui-icon mca-ui-icon--sm'])
                            {{ mca_perm('scanner.segments_add') }}
                        </button>
                    </div>
                </form>
            </div>
        </details>

        <div class="mca-perm-card mca-perm-card__body mca-perm-scanner-main">
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
    </div>
@endsection
