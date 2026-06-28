@if (session('mca_perm_status'))
    <div class="mca-ui-alert mca-perm-alert mca-perm-alert--success" role="status">
        @include('mca-permission::partials.icon', ['name' => 'check-circle', 'class' => 'mca-ui-icon mca-ui-icon--sm'])
        <div>{{ session('mca_perm_status') }}</div>
    </div>
@endif

@if ($errors->any())
    <div class="mca-ui-alert mca-perm-alert mca-perm-alert--error" role="alert">
        @include('mca-permission::partials.icon', ['name' => 'alert-circle', 'class' => 'mca-ui-icon mca-ui-icon--sm'])
        <ul style="margin:0;padding-left:1.1rem;">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
