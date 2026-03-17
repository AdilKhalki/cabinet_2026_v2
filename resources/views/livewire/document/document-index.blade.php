<div>
    @if(session('success'))
        <div class="alert alert-success alert-dismissible">
            <button type="button" class="close" data-dismiss="alert">×</button>
            {{ session('success') }}
        </div>
    @endif

    <div class="card">
        <div class="card-header">
            <h5><i class="feather icon-folder"></i> Documents</h5>
            <div class="card-header-right">
                @can('create', App\Models\Document::class)
                <a href="{{ route('documents.create') }}" class="btn btn-primary btn-sm">
                    <i class="feather icon-plus"></i> Nouveau document
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
                        <input type="text" wire:model.live.debounce.300ms="search" class="form-control" placeholder="Rechercher par titre, patient...">
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
                    <span class="text-muted">{{ $documents->total() }} document(s)</span>
                </div>
            </div>

            {{-- Table --}}
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>Titre</th>
                            <th>Patient</th>
                            <th>Catégorie</th>
                            <th>Type de fichier</th>
                            <th>Date</th>
                            <th>Taille</th>
                            <th style="width: 140px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($documents as $document)
                            <tr>
                                <td>
                                    @php
                                        $ext = strtolower($document->file_type ?? pathinfo($document->file_path ?? '', PATHINFO_EXTENSION));
                                        $icon = 'icon-file';
                                        if (in_array($ext, ['jpg','jpeg','png','gif','bmp','webp'])) $icon = 'icon-image';
                                        elseif (in_array($ext, ['doc','docx'])) $icon = 'icon-file-text';
                                    @endphp
                                    <i class="feather {{ $icon }} text-muted"></i>
                                    {{ $document->title }}
                                </td>
                                <td>{{ $document->patient->full_name ?? '—' }}</td>
                                <td>{{ $categories[$document->category] ?? $document->category ?? '—' }}</td>
                                <td>
                                    @php
                                        $badgeColors = ['pdf'=>'label-danger','jpg'=>'label-success','jpeg'=>'label-success','png'=>'label-info','doc'=>'label-primary','docx'=>'label-primary','xls'=>'label-warning','xlsx'=>'label-warning'];
                                        $badgeColor = $badgeColors[$ext] ?? 'label-default';
                                    @endphp
                                    <span class="label {{ $badgeColor }}">{{ strtoupper($ext ?: '—') }}</span>
                                </td>
                                <td>
                                    {{ $document->document_date ? \Carbon\Carbon::parse($document->document_date)->format('d/m/Y') : '—' }}
                                </td>
                                <td>
                                    @if($document->file_size)
                                        @if($document->file_size >= 1048576)
                                            {{ round($document->file_size / 1048576, 1) }} Mo
                                        @else
                                            {{ round($document->file_size / 1024, 1) }} Ko
                                        @endif
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>
                                    @if($document->file_path)
                                        @if(in_array($ext, ['pdf']))
                                            <a href="{{ Storage::url($document->file_path) }}" target="_blank" class="btn btn-info btn-xs" title="Prévisualiser">
                                                <i class="feather icon-eye"></i>
                                            </a>
                                        @endif
                                        <a href="{{ Storage::url($document->file_path) }}" download class="btn btn-default btn-xs" title="Télécharger">
                                            <i class="feather icon-download"></i>
                                        </a>
                                    @endif
                                    @can('update', $document)
                                    <a href="{{ route('documents.edit', $document) }}" class="btn btn-warning btn-xs" title="Modifier">
                                        <i class="feather icon-edit"></i>
                                    </a>
                                    @endcan
                                    @can('delete', $document)
                                    <button type="button"
                                        wire:click="delete({{ $document->id }})"
                                        wire:confirm="Êtes-vous sûr de vouloir supprimer ce document ?"
                                        class="btn btn-danger btn-xs" title="Supprimer">
                                        <i class="feather icon-trash-2"></i>
                                    </button>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted p-20">
                                    <i class="feather icon-folder" style="font-size: 2em;"></i>
                                    <p>Aucun document trouvé.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="m-t-20">
                {{ $documents->links('pagination::bootstrap-5') }}
            </div>

        </div>
    </div>
</div>
