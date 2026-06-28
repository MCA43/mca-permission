@extends('mca-permission::layouts.app')

@section('title', mca_perm('users.permissions_title').' — '.($subjectLabel ?? ''))

@section('content')
    @php $np = config('permission.routes.name_prefix'); @endphp

    <form method="POST" action="{{ route($np.'users.permissions.update', $subject->id) }}">
        @csrf

        <div class="mca-perm-toolbar">
            <div>
                <h1 class="mca-perm-toolbar__title">{{ mca_perm('users.permissions_title') }}</h1>
                <p class="mca-perm-toolbar__subtitle">{{ $subjectLabel }}</p>
                @if($matrix['departmentExclusive'] ?? false)
                    <p class="mca-perm-mono">{{ mca_perm('users.dept_exclusive_note') }}</p>
                @endif
            </div>
            <div class="mca-perm-toolbar__actions">
                <a href="{{ route($np.'users.index') }}" class="mca-perm-btn mca-perm-btn--secondary">
                    @include('mca-permission::partials.icon', ['name' => 'arrow-left', 'class' => 'mca-ui-icon mca-ui-icon--sm'])
                    {{ mca_perm('common.back_users') }}
                </a>
                <button type="submit" class="mca-perm-btn mca-perm-btn--primary">
                    @include('mca-permission::partials.icon', ['name' => 'save', 'class' => 'mca-ui-icon mca-ui-icon--sm'])
                    {{ mca_perm('common.save') }}
                </button>
            </div>
        </div>

        @include('mca-permission::partials.permission-matrix', [
            'groups' => $groups,
            'directIds' => $matrix['directIds'],
            'roleIds' => $matrix['roleIds'],
            'departmentIds' => $matrix['departmentIds'],
            'roleLabel' => $matrix['roleLabel'] ?? mca_perm('matrix.role_fallback'),
            'showExclusive' => true,
            'exclusive' => $matrix['exclusive'],
            'exclusiveContext' => 'user',
            'showRoleBadges' => true,
            'showDepartmentBadges' => \Mca\Permission\Support\PermissionMode::supportsDepartmentGrants(),
        ])
    </form>
@endsection
