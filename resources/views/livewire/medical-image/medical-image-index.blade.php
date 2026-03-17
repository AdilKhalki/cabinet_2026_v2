<div>
    @if(session('success'))
        <div class="alert alert-success alert-dismissible">
            <button type="button" class="close" data-dismiss="alert">×</button>
            {{ session('success') }}
        </div>
    @endif

    <div class="card">
        <div class="card-header">
            <h5><i class="feather icon-image"></i> Imagerie Médicale</h5>
            <div class="card-header-right">
                @can('create', App\Models\MedicalImage::class)
                <a href="{{ route('medical-images.create') }}" class="btn btn-primary btn-sm">
                    <i class="feather icon-plus"></i> Nouvelle image
                </a>
                @endcan
            </div>
        </div>
        <div class="card-block">

            {{-- Search & Filter --}}
            <div class="row m-b-20">
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-addon"><i class="feather icon-search"></i></span>
                        <input type="text" wire:model.live.debounce.300ms="search" class="form-control" placeholder="Rechercher par patient, titre...">
                    </div>
                </div>
                <div class="col-md-4">
                    <select wire:model.live="categoryFilter" class="form-control">
                        <option value="">-- Toutes les catégories --</option>
                        @foreach($categories as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 text-right">
                    <span class="text-muted">{{ $medicalImages->total() }} résultat(s)</span>
                </div>
            </div>

            {{-- Image Grid --}}
            @if($medicalImages->count())
                <div class="row">
                    @foreach($medicalImages as $image)
                        <div class="col-md-4 col-sm-6 m-b-20">
                            <div class="card" style="border: 1px solid #ddd; border-radius: 6px; overflow: hidden;">
                                {{-- Thumbnail --}}
                                <div style="height: 180px; background: #f0f0f0; display: flex; align-items: center; justify-content: center; overflow: hidden;">
                                    @if(in_array(strtolower($image->file_type ?? ''), ['pdf']))
                                        <div class="text-center text-muted p-20">
                                            <i class="feather icon-file" style="font-size: 4em; color: #e74c3c;"></i>
                                            <br><small>Document PDF</small>
                                        </div>
                                    @elseif($image->file_path)
                                        <img src="{{ Storage::url($image->file_path) }}"
                                             alt="{{ $image->title }}"
                                             style="width: 100%; height: 180px; object-fit: cover;"
                                             onerror="this.parentElement.innerHTML='<div class=\'text-center text-muted p-20\'><i class=\'feather icon-image\' style=\'font-size:3em\'></i><br><small>Image non disponible</small></div>'">
                                    @else
                                        <div class="text-center text-muted p-20">
                                            <i class="feather icon-image" style="font-size: 3em;"></i>
                                            <br><small>Pas d'aperçu</small>
                                        </div>
                                    @endif
                                </div>

                                {{-- Card Body --}}
                                <div class="card-block" style="padding: 12px;">
                                    <h6 class="m-b-5" style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="{{ $image->title }}">
                                        {{ $image->title }}
                                    </h6>
                                    <p class="text-muted m-b-5" style="font-size: 12px;">
                                        <i class="feather icon-user"></i> {{ $image->patient->full_name ?? '—' }}
                                    </p>
                                    <p class="m-b-5">
                                        @php
                                            $catColors = ['chest_xray'=>'label-info','abdominal_xray'=>'label-info','limb_xray'=>'label-info','ct_scan'=>'label-warning','mri'=>'label-primary','ultrasound'=>'label-success','other'=>'label-default'];
                                            $catColor = $catColors[$image->category] ?? 'label-default';
                                        @endphp
                                        <span class="label {{ $catColor }}">{{ $categories[$image->category] ?? $image->category }}</span>
                                        @if($image->body_part)
                                            <span class="label label-default">{{ $image->body_part }}</span>
                                        @endif
                                    </p>
                                    <p class="text-muted m-b-10" style="font-size: 12px;">
                                        <i class="feather icon-calendar"></i>
                                        {{ $image->image_date ? \Carbon\Carbon::parse($image->image_date)->format('d/m/Y') : '—' }}
                                    </p>
                                    <div class="btn-group btn-group-sm" style="width: 100%;">
                                        <a href="{{ route('medical-images.show', $image) }}" class="btn btn-info btn-sm" title="Voir">
                                            <i class="feather icon-eye"></i>
                                        </a>
                                        @can('update', $image)
                                        <a href="{{ route('medical-images.edit', $image) }}" class="btn btn-warning btn-sm" title="Modifier">
                                            <i class="feather icon-edit"></i>
                                        </a>
                                        @endcan
                                        @can('delete', $image)
                                        <button type="button"
                                            wire:click="confirmDelete({{ $image->id }})"
                                            wire:confirm="Êtes-vous sûr de vouloir supprimer cette image ?"
                                            class="btn btn-danger btn-sm" title="Supprimer">
                                            <i class="feather icon-trash-2"></i>
                                        </button>
                                        @endcan
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="m-t-20">
                    {{ $medicalImages->links('pagination::bootstrap-5') }}
                </div>
            @else
                <div class="text-center p-30 text-muted">
                    <i class="feather icon-image" style="font-size: 3em;"></i>
                    <p class="m-t-10">Aucune image médicale trouvée.</p>
                </div>
            @endif

        </div>
    </div>
</div>
