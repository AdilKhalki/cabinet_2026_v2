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
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form wire:submit="save">
        <div class="row">
            {{-- Cabinet Info --}}
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="feather icon-home"></i> Informations du Cabinet</h5>
                    </div>
                    <div class="card-block">
                        <div class="form-group">
                            <label>Nom du cabinet <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" wire:model="cabinet_name"
                                   placeholder="Cabinet Dr. Dupont">
                            @error('cabinet_name') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                        <div class="form-group">
                            <label>Adresse</label>
                            <textarea class="form-control" wire:model="cabinet_address" rows="2"
                                      placeholder="123 Rue de la Santé"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Ville</label>
                                    <input type="text" class="form-control" wire:model="cabinet_city"
                                           placeholder="Casablanca">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Téléphone <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" wire:model="cabinet_phone"
                                           placeholder="05 22 00 00 00">
                                    @error('cabinet_phone') <span class="text-danger small">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" class="form-control" wire:model="cabinet_email"
                                   placeholder="contact@cabinet.ma">
                            @error('cabinet_email') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>
            </div>

            {{-- Médecin Info --}}
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="feather icon-user"></i> Informations du Médecin</h5>
                    </div>
                    <div class="card-block">
                        <div class="form-group">
                            <label>Nom du médecin <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" wire:model="doctor_name"
                                   placeholder="Dr. Mohamed Alami">
                            @error('doctor_name') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                        <div class="form-group">
                            <label>Spécialité</label>
                            <input type="text" class="form-control" wire:model="doctor_speciality"
                                   placeholder="Médecin Généraliste">
                        </div>
                        <div class="form-group">
                            <label>N° INPE / Ordre</label>
                            <input type="text" class="form-control" wire:model="doctor_license"
                                   placeholder="12345">
                            @error('doctor_license') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            {{-- PDF & Notifications --}}
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="feather icon-file-text"></i> Options PDF</h5>
                    </div>
                    <div class="card-block">
                        <div class="form-group">
                            <label>Texte de pied de page (PDF)</label>
                            <textarea class="form-control" wire:model="pdf_footer_text" rows="3"
                                      placeholder="Horaires: Lundi-Vendredi 8h-18h"></textarea>
                            <small class="text-muted">Affiché en bas des ordonnances, factures et rapports PDF.</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="feather icon-bell"></i> Rappels RDV</h5>
                    </div>
                    <div class="card-block">
                        <div class="form-group">
                            <label>Rappel avant le RDV (heures) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" wire:model="appointment_reminder_hours"
                                   min="1" max="168">
                            <small class="text-muted">
                                Envoi automatique de rappels {{ $appointment_reminder_hours }}h avant chaque rendez-vous.
                            </small>
                            @error('appointment_reminder_hours') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <button type="submit" class="btn btn-primary">
                    <div wire:loading wire:target="save">
                        <span class="fa fa-spinner fa-spin"></span>
                    </div>
                    <i class="feather icon-save"></i> Enregistrer les paramètres
                </button>
                <a href="{{ route('home') }}" class="btn btn-default m-l-10">
                    Annuler
                </a>
            </div>
        </div>
    </form>
</div>
