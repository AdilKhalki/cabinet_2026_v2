<div>
    @if(session('success'))
        <div class="alert alert-success alert-dismissible">
            <button type="button" class="close" data-dismiss="alert">×</button>
            {{ session('success') }}
        </div>
    @endif
    <div class="card">
        <div class="card-header">
            <h5>Rôles</h5>
            <div class="card-header-right">
                <a href="{{ route('dashboard') }}" class="btn btn-primary btn-sm">
                    <i class="feather icon-plus"></i> Tableau de bord
                </a>
            </div>
        </div>
        <div class="card-block">
            <p class="text-muted text-center p-30">
                <i class="feather icon-settings" style="font-size:2em;"></i><br>
                Module en cours de développement.
            </p>
        </div>
    </div>
</div>
