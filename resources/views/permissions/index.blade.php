@extends('mca-permission::layouts.app')

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

    <div class="mca-perm-card mca-perm-table-wrap">
        <table class="mca-perm-table">
            <thead>
                <tr>
                    <th>{{ mca_perm('permissions.col_name') }}</th>
                    <th>{{ mca_perm('permissions.col_folder') }}</th>
                    <th>{{ mca_perm('permissions.col_controller') }}</th>
                    <th>{{ mca_perm('permissions.col_module') }}</th>
                    <th>{{ mca_perm('permissions.col_method') }}</th>
                    <th>{{ mca_perm('permissions.col_root') }}</th>
                    <th>{{ mca_perm('permissions.col_roles') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($permissions as $permission)
                    <tr>
                        <td><code class="mca-perm-mono">{{ $permission->name }}</code></td>
                        <td>{{ $permission->folder }}</td>
                        <td>{{ $permission->controller }}</td>
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
@endsection
