<div>
    @if(session('success'))
        <div class="alert alert-success alert-dismissible">
            <button type="button" class="close" data-dismiss="alert">×</button>
            {{ session('success') }}
        </div>
    @endif

    {{-- Image Info Header --}}
    <div class="card">
        <div class="card-header">
            <h5><i class="feather icon-image"></i> {{ $medicalImage->title }}</h5>
            <div class="card-header-right">
                @if($medicalImage->patient)
                    <a href="{{ route('patients.show', $medicalImage->patient) }}" wire:navigate class="btn btn-default btn-sm">
                        <i class="feather icon-arrow-left"></i> Dossier patient
                    </a>
                @else
                    <a href="{{ route('medical-images.index') }}" wire:navigate class="btn btn-default btn-sm">
                        <i class="feather icon-arrow-left"></i> Retour
                    </a>
                @endif
                @if($medicalImage->file_path)
                    <button type="button" wire:click="download" class="btn btn-info btn-sm">
                        <i class="feather icon-download"></i> Télécharger
                    </button>
                @endif
                @can('update', $medicalImage)
                <a href="{{ route('medical-images.edit', $medicalImage) }}" class="btn btn-warning btn-sm">
                    <i class="feather icon-edit"></i> Modifier
                </a>
                @endcan
            </div>
        </div>
        <div class="card-block">
            <div class="row">
                <div class="col-md-3">
                    <strong><i class="feather icon-user"></i> Patient :</strong><br>
                    @if($medicalImage->patient)
                        <a href="{{ route('patients.show', $medicalImage->patient) }}" wire:navigate>{{ $medicalImage->patient->full_name }}</a>
                        <br><small class="text-muted">{{ $medicalImage->patient->dossier_number }}</small>
                    @else
                        —
                    @endif
                </div>
                <div class="col-md-3">
                    <strong><i class="feather icon-calendar"></i> Date :</strong><br>
                    {{ $medicalImage->image_date?->format('d/m/Y') ?? '—' }}
                </div>
                <div class="col-md-3">
                    <strong><i class="feather icon-tag"></i> Catégorie :</strong><br>
                    @php
                        $catColors = ['chest_xray'=>'label-info','abdominal_xray'=>'label-info','limb_xray'=>'label-info','ct_scan'=>'label-warning','mri'=>'label-primary','ultrasound'=>'label-success','other'=>'label-default'];
                        $catColor = $catColors[$medicalImage->category] ?? 'label-default';
                    @endphp
                    <span class="label {{ $catColor }}">{{ $categoryLabels[$medicalImage->category] ?? $medicalImage->category }}</span>
                </div>
                <div class="col-md-3">
                    <strong><i class="feather icon-user-check"></i> Médecin :</strong><br>
                    {{ $medicalImage->doctor->full_name ?? '—' }}
                </div>
            </div>
            @if($medicalImage->body_part)
                <div class="row m-t-10">
                    <div class="col-md-12">
                        <strong>Partie du corps :</strong> {{ $medicalImage->body_part }}
                    </div>
                </div>
            @endif
            @if($medicalImage->description)
                <div class="row m-t-10">
                    <div class="col-md-12">
                        <strong>Description :</strong><br>
                        {{ $medicalImage->description }}
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Inline Image Viewer --}}
    @if($medicalImage->file_path)
        @php
            $ext = strtolower($medicalImage->file_type ?: pathinfo($medicalImage->file_path, PATHINFO_EXTENSION));
            $isImage = $medicalImage->is_image;
            $isPdf = $ext === 'pdf';
            $fileUrl = $medicalImage->file_url;
        @endphp

        <div class="card m-t-15">
            <div class="card-header">
                <h5><i class="feather icon-eye"></i> Visualisation</h5>
                @if($isImage)
                    <div class="card-header-right">
                        <small class="text-muted">Utilisez les boutons pour zoomer</small>
                    </div>
                @endif
            </div>
            <div class="card-block text-center">

                @if($isImage)
                    <div x-data="{ scale: 1 }">
                        <div class="m-b-10">
                            <button type="button" class="btn btn-default btn-sm" @click="scale = Math.max(0.25, scale - 0.25)" title="Dézoomer">
                                <i class="feather icon-minus"></i> Dézoomer
                            </button>
                            <button type="button" class="btn btn-default btn-sm" @click="scale = 1" title="Réinitialiser">
                                <i class="feather icon-maximize-2"></i> 100%
                            </button>
                            <button type="button" class="btn btn-default btn-sm" @click="scale = Math.min(5, scale + 0.25)" title="Zoomer">
                                <i class="feather icon-plus"></i> Zoomer
                            </button>
                            <span class="text-muted m-l-10" x-text="Math.round(scale * 100) + '%'"></span>
                        </div>
                        <div style="overflow: auto; max-height: 600px; border: 1px solid #ddd; border-radius: 4px; padding: 10px; background: #f8f9fa;">
                            <div style="display: inline-block; transition: transform 0.2s;" :style="'transform: scale(' + scale + '); transform-origin: top center;'">
                                <img src="{{ $fileUrl }}"
                                     alt="{{ $medicalImage->title }}"
                                     style="max-width: 100%; display: block; margin: 0 auto; border-radius: 4px;">
                            </div>
                        </div>
                    </div>
                @elseif($isPdf)
                    <div class="text-center p-20" style="border: 1px solid #ddd; border-radius: 4px; background: #f8f9fa;">
                        <i class="feather icon-file" style="font-size: 4em; color: #e74c3c;"></i>
                        <p class="m-t-10">Document PDF</p>
                        <button type="button" wire:click="download" class="btn btn-info">
                            <i class="feather icon-download"></i> Télécharger le PDF
                        </button>
                    </div>
                @else
                    <div class="text-center p-20 text-muted">
                        <i class="feather icon-file" style="font-size: 4em;"></i>
                        <p>Format de fichier non prévisualisable.</p>
                        <button type="button" wire:click="download" class="btn btn-info">
                            <i class="feather icon-download"></i> Télécharger le fichier
                        </button>
                    </div>
                @endif

            </div>
        </div>
    @endif

    {{-- Radiological Report --}}
    <div class="card m-t-15">
        <div class="card-header">
            <h5><i class="feather icon-file-text"></i> Compte-rendu radiologique</h5>
        </div>
        <div class="card-block">
            @if($editing ?? false)
                <div class="form-group {{ $errors->has('radiologicalReport') ? 'has-error' : '' }}">
                    <textarea wire:model="radiologicalReport" class="form-control" rows="8"></textarea>
                    @error('radiologicalReport')<span class="help-block text-danger">{{ $message }}</span>@enderror
                </div>
                <button type="button" wire:click="saveReport" class="btn btn-primary btn-sm">
                    <i class="feather icon-save"></i> Sauvegarder
                </button>
                <button type="button" wire:click="cancelEditing" class="btn btn-default btn-sm">
                    <i class="feather icon-x"></i> Annuler
                </button>
            @else
                @if($medicalImage->radiological_report)
                    <div style="white-space: pre-wrap; background: #f8f9fa; padding: 15px; border-radius: 4px; border-left: 4px solid #4099ff;">{{ $medicalImage->radiological_report }}</div>
                @else
                    <p class="text-muted">Aucun compte-rendu radiologique disponible.</p>
                @endif
                @can('update', $medicalImage)
                <button type="button" wire:click="startEditing" class="btn btn-warning btn-sm m-t-10">
                    <i class="feather icon-edit"></i> Modifier le compte-rendu
                </button>
                @endcan
            @endif
        </div>
    </div>
</div>
