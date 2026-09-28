@extends(\Mca\Permission\Support\McaPermissionView::layout())

@section('title', mca_perm('departments.title').' — '.($mcaPermTitle ?? mca_perm('app.title')))

@section('content')
    @php
        $np = config('permission.routes.name_prefix');
        $routeKey = config('permission.department.route_key', 'slug');
    @endphp

    <div class="mca-perm-toolbar">
        <div>
            <h1 class="mca-perm-toolbar__title">{{ mca_perm('departments.title') }}</h1>
            <p class="mca-perm-toolbar__subtitle">{{ mca_perm('departments.subtitle') }}</p>
        </div>
    </div>

    @if(! $departmentConfigured)
        <div class="mca-perm-card mca-perm-card__body mca-perm-empty">
            <p>{{ mca_perm('departments.not_configured') }}</p>
            <pre class="mca-perm-mono" style="text-align:left;margin:1rem 0;">{{ mca_perm('console.install.dept_stub_hint') }}
{{ mca_perm('console.install.dept_migrate_hint') }}</pre>
            <p>{{ mca_perm('console.install.dept_config_hint') }}</p>
        </div>
    @else
        <div class="mca-perm-layout-split">
            <div class="mca-perm-card mca-perm-card__body">
                <h2 style="margin:0 0 1rem;font-size:1rem;">{{ mca_perm('departments.new') }}</h2>
                <form method="POST" action="{{ route($np.'departments.store') }}">
                    @csrf
                    <div class="mca-perm-field">
                        <label class="mca-perm-label">{{ mca_perm('common.name') }} *</label>
                        <input name="name" class="mca-perm-input" required value="{{ old('name') }}">
                    </div>
                    <div class="mca-perm-field">
                        <label class="mca-perm-label">{{ mca_perm('common.slug') }}</label>
                        <input name="slug" class="mca-perm-input mca-perm-mono" value="{{ old('slug') }}">
                    </div>
                    <label class="mca-perm-checkbox-label">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" checked> {{ mca_perm('common.active') }}
                    </label>
                    <button type="submit" class="mca-perm-btn mca-perm-btn--primary mca-ui-btn--block" style="margin-top:0.75rem;">
                        @include('mca-permission::partials.icon', ['name' => 'plus', 'class' => 'mca-ui-icon mca-ui-icon--sm'])
                        {{ mca_perm('common.create') }}
                    </button>
                </form>
            </div>

            <div>
                @forelse($departments as $department)
                    @php $key = $department->{$routeKey} ?? $department->getKey(); @endphp
                    <div class="mca-perm-card mca-perm-role-card">
                        <div>
                            <strong style="font-size:1.05rem;">{{ $department->name }}</strong>
                            <div class="mca-perm-mono">{{ $department->slug ?? $department->id }}</div>
                        </div>
                        <div class="mca-ui-list-card__actions">
                            <a href="{{ route($np.'departments.permissions.edit', $key) }}" class="mca-perm-btn mca-perm-btn--secondary">
                                @include('mca-permission::partials.icon', ['name' => 'key', 'class' => 'mca-ui-icon mca-ui-icon--sm'])
                                {{ mca_perm('common.permissions') }}
                            </a>
                            <details class="mca-perm-details mca-perm-relative">
                                <summary class="mca-perm-btn mca-perm-btn--secondary">
                                    @include('mca-permission::partials.icon', ['name' => 'pencil', 'class' => 'mca-ui-icon mca-ui-icon--sm'])
                                    {{ mca_perm('common.edit') }}
                                </summary>
                                <div class="mca-perm-popover">
                                    <form method="POST" action="{{ route($np.'departments.update', $key) }}">
                                        @csrf
                                        @method('PUT')
                                        <div class="mca-perm-field">
                                            <label class="mca-perm-label">{{ mca_perm('common.name') }}</label>
                                            <input name="name" class="mca-perm-input" value="{{ $department->name }}" required>
                                        </div>
                                        <label class="mca-perm-checkbox-label">
                                            <input type="hidden" name="is_active" value="0">
                                            <input type="checkbox" name="is_active" value="1" @checked($department->is_active ?? true)> {{ mca_perm('common.active') }}
                                        </label>
                                        <button type="submit" class="mca-perm-btn mca-perm-btn--primary mca-ui-btn--block" style="margin-top:0.5rem;">
                                            @include('mca-permission::partials.icon', ['name' => 'save', 'class' => 'mca-ui-icon mca-ui-icon--sm'])
                                            {{ mca_perm('common.save') }}
                                        </button>
                                    </form>
                                </div>
                            </details>
                            <form method="POST" action="{{ route($np.'departments.destroy', $key) }}"
                                  data-mca-confirm="{{ mca_perm('departments.delete_confirm') }}"
                                  data-mca-confirm-title="{{ mca_perm('modal.delete_title') }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="mca-perm-btn mca-perm-btn--danger">
                                    @include('mca-permission::partials.icon', ['name' => 'trash', 'class' => 'mca-ui-icon mca-ui-icon--sm'])
                                    {{ mca_perm('common.delete') }}
                                </button>
                            </form>
                        </div>
                    </div>
                @empty
                    <div class="mca-perm-card mca-perm-card__body mca-perm-empty">{{ mca_perm('departments.empty') }}</div>
                @endforelse

                @if($departments->hasPages())
                    {{ $departments->links('mca-permission::partials.pagination') }}
                @endif
            </div>
        </div>
    @endif
@endsection
