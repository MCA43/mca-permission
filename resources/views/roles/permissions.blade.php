@extends(\Mca\Permission\Support\McaPermissionView::layout())

@section('title', mca_perm('roles.permissions_title').' — '.($roleLabel ?? ''))

@section('content')
    @php $np = config('permission.routes.name_prefix'); @endphp

    <form method="POST" action="{{ route($np.'roles.permissions.update', $role) }}">
        @csrf

        <div class="mca-perm-toolbar">
            <div>
                <h1 class="mca-perm-toolbar__title">{{ mca_perm('roles.permissions_title') }}</h1>
                <p class="mca-perm-toolbar__subtitle">{{ $roleLabel }}</p>
            </div>
            <div class="mca-perm-toolbar__actions">
                <a href="{{ route($np.'roles.index') }}" class="mca-perm-btn mca-perm-btn--secondary">
                    @include('mca-permission::partials.icon', ['name' => 'arrow-left', 'class' => 'mca-ui-icon mca-ui-icon--sm'])
                    {{ mca_perm('common.back_roles') }}
                </a>
                @if($editable)
                    <button type="submit" class="mca-perm-btn mca-perm-btn--primary">
                        @include('mca-permission::partials.icon', ['name' => 'save', 'class' => 'mca-ui-icon mca-ui-icon--sm'])
                        {{ mca_perm('common.save') }}
                    </button>
                @endif
            </div>
        </div>

        @if(! $editable)
            <div class="mca-perm-card mca-perm-card__body mca-perm-empty">
                {{ mca_perm('roles.root_note') }}
            </div>
        @elseif(count($groups) === 0)
            <div class="mca-perm-card mca-perm-card__body mca-perm-empty">
                <p>{{ mca_perm('roles.no_assignable') }}</p>
                <a href="{{ route($np.'scanner') }}" class="mca-perm-btn mca-perm-btn--primary">
                    @include('mca-permission::partials.icon', ['name' => 'scan', 'class' => 'mca-ui-icon mca-ui-icon--sm'])
                    {{ mca_perm('roles.go_scanner') }}
                </a>
            </div>
        @else
            <div class="mca-perm-grid-3">
                @foreach($groups as $group)
                    <div class="mca-perm-card mca-perm-card__body" data-mca-perm-module>
                        <div class="mca-perm-module-head">
                            <div>
                                <div class="mca-perm-module-folder">{{ $group['folder'] }}</div>
                                <h3 class="mca-perm-module-title">{{ $group['module_label'] }}</h3>
                            </div>
                            <div class="mca-perm-module-head__meta">
                                <code class="mca-perm-mono">{{ $group['module'] }}</code>
                                <label class="mca-perm-module-toggle" title="{{ mca_perm('roles.module_check_all') }}">
                                    <input type="checkbox" data-mca-perm-module-toggle>
                                    <span>{{ mca_perm('roles.module_check_all') }}</span>
                                </label>
                            </div>
                        </div>
                        <p class="mca-perm-mono" style="margin:0 0 0.75rem;">{{ $group['controller'] }}</p>
                        <ul class="mca-perm-check-list">
                            @foreach($group['items'] as $item)
                                <li>
                                    <label class="mca-perm-check-row">
                                        <input type="checkbox" name="permission_ids[]" value="{{ $item['id'] }}"
                                               data-mca-perm-item
                                               @checked(in_array($item['id'], $assignedIds, true))>
                                        <span>
                                            <code class="mca-perm-mono">{{ $item['name'] }}</code>
                                            <div style="font-size:0.875rem;">{{ $item['method_label'] }}</div>
                                        </span>
                                    </label>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </div>
        @endif
    </form>
@endsection
