@extends(\Mca\Permission\Support\McaPermissionView::layout())

@section('title', mca_perm('permissions.list_title').' — '.($mcaPermTitle ?? mca_perm('app.title')))

@section('content')
    @php $np = config('permission.routes.name_prefix'); @endphp

    <div class="mca-perm-toolbar">
        <div>
            <h1 class="mca-perm-toolbar__title">{{ mca_perm('permissions.title') }}</h1>
            <p class="mca-perm-toolbar__subtitle">{{ mca_perm('permissions.subtitle') }}</p>
        </div>
        <div class="mca-perm-toolbar__actions">
            <a href="{{ route($np.'roles.index') }}" class="mca-perm-btn mca-perm-btn--secondary">
                @include('mca-permission::partials.icon', ['name' => 'shield', 'class' => 'mca-ui-icon mca-ui-icon--sm'])
                {{ mca_perm('nav.roles') }}
            </a>
            <a href="{{ route($np.'scanner') }}" class="mca-perm-btn mca-perm-btn--primary">
                @include('mca-permission::partials.icon', ['name' => 'scan', 'class' => 'mca-ui-icon mca-ui-icon--sm'])
                {{ mca_perm('nav.scanner') }}
            </a>
        </div>
    </div>

    <div class="mca-perm-layout-split" id="mcaPermPermissionListRoot"
         data-store-url="{{ route($np.'permissions.store') }}"
         data-i18n-new="{{ mca_perm('permissions.new') }}"
         data-i18n-edit="{{ mca_perm('permissions.edit_title') }}"
         data-i18n-hint="{{ mca_perm('permissions.new_hint') }}"
         data-i18n-add="{{ mca_perm('permissions.add') }}"
         data-i18n-save="{{ mca_perm('common.save') }}"
         data-i18n-cancel="{{ mca_perm('permissions.cancel_edit') }}"
         data-i18n-editing="{{ mca_perm('permissions.editing_name', ['name' => '__NAME__']) }}">
        <div class="mca-perm-card mca-perm-card__body mca-perm-permission-form-card" id="mcaPermPermissionFormCard">
            <h2 class="mca-ui-card__title" id="mcaPermFormTitle">{{ mca_perm('permissions.new') }}</h2>
            <p class="mca-perm-help" id="mcaPermFormHint" style="margin-bottom:0.75rem;">{{ mca_perm('permissions.new_hint') }}</p>
            <p class="mca-perm-mono mca-perm-form-editing-name" id="mcaPermFormEditingName" hidden></p>

            <form method="POST" action="{{ route($np.'permissions.store') }}" id="mcaPermPermissionForm">
                @csrf
                <input type="hidden" name="_method" id="mcaPermFormMethod" value="PUT" disabled>

                <div class="mca-perm-field">
                    <label class="mca-perm-label" for="mcaPermFolder">{{ mca_perm('permissions.col_folder') }} *</label>
                    <input id="mcaPermFolder" name="folder" class="mca-perm-input" required value="{{ old('folder', 'Panel') }}" placeholder="Panel">
                </div>
                <div class="mca-perm-field">
                    <label class="mca-perm-label" for="mcaPermController">{{ mca_perm('permissions.col_controller') }} *</label>
                    <input id="mcaPermController" name="controller" class="mca-perm-input mca-perm-mono" required value="{{ old('controller') }}" placeholder="DashboardController">
                </div>
                <div class="mca-perm-field">
                    <label class="mca-perm-label" for="mcaPermMethod">{{ mca_perm('permissions.col_method') }} *</label>
                    <input id="mcaPermMethod" name="method" class="mca-perm-input mca-perm-mono" required value="{{ old('method', 'index') }}" placeholder="index">
                </div>
                <div class="mca-perm-field">
                    <label class="mca-perm-label" for="mcaPermModuleDesc">{{ mca_perm('permissions.module_description') }}</label>
                    <input id="mcaPermModuleDesc" name="module_description" class="mca-perm-input" value="{{ old('module_description') }}">
                </div>
                <div class="mca-perm-field">
                    <label class="mca-perm-label" for="mcaPermMethodDesc">{{ mca_perm('permissions.method_description') }}</label>
                    <input id="mcaPermMethodDesc" name="method_description" class="mca-perm-input" value="{{ old('method_description') }}">
                </div>
                <label class="mca-perm-checkbox-label">
                    <input type="hidden" name="is_root_only" value="0">
                    <input type="checkbox" id="mcaPermRootOnly" name="is_root_only" value="1" @checked(old('is_root_only'))>
                    {{ mca_perm('permissions.root_only') }}
                </label>
                <div class="mca-perm-form-actions">
                    <button type="submit" class="mca-perm-btn mca-perm-btn--primary mca-ui-btn--block" id="mcaPermFormSubmit">
                        @include('mca-permission::partials.icon', ['name' => 'plus', 'class' => 'mca-ui-icon mca-ui-icon--sm'])
                        <span id="mcaPermFormSubmitLabel">{{ mca_perm('permissions.add') }}</span>
                    </button>
                    <button type="button" class="mca-perm-btn mca-perm-btn--secondary mca-ui-btn--block" id="mcaPermFormCancel" hidden>
                        {{ mca_perm('permissions.cancel_edit') }}
                    </button>
                </div>
            </form>
        </div>

        <div class="mca-perm-card mca-perm-table-wrap">
            <table class="mca-perm-table" id="mcaPermPermissionTable">
                <thead>
                    <tr>
                        <th>{{ mca_perm('permissions.col_name') }}</th>
                        <th>{{ mca_perm('permissions.col_folder') }}</th>
                        <th>{{ mca_perm('permissions.col_module') }}</th>
                        <th>{{ mca_perm('permissions.col_method') }}</th>
                        <th>{{ mca_perm('permissions.col_root') }}</th>
                        <th>{{ mca_perm('permissions.col_roles') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($permissions as $permission)
                        <tr data-permission-id="{{ $permission->id }}">
                            <td><code class="mca-perm-mono">{{ $permission->name }}</code></td>
                            <td>{{ $permission->folder }}</td>
                            <td>
                                <div>{{ $permission->module_description }}</div>
                                <code class="mca-perm-mono">{{ $permission->module }}</code>
                            </td>
                            <td>
                                <div>{{ $permission->method_description }}</div>
                                <code class="mca-perm-mono">{{ $permission->method }}</code>
                            </td>
                            <td>
                                <span class="mca-perm-badge {{ $permission->is_root_only ? 'mca-perm-badge--yes' : 'mca-perm-badge--no' }}">
                                    {{ $permission->is_root_only ? mca_perm('common.yes') : mca_perm('common.no') }}
                                </span>
                            </td>
                            <td class="mca-perm-mono">{{ implode(', ', $permission->assignedRoles()) ?: mca_perm('common.empty_dash') }}</td>
                            <td>
                                <div class="mca-ui-list-card__actions mca-ui-list-card__actions--tight">
                                    <button type="button"
                                            class="mca-perm-btn mca-perm-btn--secondary mca-perm-btn--icon mca-perm-edit-permission"
                                            title="{{ mca_perm('common.edit') }}"
                                            aria-label="{{ mca_perm('common.edit') }}"
                                            data-update-url="{{ route($np.'permissions.update', $permission) }}"
                                            data-name="{{ $permission->name }}"
                                            data-folder="{{ $permission->folder }}"
                                            data-controller="{{ $permission->controller }}"
                                            data-method="{{ $permission->method }}"
                                            data-module-description="{{ $permission->module_description }}"
                                            data-method-description="{{ $permission->method_description }}"
                                            data-root-only="{{ $permission->is_root_only ? '1' : '0' }}">
                                        @include('mca-permission::partials.icon', ['name' => 'pencil', 'class' => 'mca-ui-icon mca-ui-icon--xs'])
                                    </button>
                                    <form method="POST" action="{{ route($np.'permissions.destroy', $permission) }}"
                                          data-mca-confirm="{{ mca_perm('permissions.delete_confirm') }}"
                                          data-mca-confirm-title="{{ mca_perm('modal.delete_title') }}"
                                          data-mca-confirm-text="{{ mca_perm('common.delete') }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="mca-perm-btn mca-perm-btn--danger mca-perm-btn--icon" title="{{ mca_perm('common.delete') }}" aria-label="{{ mca_perm('common.delete') }}">
                                            @include('mca-permission::partials.icon', ['name' => 'trash', 'class' => 'mca-ui-icon mca-ui-icon--xs'])
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="mca-perm-empty">
                                {!! mca_perm('permissions.empty', [
                                    'link' => '<a href="'.route($np.'scanner').'">'.mca_perm('permissions.empty_link').'</a>',
                                ]) !!}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
