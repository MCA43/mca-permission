@php
    $np = config('permission.routes.name_prefix');
    $mode = \Mca\Permission\Support\PermissionMode::current();
@endphp
<header class="mca-ui-shell" id="mcaUiShell">
    <div class="mca-ui-shell__wrap">
        <div class="mca-ui-shell__inner">
            <a href="{{ route($np.'index') }}" class="mca-ui-brand">
                <span class="mca-ui-brand__mark" aria-hidden="true">
                    @include('mca-permission::partials.icon', ['name' => 'shield'])
                </span>
                <span>{{ $mcaPermTitle ?? mca_perm('app.brand') }}</span>
            </a>

            <button type="button"
                    class="mca-ui-menu-btn"
                    id="mcaUiMenuBtn"
                    aria-expanded="false"
                    aria-controls="mcaUiNav"
                    aria-label="{{ mca_perm('app.nav_aria') }}">
                @include('mca-permission::partials.icon', ['name' => 'menu'])
            </button>
        </div>

        <nav class="mca-ui-nav" id="mcaUiNav" aria-label="{{ mca_perm('app.nav_aria') }}">
            @if(Route::has('mca.hub.index'))
                <a href="{{ route('mca.hub.index') }}" class="mca-ui-nav__link">
                    @include('mca-permission::partials.icon', ['name' => 'grid', 'class' => 'mca-ui-icon mca-ui-icon--sm'])
                    {{ mca_perm('common.back_mca') }}
                </a>
            @endif

            <a href="{{ route($np.'index') }}"
               class="mca-ui-nav__link {{ request()->routeIs($np.'index') ? 'mca-ui-nav__link--active' : '' }}">
                @include('mca-permission::partials.icon', ['name' => 'key', 'class' => 'mca-ui-icon mca-ui-icon--sm'])
                {{ mca_perm('nav.permissions') }}
            </a>

            <a href="{{ route($np.'scanner') }}"
               class="mca-ui-nav__link {{ request()->routeIs($np.'scanner') ? 'mca-ui-nav__link--active' : '' }}">
                @include('mca-permission::partials.icon', ['name' => 'scan', 'class' => 'mca-ui-icon mca-ui-icon--sm'])
                {{ mca_perm('nav.scanner') }}
            </a>

            <a href="{{ route($np.'roles.index') }}"
               class="mca-ui-nav__link {{ request()->routeIs($np.'roles.*') ? 'mca-ui-nav__link--active' : '' }}">
                @include('mca-permission::partials.icon', ['name' => 'shield', 'class' => 'mca-ui-icon mca-ui-icon--sm'])
                {{ mca_perm('nav.roles') }}
            </a>

            @if(\Mca\Permission\Support\PermissionMode::supportsUserGrants())
                <a href="{{ route($np.'users.index') }}"
                   class="mca-ui-nav__link {{ request()->routeIs($np.'users.*') ? 'mca-ui-nav__link--active' : '' }}">
                    @include('mca-permission::partials.icon', ['name' => 'users', 'class' => 'mca-ui-icon mca-ui-icon--sm'])
                    {{ mca_perm('nav.users') }}
                </a>
            @endif

            @if(\Mca\Permission\Support\PermissionMode::supportsDepartmentGrants())
                <a href="{{ route($np.'departments.index') }}"
                   class="mca-ui-nav__link {{ request()->routeIs($np.'departments.*') ? 'mca-ui-nav__link--active' : '' }}">
                    @include('mca-permission::partials.icon', ['name' => 'building', 'class' => 'mca-ui-icon mca-ui-icon--sm'])
                    {{ mca_perm('nav.departments') }}
                </a>
            @endif

            <span class="mca-ui-nav__badge mca-perm-badge mca-perm-badge--mode">{{ $mode }}</span>
        </nav>
    </div>
</header>
