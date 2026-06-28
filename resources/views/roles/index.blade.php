@extends('mca-permission::layouts.app')

@section('title', mca_perm('roles.title').' — '.($mcaPermTitle ?? mca_perm('app.title')))

@section('content')
    @php $np = config('permission.routes.name_prefix'); @endphp

    <div class="mca-perm-toolbar">
        <div>
            <h1 class="mca-perm-toolbar__title">{{ mca_perm('roles.title') }}</h1>
            <p class="mca-perm-toolbar__subtitle">{{ mca_perm('roles.subtitle') }}</p>
        </div>
        <div class="mca-perm-toolbar__actions">
            <a href="{{ route($np.'index') }}" class="mca-perm-btn mca-perm-btn--secondary">
                @include('mca-permission::partials.icon', ['name' => 'key', 'class' => 'mca-ui-icon mca-ui-icon--sm'])
                {{ mca_perm('nav.permissions') }}
            </a>
            <a href="{{ route($np.'scanner') }}" class="mca-perm-btn mca-perm-btn--secondary">
                @include('mca-permission::partials.icon', ['name' => 'scan', 'class' => 'mca-ui-icon mca-ui-icon--sm'])
                {{ mca_perm('nav.scanner') }}
            </a>
        </div>
    </div>

    <div class="mca-perm-layout-split">
        <div class="mca-perm-card mca-perm-card__body">
            <h2 style="margin:0 0 1rem;font-size:1rem;">{{ mca_perm('roles.new') }}</h2>
            <form method="POST" action="{{ route($np.'roles.store') }}">
                @csrf
                <div class="mca-perm-field">
                    <label class="mca-perm-label" for="mcaRoleName">{{ mca_perm('roles.role_name') }} *</label>
                    <input id="mcaRoleName" name="name" class="mca-perm-input" required value="{{ old('name') }}">
                </div>
                <div class="mca-perm-field">
                    <label class="mca-perm-label" for="mcaRoleSlug">{{ mca_perm('common.slug') }}</label>
                    <input id="mcaRoleSlug" name="slug" class="mca-perm-input mca-perm-mono" value="{{ old('slug') }}">
                </div>
                <div class="mca-perm-field">
                    <label class="mca-perm-label" for="mcaRoleDesc">{{ mca_perm('common.description') }}</label>
                    <input id="mcaRoleDesc" name="description" class="mca-perm-input" value="{{ old('description') }}">
                </div>
                <label class="mca-perm-checkbox-label">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" checked>
                    {{ mca_perm('common.active') }}
                </label>
                <button type="submit" class="mca-perm-btn mca-perm-btn--primary mca-ui-btn--block" style="margin-top:0.75rem;">
                    @include('mca-permission::partials.icon', ['name' => 'plus', 'class' => 'mca-ui-icon mca-ui-icon--sm'])
                    {{ mca_perm('common.create') }}
                </button>
            </form>
        </div>

        <div>
            @foreach($roles as $role)
                <div class="mca-perm-card mca-perm-role-card">
                    <div>
                        <strong style="font-size:1.05rem;">{{ $role->name }}</strong>
                        @if($role->is_root)
                            <span class="mca-perm-badge mca-perm-badge--yes">{{ mca_perm('permissions.col_root') }}</span>
                        @elseif($role->is_system)
                            <span class="mca-perm-mono">{{ mca_perm('common.system') }}</span>
                        @endif
                        <div class="mca-perm-mono">{{ $role->slug }}</div>
                        @if($role->description)
                            <p style="margin:0.35rem 0 0;font-size:0.875rem;color:#64748b;">{{ $role->description }}</p>
                        @endif
                        @if(($role->users_count ?? 0) > 0)
                            <span class="mca-perm-mono">{{ mca_perm('common.users_count', ['count' => $role->users_count]) }}</span>
                        @endif
                    </div>
                    <div class="mca-ui-list-card__actions">
                        @if($role->permissionsEditable())
                            <a href="{{ route($np.'roles.permissions.edit', $role) }}" class="mca-perm-btn mca-perm-btn--secondary">
                                @include('mca-permission::partials.icon', ['name' => 'key', 'class' => 'mca-ui-icon mca-ui-icon--sm'])
                                {{ mca_perm('common.permissions') }}
                            </a>
                        @else
                            <span class="mca-perm-mono">{{ mca_perm('common.all_permissions') }}</span>
                        @endif

                        @if(! $role->is_system && ! $role->is_root)
                            <details class="mca-perm-details mca-perm-relative">
                                <summary class="mca-perm-btn mca-perm-btn--secondary">
                                    @include('mca-permission::partials.icon', ['name' => 'pencil', 'class' => 'mca-ui-icon mca-ui-icon--sm'])
                                    {{ mca_perm('common.edit') }}
                                </summary>
                                <div class="mca-perm-popover">
                                    <form method="POST" action="{{ route($np.'roles.update', $role) }}">
                                        @csrf
                                        @method('PUT')
                                        <div class="mca-perm-field">
                                            <label class="mca-perm-label">{{ mca_perm('common.name') }}</label>
                                            <input name="name" class="mca-perm-input" value="{{ $role->name }}" required>
                                        </div>
                                        <div class="mca-perm-field">
                                            <label class="mca-perm-label">{{ mca_perm('common.description') }}</label>
                                            <input name="description" class="mca-perm-input" value="{{ $role->description }}">
                                        </div>
                                        <label class="mca-perm-checkbox-label">
                                            <input type="hidden" name="is_active" value="0">
                                            <input type="checkbox" name="is_active" value="1" @checked($role->is_active)> {{ mca_perm('common.active') }}
                                        </label>
                                        <button type="submit" class="mca-perm-btn mca-perm-btn--primary mca-ui-btn--block" style="margin-top:0.5rem;">
                                            @include('mca-permission::partials.icon', ['name' => 'save', 'class' => 'mca-ui-icon mca-ui-icon--sm'])
                                            {{ mca_perm('common.save') }}
                                        </button>
                                    </form>
                                </div>
                            </details>
                            <form method="POST" action="{{ route($np.'roles.destroy', $role) }}" onsubmit="return confirm(@js(mca_perm('roles.delete_confirm')))">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="mca-perm-btn mca-perm-btn--danger">
                                    @include('mca-permission::partials.icon', ['name' => 'trash', 'class' => 'mca-ui-icon mca-ui-icon--sm'])
                                    {{ mca_perm('common.delete') }}
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endsection
