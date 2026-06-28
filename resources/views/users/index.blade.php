@extends('mca-permission::layouts.app')

@section('title', mca_perm('users.title').' — '.($mcaPermTitle ?? mca_perm('app.title')))

@section('content')
    @php $np = config('permission.routes.name_prefix'); @endphp

    <div class="mca-perm-toolbar">
        <div>
            <h1 class="mca-perm-toolbar__title">{{ mca_perm('users.title') }}</h1>
            <p class="mca-perm-toolbar__subtitle">{{ mca_perm('users.subtitle') }}</p>
        </div>
    </div>

    <div class="mca-perm-layout-split">
        <div class="mca-perm-card mca-perm-card__body">
            <h2 style="margin:0 0 1rem;font-size:1rem;">{{ mca_perm('users.new') }}</h2>
            <form method="POST" action="{{ route($np.'users.store') }}">
                @csrf
                <div class="mca-perm-field">
                    <label class="mca-perm-label">{{ mca_perm('common.name') }} *</label>
                    <input name="name" class="mca-perm-input" required value="{{ old('name') }}">
                </div>
                <div class="mca-perm-field">
                    <label class="mca-perm-label">{{ mca_perm('common.email') }} *</label>
                    <input type="email" name="email" class="mca-perm-input" required value="{{ old('email') }}">
                </div>
                <div class="mca-perm-field">
                    <label class="mca-perm-label">{{ mca_perm('common.password') }} *</label>
                    <input type="password" name="password" class="mca-perm-input" required autocomplete="new-password">
                </div>
                <div class="mca-perm-field">
                    <label class="mca-perm-label">{{ mca_perm('common.role') }} *</label>
                    <select name="{{ $roleColumn }}" class="mca-perm-input" required>
                        @foreach($roles as $role)
                            <option value="{{ $role->id }}" @selected(old($roleColumn) == $role->id)>{{ $role->name }}</option>
                        @endforeach
                    </select>
                </div>
                @if($showDepartment)
                    <div class="mca-perm-field">
                        <label class="mca-perm-label">{{ mca_perm('common.department') }}</label>
                        <select name="department_id" class="mca-perm-input">
                            <option value="">{{ mca_perm('common.empty_dash') }}</option>
                            @foreach($departments as $dept)
                                <option value="{{ $dept->id }}" @selected(old('department_id') == $dept->id)>{{ $dept->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
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
            @forelse($users as $user)
                <div class="mca-perm-card mca-perm-role-card">
                    <div>
                        <strong style="font-size:1.05rem;">{{ $user->name }}</strong>
                        <div class="mca-perm-mono">{{ $user->email }}</div>
                        <div class="mca-perm-user-badges">
                            <span class="mca-perm-badge mca-perm-badge--no">{{ $roleNames[$user->{$roleColumn}] ?? $user->{$roleColumn} }}</span>
                            @if(\Mca\Permission\Support\PermissionGrantContext::isUserExclusive($user))
                                <span class="mca-perm-badge mca-perm-badge--warn" title="{{ mca_perm('matrix.exclusive_badge_title') }}">{{ mca_perm('matrix.exclusive_badge') }}</span>
                            @endif
                            @if($showDepartment && $user->{$deptColumn})
                                <span class="mca-perm-badge mca-perm-badge--dept">{{ $departmentNames[$user->{$deptColumn}] ?? $user->{$deptColumn} }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="mca-ui-list-card__actions">
                        <a href="{{ route($np.'users.permissions.edit', $user->id) }}" class="mca-perm-btn mca-perm-btn--secondary">
                            @include('mca-permission::partials.icon', ['name' => 'key', 'class' => 'mca-ui-icon mca-ui-icon--sm'])
                            {{ mca_perm('common.permissions') }}
                        </a>
                        <details class="mca-perm-details mca-perm-relative">
                            <summary class="mca-perm-btn mca-perm-btn--secondary">
                                @include('mca-permission::partials.icon', ['name' => 'pencil', 'class' => 'mca-ui-icon mca-ui-icon--sm'])
                                {{ mca_perm('common.edit') }}
                            </summary>
                            <div class="mca-perm-popover" style="width:20rem;">
                                <form method="POST" action="{{ route($np.'users.update', $user->id) }}">
                                    @csrf
                                    @method('PUT')
                                    <div class="mca-perm-field">
                                        <label class="mca-perm-label">{{ mca_perm('common.name') }}</label>
                                        <input name="name" class="mca-perm-input" value="{{ $user->name }}" required>
                                    </div>
                                    <div class="mca-perm-field">
                                        <label class="mca-perm-label">{{ mca_perm('common.email') }}</label>
                                        <input type="email" name="email" class="mca-perm-input" value="{{ $user->email }}" required>
                                    </div>
                                    <div class="mca-perm-field">
                                        <label class="mca-perm-label">{{ mca_perm('common.new_password') }}</label>
                                        <input type="password" name="password" class="mca-perm-input" autocomplete="new-password" placeholder="{{ mca_perm('common.password_optional') }}">
                                    </div>
                                    <div class="mca-perm-field">
                                        <label class="mca-perm-label">{{ mca_perm('common.role') }}</label>
                                        <select name="{{ $roleColumn }}" class="mca-perm-input" required>
                                            @foreach($roles as $role)
                                                <option value="{{ $role->id }}" @selected($user->{$roleColumn} == $role->id)>{{ $role->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    @if($showDepartment)
                                        <div class="mca-perm-field">
                                            <label class="mca-perm-label">{{ mca_perm('common.department') }}</label>
                                            <select name="department_id" class="mca-perm-input">
                                                <option value="">{{ mca_perm('common.empty_dash') }}</option>
                                                @foreach($departments as $dept)
                                                    <option value="{{ $dept->id }}" @selected($user->{$deptColumn} == $dept->id)>{{ $dept->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    @endif
                                    <label class="mca-perm-checkbox-label">
                                        <input type="hidden" name="is_active" value="0">
                                        <input type="checkbox" name="is_active" value="1" @checked($user->is_active ?? true)> {{ mca_perm('common.active') }}
                                    </label>
                                    <button type="submit" class="mca-perm-btn mca-perm-btn--primary mca-ui-btn--block" style="margin-top:0.5rem;">
                                        @include('mca-permission::partials.icon', ['name' => 'save', 'class' => 'mca-ui-icon mca-ui-icon--sm'])
                                        {{ mca_perm('common.save') }}
                                    </button>
                                </form>
                            </div>
                        </details>
                        <form method="POST" action="{{ route($np.'users.destroy', $user->id) }}"
                              data-mca-confirm="{{ mca_perm('users.delete_confirm') }}"
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
                <div class="mca-perm-card mca-perm-card__body mca-perm-empty">{{ mca_perm('users.empty') }}</div>
            @endforelse

            @if($users->hasPages())
                <div style="margin-top:1rem;">{{ $users->links() }}</div>
            @endif
        </div>
    </div>
@endsection
