<div>
    @if(session('success'))
        <div class="alert alert-success alert-dismissible">
            <button type="button" class="close" data-dismiss="alert">×</button>
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible">
            <button type="button" class="close" data-dismiss="alert">×</button>
            <ul class="m-b-0">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    {{-- Filtres + Bouton --}}
    <div class="card">
        <div class="card-block">
            <div class="row">
                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-addon"><i class="feather icon-search"></i></span>
                        <input type="text" class="form-control" placeholder="Rechercher patient, facture..."
                               wire:model.live.debounce.300ms="search">
                    </div>
                </div>
                <div class="col-md-2">
                    <select wire:model.live="statusFilter" class="form-control">
                        <option value="">Tous les statuts</option>
                        <option value="paid">Payé</option>
                        <option value="partial">Partiel</option>
                        <option value="pending">Impayé</option>
                        <option value="cancelled">Annulé</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="date" class="form-control" wire:model.live="periodFrom" placeholder="Du">
                </div>
                <div class="col-md-2">
                    <input type="date" class="form-control" wire:model.live="periodTo" placeholder="Au">
                </div>
                <div class="col-md-2 text-right">
                    <button wire:click="openForm" class="btn btn-primary btn-sm">
                        <i class="feather icon-plus"></i> Nouveau Paiement
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Formulaire --}}
    @if($showForm)
    <div class="card">
        <div class="card-header">
            <h5><i class="feather icon-credit-card"></i> {{ $editingId ? 'Modifier Paiement' : 'Nouveau Paiement' }}</h5>
            <div class="card-header-right">
                <button wire:click="closeForm" class="btn btn-default btn-sm"><i class="feather icon-x"></i></button>
            </div>
        </div>
        <div class="card-block">
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Patient <span class="text-danger">*</span></label>
                        <select class="form-control" wire:model="patient_id">
                            <option value="">-- Sélectionner --</option>
                            @foreach($patients as $pat)
                                <option value="{{ $pat->id }}">{{ $pat->full_name }} ({{ $pat->dossier_number }})</option>
                            @endforeach
                        </select>
                        @error('patient_id') <span class="text-danger small">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Médecin</label>
                        <select class="form-control" wire:model="doctor_id">
                            <option value="">-- Sélectionner --</option>
                            @foreach($doctors as $doc)
                                <option value="{{ $doc->id }}">Dr. {{ $doc->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" wire:model="payment_date">
                        @error('payment_date') <span class="text-danger small">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>

            {{-- Lignes --}}
            <h6 class="m-t-20 m-b-10"><strong>Lignes de facturation</strong></h6>
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Description</th>
                            <th style="width:100px">Qté</th>
                            <th style="width:130px">Prix unitaire</th>
                            <th style="width:130px">Total</th>
                            <th style="width:50px"></th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($lines as $i => $line)
                        <tr>
                            <td>
                                <input type="text" class="form-control form-control-sm"
                                       wire:model="lines.{{ $i }}.description"
                                       placeholder="Description de l'acte">
                            </td>
                            <td>
                                <input type="number" class="form-control form-control-sm"
                                       wire:model.live="lines.{{ $i }}.quantity"
                                       min="1" step="1">
                            </td>
                            <td>
                                <input type="number" class="form-control form-control-sm"
                                       wire:model.live="lines.{{ $i }}.unit_price"
                                       min="0" step="0.01">
                            </td>
                            <td>
                                <span class="form-control form-control-sm bg-light">
                                    {{ number_format(($line['quantity'] ?? 1) * ($line['unit_price'] ?? 0), 2) }}
                                </span>
                            </td>
                            <td class="text-center">
                                @if(count($lines) > 1)
                                <button wire:click="removeLine({{ $i }})" class="btn btn-danger btn-xs">
                                    <i class="feather icon-trash-2"></i>
                                </button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <button wire:click="addLine" class="btn btn-default btn-sm m-b-20">
                <i class="feather icon-plus"></i> Ajouter une ligne
            </button>

            {{-- Totaux --}}
            <div class="row">
                <div class="col-md-6">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Mode de paiement <span class="text-danger">*</span></label>
                                <select class="form-control" wire:model="payment_method">
                                    <option value="cash">Espèces</option>
                                    <option value="card">Carte bancaire</option>
                                    <option value="transfer">Virement</option>
                                    <option value="insurance">Assurance</option>
                                    <option value="check">Chèque</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Notes</label>
                                <textarea class="form-control" wire:model="notes" rows="2"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <table class="table table-condensed">
                        <tr>
                            <th>Sous-total</th>
                            <td class="text-right">{{ number_format($this->subtotal, 2) }} MAD</td>
                        </tr>
                        <tr>
                            <th>
                                Remise
                                <input type="number" class="form-control form-control-sm d-inline" style="width:80px"
                                       wire:model.live="discount" min="0">
                                MAD
                            </th>
                            <td class="text-right text-danger">- {{ number_format($discount, 2) }} MAD</td>
                        </tr>
                        <tr>
                            <th>
                                Couverture assurance
                                <input type="number" class="form-control form-control-sm d-inline" style="width:80px"
                                       wire:model.live="insurance_coverage" min="0">
                                MAD
                            </th>
                            <td class="text-right text-info">- {{ number_format($insurance_coverage, 2) }} MAD</td>
                        </tr>
                        <tr class="bg-primary text-white">
                            <th>TOTAL</th>
                            <td class="text-right"><strong>{{ number_format($this->total, 2) }} MAD</strong></td>
                        </tr>
                        <tr>
                            <th>
                                Montant payé
                                <input type="number" class="form-control form-control-sm d-inline" style="width:100px"
                                       wire:model.live="amount_paid" min="0">
                            </th>
                            <td class="text-right text-success">{{ number_format($amount_paid, 2) }} MAD</td>
                        </tr>
                        <tr>
                            <th>Reste à payer</th>
                            <td class="text-right {{ $this->amountDue > 0 ? 'text-danger' : 'text-success' }}">
                                <strong>{{ number_format($this->amountDue, 2) }} MAD</strong>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="m-t-10">
                <button wire:click="save" class="btn btn-primary">
                    <i class="feather icon-save"></i> Enregistrer
                </button>
                <button wire:click="closeForm" class="btn btn-default m-l-10">Annuler</button>
            </div>
        </div>
    </div>
    @endif

    {{-- Liste des paiements --}}
    <div class="card">
        <div class="card-header">
            <h5>Historique des paiements</h5>
        </div>
        <div class="card-block">
            <div class="table-responsive">
                <table class="table table-hover table-bordered">
                    <thead>
                        <tr>
                            <th>N° Facture</th>
                            <th>Patient</th>
                            <th>Date</th>
                            <th>Total</th>
                            <th>Payé</th>
                            <th>Reste</th>
                            <th>Mode</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($this->payments as $payment)
                        @php
                            $statusColors = ['paid'=>'label-success','partial'=>'label-warning','pending'=>'label-danger','cancelled'=>'label-default','refunded'=>'label-info'];
                            $statusLabels = ['paid'=>'Payé','partial'=>'Partiel','pending'=>'Impayé','cancelled'=>'Annulé','refunded'=>'Remboursé'];
                            $methodLabels = ['cash'=>'Espèces','card'=>'Carte','transfer'=>'Virement','insurance'=>'Assurance','check'=>'Chèque'];
                        @endphp
                        <tr>
                            <td><strong>{{ $payment->invoice_number }}</strong></td>
                            <td>
                                <a href="{{ route('patients.show', $payment->patient_id) }}">
                                    {{ $payment->patient->full_name }}
                                </a>
                            </td>
                            <td>{{ $payment->payment_date ? \Carbon\Carbon::parse($payment->payment_date)->format('d/m/Y') : $payment->created_at->format('d/m/Y') }}</td>
                            <td>{{ number_format($payment->total_amount, 2) }} MAD</td>
                            <td class="text-success">{{ number_format($payment->amount_paid, 2) }} MAD</td>
                            <td class="{{ $payment->amount_due > 0 ? 'text-danger' : '' }}">
                                {{ number_format($payment->amount_due, 2) }} MAD
                            </td>
                            <td>{{ $methodLabels[$payment->payment_method] ?? $payment->payment_method }}</td>
                            <td>
                                <span class="label {{ $statusColors[$payment->status] ?? 'label-default' }}">
                                    {{ $statusLabels[$payment->status] ?? $payment->status }}
                                </span>
                            </td>
                            <td>
                                <a href="{{ route('payments.pdf', $payment->id) }}"
                                   target="_blank" class="btn btn-primary btn-xs" title="PDF">
                                    <i class="feather icon-printer"></i>
                                </a>
                                <button wire:click="openEdit({{ $payment->id }})"
                                        class="btn btn-warning btn-xs" title="Modifier">
                                    <i class="feather icon-edit"></i>
                                </button>
                                <button wire:click="confirmDelete({{ $payment->id }})"
                                        class="btn btn-danger btn-xs" title="Supprimer">
                                    <i class="feather icon-trash-2"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted p-20">
                                <i class="feather icon-credit-card" style="font-size:2em;"></i><br>
                                Aucun paiement trouvé.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="m-t-10">
                {{ $this->payments->links() }}
            </div>
        </div>
    </div>

    {{-- Modal suppression --}}
    @if($confirmingDeleteId ?? false)
    <div class="modal fade in" style="display:block;background:rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header"><h4 class="modal-title">Confirmer la suppression</h4></div>
                <div class="modal-body"><p>Supprimer ce paiement définitivement ?</p></div>
                <div class="modal-footer">
                    <button wire:click="cancelDelete" class="btn btn-default">Annuler</button>
                    <button wire:click="delete" class="btn btn-danger"><i class="feather icon-trash-2"></i> Supprimer</button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
