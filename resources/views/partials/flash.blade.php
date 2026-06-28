@if (session('mca_perm_status') || $errors->any())
    <div id="mcaUiFlashQueue" hidden>
        @if (session('mca_perm_status'))
            <span data-type="success" data-message="{{ session('mca_perm_status') }}"></span>
        @endif
        @if ($errors->any())
            <span data-type="error" data-message="{{ $errors->first() }}"></span>
        @endif
    </div>
@endif
