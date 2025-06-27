<p class="settings-subtitle">Gestisci le impostazioni del tuo account e le preferenze</p>

<div class="settings-tabs">
    <div class="settings-tab active" data-tab="profile">Dettagli del profilo</div>
    <div class="settings-tab" data-tab="password">Password</div>
    <div class="settings-tab" data-tab="email">Email</div>
</div>

<div class="sezione-impostazioni">
    <div id="profile-panel" class="settings-panel active">
        <div class="settings-grid">
            <div class="settings-box">
                <h3>Nome</h3>
                <div class="settings-column-right">
                    <?= htmlspecialchars($_SESSION['name'] ?? 'Ospite'); ?>
                </div>
            </div>
            
            <div class="settings-box">
                <h3>Cognome</h3>
                <div class="settings-column-right">
                    <?= htmlspecialchars($_SESSION['surname'] ?? 'Ospite'); ?>
                </div>
            </div>
        </div>
        
        <div class="settings-box">
            <h3>Azienda</h3>
            <div class="settings-column-right">
                <div id="company-display" style="display: flex; align-items: center;">
                    <span id="current-company"><?= htmlspecialchars($company_name ?? ''); ?></span>
                    <button id="edit-company-btn" class="btn-secondary" style="margin-left: 10px;">
                        <i class="far fa-edit"></i> Aggiorna
                    </button>
                </div>
                <div id="company-edit-container" style="display: none; margin-top: 10px;">
                    <input type="text" id="user-company" class="settings-input">
                    <button id="save-company-btn" class="btn-secondary">
                        <i class="fa-regular fa-floppy-disk"></i> Salva
                    </button>
                    <button id="cancel-company-btn" class="btn-primary">
                        <i class="fas fa-times"></i> Annulla
                    </button>
                </div>
            </div>
        </div>
        
        <div class="settings-grid" style="margin-top: 25px;">
            <div class="settings-box">
                <h3>Nome del tuo sito</h3>
                <div class="settings-column-right">
                    <div id="website-name-display" style="display: flex; align-items: center;">
                        <span id="current-website-name"><?= htmlspecialchars($website_name ?? ''); ?></span>
                        <button id="edit-website-name-btn" class="btn-secondary" style="margin-left: 10px;">
                            <i class="far fa-edit"></i> Aggiorna
                        </button>
                    </div>
                    <div id="website-name-edit-container" style="display: none; margin-top: 10px;">
                        <input type="text" id="user-website-name" class="settings-input">
                        <button id="save-website-name-btn" class="btn-secondary">
                            <i class="fa-regular fa-floppy-disk"></i> Salva
                        </button>
                        <button id="cancel-website-name-btn" class="btn-primary">
                            <i class="fas fa-times"></i> Annulla
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="settings-box">
                <h3>URL del tuo sito</h3>
                <div class="settings-column-right">
                    <div id="website-url-display" style="display: flex; align-items: center;">
                        <span id="current-website-url"><?= htmlspecialchars($website_url ?? ''); ?></span>
                        <button id="edit-website-url-btn" class="btn-secondary" style="margin-left: 10px;">
                            <i class="far fa-edit"></i> Aggiorna
                        </button>
                    </div>
                    <div id="website-url-edit-container" style="display: none; margin-top: 10px;">
                        <input type="url" id="user-website-url" class="settings-input">
                        <button id="save-website-url-btn" class="btn-secondary">
                            <i class="fa-regular fa-floppy-disk"></i> Salva
                        </button>
                        <button id="cancel-website-url-btn" class="btn-primary">
                            <i class="fas fa-times"></i> Annulla
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="settings-footer">
            <div class="settings-row">
                <div class="settings-column-left">
                    <button id="logout-button" class="btn-primary"><i
                            class="fa-solid fa-arrow-right-from-bracket"></i> Disconnetti</button>
                    <p class="settings-desc" style="color: #e03f3f">Chiudi la sessione, dovrai effettuare nuovamente l'accesso.</p>
                </div>
            </div>
        </div>
    </div>

<div id="password-panel" class="settings-panel">
    <div class="settings-box">
        <h3>Modifica password</h3>
        <p class="settings-desc">La password viene utilizzata per accedere al tuo profilo.<br>
			Cambiala spesso e ricorda di usare una combinazione sicura con lettere maiuscole, minuscole, numeri e simboli.</p>
        <div class="settings-column-right">
            <div class="password-container">
                <button id="edit-password-btn" class="btn-secondary">
                    <i class="far fa-edit"></i> Cambia password
                </button>
                <div id="password-edit-container" style="display: none; margin-top: 10px;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <div class="float-label-container" style="flex: 1;">
                            <input type="password" id="user-password" placeholder=" " class="settings-input">
                            <label for="user-password">Nuova password</label>
                        </div>
                        <button id="save-password-btn" class="btn-secondary"><i class="fas fa-save"></i> Salva</button>
                        <button id="cancel-password-btn" class="btn-primary"><i class="fas fa-times"></i> Annulla</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

    <div id="email-panel" class="settings-panel">
        <div class="settings-box">
            <h3>Aggiorna indirizzo email</h3>
			                <p class="settings-desc">Utilizzato per le comunicazioni importanti riguardanti il tuo account<br>
								e per ricevere notifiche sui tuoi lead.</p>
            <div class="settings-column-right">
                <div id="email-display" style="display: flex; align-items: center;">
                    <span id="current-email">
                        <?= htmlspecialchars($_SESSION['email'] ?? ''); ?>
                    </span>
                    <button id="edit-email-btn" class="btn-secondary" style="margin-left: 10px;"><i class="far fa-edit"></i>
                        Aggiorna</button>
                </div>
                <div id="email-edit-container" style="display: none; margin-top: 10px;">
                    <input type="email" id="user-email" class="settings-input">
                    <button id="save-email-btn" class="btn-secondary"><i
                            class="fa-regular fa-floppy-disk"></i> Salva</button>
                    <button id="cancel-email-btn" class="btn-primary"><i class="fas fa-times"></i>
                        Annulla</button>
                </div>
            </div>
        </div>
    </div>
</div>