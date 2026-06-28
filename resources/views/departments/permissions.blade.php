@extends('mca-permission::layouts.app')

@section('title', mca_perm('departments.permissions_title').' — '.($subjectLabel ?? ''))

@section('content')
    @php
        $np = config('permission.routes.name_prefix');
        $routeKey = config('permission.department.route_key', 'slug');
        $subjectRouteKey = $subject->{$routeKey} ?? $subject->getKey();
    @endphp

    <form method="POST" action="{{ route($np.'departments.permissions.update', $subjectRouteKey) }}">
        @csrf

        <div class="mca-perm-toolbar">
            <div>
                <h1 class="mca-perm-toolbar__title">{{ mca_perm('departments.permissions_title') }}</h1>
                <p class="mca-perm-toolbar__subtitle">{{ $subjectLabel }}</p>
            </div>
            <div class="mca-perm-toolbar__actions">
                <a href="{{ route($np.'departments.index') }}" class="mca-perm-btn mca-perm-btn--secondary">
                    @include('mca-permission::partials.icon', ['name' => 'arrow-left', 'class' => 'mca-ui-icon mca-ui-icon--sm'])
                    {{ mca_perm('common.back_departments') }}
                </a>
                <button type="submit" class="mca-perm-btn mca-perm-btn--primary">
                    @include('mca-permission::partials.icon', ['name' => 'save', 'class' => 'mca-ui-icon mca-ui-icon--sm'])
                    {{ mca_perm('common.save') }}
                </button>
            </div>
        </div>

        @include('mca-permission::partials.permission-matrix', [
            'groups' => $groups,
            'directIds' => $assignedIds,
            'showExclusive' => true,
            'exclusive' => $exclusive,
            'exclusiveContext' => 'department',
            'showRoleBadges' => false,
            'showDepartmentBadges' => false,
        ])
    </form>
@endsection
