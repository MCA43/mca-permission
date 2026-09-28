@php
    $directIds = $directIds ?? [];
    $roleIds = $roleIds ?? [];
    $departmentIds = $departmentIds ?? [];
    $showExclusive = $showExclusive ?? false;
    $exclusiveContext = $exclusiveContext ?? 'user';
    $exclusive = old('permission_exclusive', $exclusive ?? false);
    if (is_string($exclusive)) {
        $exclusive = filter_var($exclusive, FILTER_VALIDATE_BOOLEAN);
    }
    $showRoleBadges = $showRoleBadges ?? false;
    $showDepartmentBadges = $showDepartmentBadges ?? false;
    $roleLabel = $roleLabel ?? null;
    $exclusiveLabel = $exclusiveLabel ?? mca_perm("matrix.exclusive.{$exclusiveContext}.label");
    $exclusiveHelp = $exclusiveHelp ?? mca_perm("matrix.exclusive.{$exclusiveContext}.help");
@endphp

@if($showExclusive)
    <div class="mca-perm-card mca-perm-card__body mca-perm-exclusive-box">
        <label class="mca-perm-checkbox-label mca-perm-exclusive-toggle">
            <input type="checkbox" name="permission_exclusive" value="1" @checked($exclusive)>
            <span>
                <strong>{{ $exclusiveLabel }}</strong>
                <div class="mca-perm-help">{{ $exclusiveHelp }}</div>
            </span>
        </label>
    </div>
@endif

@if($showRoleBadges || $showDepartmentBadges)
    <div class="mca-perm-card mca-perm-card__body mca-perm-help-box">
        <strong>{{ mca_perm('matrix.how_to_read') }}</strong>
        <ul class="mca-perm-help-list">
            <li>
                <span class="mca-perm-badge mca-perm-badge--role">{{ mca_perm('nav.roles') }}</span>
                — {!! $exclusive ? mca_perm('matrix.role_from_exclusive') : mca_perm('matrix.role_from', ['role' => $roleLabel ?? mca_perm('matrix.role_fallback')]) !!}
            </li>
            @if($showDepartmentBadges)
                <li>
                    <span class="mca-perm-badge mca-perm-badge--dept">{{ mca_perm('nav.departments') }}</span>
                    — {!! $exclusive ? mca_perm('matrix.dept_from_exclusive') : mca_perm('matrix.dept_from') !!}
                </li>
            @endif
            <li>
                <span class="mca-perm-badge mca-perm-badge--direct">{{ mca_perm('matrix.badge_direct') }}</span>
                — {{ mca_perm('matrix.direct_grant_help') }}
            </li>
            <li>{!! $exclusive ? mca_perm('matrix.checkbox_hint_exclusive') : mca_perm('matrix.checkbox_hint') !!}</li>
        </ul>
    </div>
@endif

@if(! $exclusive && ($showRoleBadges || $showDepartmentBadges))
    <div class="mca-perm-alert mca-perm-alert--info">
        {!! mca_perm('matrix.normal_mode') !!}
    </div>
@elseif($exclusive)
    <div class="mca-perm-alert mca-perm-alert--warn">
        {{ mca_perm('matrix.exclusive_mode') }}
    </div>
@endif

@if(count($groups) === 0)
    <div class="mca-perm-card mca-perm-card__body mca-perm-empty">
        <a href="{{ route(config('permission.routes.name_prefix').'scanner') }}" class="mca-perm-btn mca-perm-btn--primary mca-perm-btn--sm">{{ mca_perm('matrix.scan_first') }}</a>
    </div>
@else
    <div class="mca-perm-grid-3">
        @foreach($groups as $group)
            <div class="mca-perm-card mca-perm-card__body" data-mca-perm-module>
                <div class="mca-perm-module-head">
                    <div>
                        @if(! empty($group['folder']))
                            <div class="mca-perm-module-folder">{{ $group['folder'] }}</div>
                        @endif
                        <h3 class="mca-perm-module-title">{{ $group['module_label'] }}</h3>
                    </div>
                    <label class="mca-perm-module-toggle" title="{{ mca_perm('roles.module_check_all') }}">
                        <input type="checkbox" data-mca-perm-module-toggle>
                        <span>{{ mca_perm('roles.module_check_all') }}</span>
                    </label>
                </div>
                <ul class="mca-perm-check-list">
                    @foreach($group['items'] as $item)
                        @php
                            $id = $item['id'];
                            $fromRole = $showRoleBadges && in_array($id, $roleIds, true);
                            $fromDept = $showDepartmentBadges && in_array($id, $departmentIds, true);
                            $direct = in_array($id, $directIds, true);
                            $hasAccess = $fromRole || $fromDept || $direct;
                        @endphp
                        <li>
                            <label class="mca-perm-check-row {{ $fromRole || $fromDept ? 'mca-perm-check-row--inherited' : '' }}">
                                <input type="checkbox" name="permission_ids[]" value="{{ $id }}" data-mca-perm-item @checked($direct)>
                                <span class="mca-perm-check-content">
                                    <span class="mca-perm-check-badges">
                                        @if($fromRole)
                                            <span class="mca-perm-badge mca-perm-badge--role{{ $exclusive ? ' mca-perm-badge--muted' : '' }}" title="{{ mca_perm('matrix.title_role') }}">{{ mca_perm('matrix.badge_role') }}</span>
                                        @endif
                                        @if($fromDept)
                                            <span class="mca-perm-badge mca-perm-badge--dept{{ $exclusive ? ' mca-perm-badge--muted' : '' }}" title="{{ mca_perm('matrix.title_dept') }}">{{ mca_perm('matrix.badge_dept') }}</span>
                                        @endif
                                        @if($direct)
                                            <span class="mca-perm-badge mca-perm-badge--direct">{{ mca_perm('matrix.badge_direct') }}</span>
                                        @endif
                                        @if($hasAccess && ! $fromRole && ! $fromDept && ! $direct)
                                            <span class="mca-perm-badge mca-perm-badge--yes">{{ mca_perm('matrix.badge_active') }}</span>
                                        @endif
                                    </span>
                                    <code class="mca-perm-mono">{{ $item['name'] }}</code>
                                    @if(! empty($item['method_label']))
                                        <div class="mca-perm-check-desc">{{ $item['method_label'] }}</div>
                                    @endif
                                    @if($exclusive && ($fromRole || $fromDept) && ! $direct)
                                        <div class="mca-perm-check-hint mca-perm-check-hint--warn">{{ mca_perm('matrix.hint_exclusive_inherited') }}</div>
                                    @elseif($fromRole && ! $direct)
                                        <div class="mca-perm-check-hint">{{ mca_perm('matrix.hint_role') }}</div>
                                    @elseif($fromDept && ! $direct && ! $fromRole)
                                        <div class="mca-perm-check-hint">{{ mca_perm('matrix.hint_dept') }}</div>
                                    @endif
                                </span>
                            </label>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    </div>
@endif
