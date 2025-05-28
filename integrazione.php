<?php
require 'includes/auth-checker.php';
$pageTitle = "Integrazione | LeadAI";
include 'includes/parts/header.php';
require 'includes/db.php';

if (!isset($_SESSION['loggedin']) || !isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$client_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT name, surname, company FROM clients WHERE id = ?");
$stmt->bind_param("i", $client_id);
$stmt->execute();
$stmt->bind_result($client_name, $client_surname, $client_company);
$stmt->fetch();
$stmt->close();

$stmt = $conn->prepare("SELECT name, url FROM websites WHERE clients_id = ?");
$stmt->bind_param("i", $client_id);
$stmt->execute();
$stmt->bind_result($website_name, $website_url);
$stmt->fetch();
$stmt->close();

// URL base del sistema (DA CONFIGURARE)
$base_url = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']);
$form_action_url = $base_url . '/add_lead.php';
$api_endpoint_url = $base_url . '/includes/functions/api_lead_receiver.php';

$display_name = $client_company ?: ($client_name . ' ' . $client_surname);
?>

<body>
    <?php $current_page = basename($_SERVER['PHP_SELF']); ?>
    <div class="app-container">
        <?php include 'includes/parts/head.php'; ?>
        <div class="app-content">
            <?php include 'includes/parts/sidebar.php'; ?>
            <div class="projects-section" style="overflow: auto;">
                <div class="projects-section-header">
                    <p>Integrare LeadAI nel tuo form</p>
                </div>

                <div class="integration-layout">
                    <div class="integration-sidebar">
                        <div class="alert-box">
                            <h4 style="padding-left: 25px;"><i class="fa-solid fa-triangle-exclamation" style="color: #ffc107"></i> Informazioni</h4>
                            <ul style="line-height: 25px;">
                                <li><strong>Client ID:</strong> Il tuo ID cliente è <code><?= $client_id ?></code></li>
                                <li><strong>Dominio verificato:</strong> <?= htmlspecialchars($website_url ?: 'Non configurato') ?></li>
                                <li><strong>Sicurezza:</strong> I dati sensibili (telefono e messaggio) vengono crittografati automaticamente</li>
                                <li><strong>Notifiche:</strong> Riceverai un'email per ogni nuovo lead a: <?= htmlspecialchars($_SESSION['email']) ?></li>
                            </ul>
                        </div>
                        
                        <div class="info-box">
                            <h4><i class="fa-solid fa-user"></i> Riepilogo del tuo account</h4>
                            <div class="stat-item">
                                <span class="stat-label">Azienda:</span>
                                <span class="stat-value"><?= htmlspecialchars($display_name) ?></span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-label">Sito web:</span>
                                <span class="stat-value"><?= htmlspecialchars($website_name ?: 'Non configurato') ?></span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-label">Endpoint API:</span>
                                <span class="stat-value" style="color: #2fbf3e;">Attivo</span>
                            </div>
                        </div>

                        <div class="help-box">
                            <h4><i class="fa-solid fa-circle-info"></i> Hai bisogno di aiuto?</h4>
                            <p>Se hai difficoltà nell'integrazione, consulta la <a href="docs/documentazione.html" target="_blank">documentazione completa</a> o contatta il supporto.</p>
                            <div class="help-links">
                                <a href="docs/documentazione.html" target="_blank" class="help-link">
                                    <i class="fas fa-book"></i> Documentazione
                                </a>
                                <a href="mailto:support@leadai.com" class="help-link">
                                    <i class="fas fa-envelope"></i> Supporto
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="integration-content">
                        <div class="settings-tabs">
                            <div class="settings-tab active" data-tab="html">Form HTML</div>
                            <div class="settings-tab" data-tab="javascript">JavaScript API</div>
                            <div class="settings-tab" data-tab="wordpress">WordPress</div>
                            <div class="settings-tab" data-tab="webhook">Webhook</div>
                        </div>

                        <div class="sezione-impostazioni">
                    <div id="html-panel" class="settings-panel active">
                        <div class="settings-box">
                            <h3><i class="fab fa-html5" style="color: #ef7e50"></i> Form HTML</h3>
                            <p class="settings-desc">LeadAI può essere integrato in qualsiasi form html del tuo sito web.<br>
								Inserisci il campo nascosto come indicato nell'esempio di seguito ed il form invierà i dati direttamente al sistema.</p>
                            
                            <div class="code-container">
                                <div class="code-header">
                                    <span>il-tuo-form-contatto.html</span>
                                    <button class="copy-btn" onclick="copyToClipboard('html-code')">
                                        <i class="fas fa-copy"></i> Copia
                                    </button>
                                </div>
                                <pre id="html-code"><code>
&lt;form action="<?= $form_action_url ?>" method="POST" class="leadai-form"&gt;
    &lt;!-- Inserisci questo campo nascosto con identificazione cliente --&gt;
    &lt;input type="hidden" name="clients_id" value="<?= $client_id ?>"&gt;
    
    &lt;div class="form-group"&gt;
        &lt;label for="name"&gt;Nome *&lt;/label&gt;
        &lt;input type="text" id="name" name="name" required&gt;
    &lt;/div&gt;
    
    &lt;div class="form-group"&gt;
        &lt;label for="surname"&gt;Cognome *&lt;/label&gt;
        &lt;input type="text" id="surname" name="surname" required&gt;
    &lt;/div&gt;
    
    &lt;div class="form-group"&gt;
        &lt;label for="email"&gt;Email *&lt;/label&gt;
        &lt;input type="email" id="email" name="email" required&gt;
    &lt;/div&gt;
    
    &lt;div class="form-group"&gt;
        &lt;label for="phone"&gt;Telefono *&lt;/label&gt;
        &lt;input type="tel" id="phone" name="phone" required&gt;
    &lt;/div&gt;
    
    &lt;div class="form-group"&gt;
        &lt;label for="message"&gt;Messaggio *&lt;/label&gt;
        &lt;textarea id="message" name="message" rows="4" required&gt;&lt;/textarea&gt;
    &lt;/div&gt;
    
    &lt;button type="submit" class="submit-btn"&gt;Invia Richiesta&lt;/button&gt;
&lt;/form&gt;
</code></pre>
                            </div>
                        </div>
                    </div>

                    <!-- JavaScript API -->
                    <div id="javascript-panel" class="settings-panel">
                        <div class="settings-box">
                            <h3><i class="fa-brands fa-js" style="color: #ffc107"></i> Integrazione JavaScript</h3>
                            <p class="settings-desc">Puoi integrare LeadAI tramite funzione Javascript ed inviare i dati tramite AJAX.<br>
							Perfetto per form personalizzati e applicazioni a singola pagina.</p>
                            
                            <div class="code-container">
                                <div class="code-header">
                                    <span>leadai-integration.js</span>
                                    <button class="copy-btn" onclick="copyToClipboard('js-code')">
                                        <i class="fas fa-copy"></i> Copia
                                    </button>
                                </div>
                                <pre id="js-code"><code>/**
 * Integrazione LeadAI per <?= htmlspecialchars($display_name) ?>
 * Client ID: <?= $client_id ?>
 */

class LeadAI {
    constructor() {
        this.apiUrl = '<?= $api_endpoint_url ?>';
        this.clientId = <?= $client_id ?>;
    }

    async inviaLead(formData) {
        try {
            const leadData = {
                name: formData.name || formData.nome,
                surname: formData.surname || formData.cognome,
                email: formData.email,
                phone: formData.phone || formData.telefono,
                message: formData.message || formData.messaggio,
                clients_id: this.clientId
            };

            const response = await fetch(this.apiUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(leadData)
            });

            const result = await response.json();

            if (result.success) {
                return {
                    success: true,
                    message: 'Lead inviato con successo!',
                    leadId: result.lead_id
                };
            } else {
                throw new Error(result.error || 'Errore sconosciuto');
            }

        } catch (error) {
            console.error('Errore invio lead:', error);
            return {
                success: false,
                message: 'Errore durante l\'invio: ' + error.message
            };
        }
    }

    // Metodo helper per form HTML esistenti
    attachToForm(formSelector) {
        const form = document.querySelector(formSelector);
        if (!form) {
            console.error('Form non trovato:', formSelector);
            return;
        }

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const formData = new FormData(form);
            const data = Object.fromEntries(formData.entries());
            
            const result = await this.inviaLead(data);
            
            if (result.success) {
                alert('Grazie! Il tuo messaggio è stato inviato.');
                form.reset();
            } else {
                alert('Errore: ' + result.message);
            }
        });
    }
}

// Inizializzazione
const leadAI = new LeadAI();

// Esempio di utilizzo:
/*
// Metodo 1: Dati da oggetto
const datiForm = {
    name: 'Mario',
    surname: 'Rossi',
    email: 'mario@example.com',
    phone: '+39 123 456 7890',
    message: 'Vorrei informazioni sui vostri servizi'
};
leadAI.inviaLead(datiForm).then(result => console.log(result));

// Oppure collegalo ad un form già esistente
leadAI.attachToForm('#mio-form');
*/</code></pre>
                            </div>
                        </div>
                    </div>

                    <!-- WordPress -->
                    <div id="wordpress-panel" class="settings-panel">
                        <div class="settings-box">
                            <h3><i class="fa-brands fa-wordpress"></i> Plugin WordPress</h3>
                            <p class="settings-desc">LeadAI è studiato appositamente per essere integrato utilizzando Contact Form 7 oppure tramite funzione personalizzata.</p>
                            
                            <div class="settings-box" style="margin-bottom: 20px;">
                                <h4>Contact Form 7</h4>
                                <div class="code-container">
                                    <div class="code-header">
                                        <span>contact-form-7-shortcode</span>
                                        <button class="copy-btn" onclick="copyToClipboard('cf7-code')">
                                            <i class="fas fa-copy"></i> Copia
                                        </button>
                                    </div>
                                    <pre id="cf7-code"><code>

&lt;label&gt; Nome (richiesto)
    [text* nome] &lt;/label&gt;

&lt;label&gt; Cognome (richiesto)
    [text* cognome] &lt;/label&gt;

&lt;label&gt; Email (richiesta)
    [email* email] &lt;/label&gt;

&lt;label&gt; Telefono (richiesto)
    [tel* telefono] &lt;/label&gt;

&lt;label&gt; Il tuo messaggio
    [textarea* messaggio] &lt;/label&gt;

[submit "Invia"]</code></pre>
                                </div>
                            </div>

                            <div class="settings-box">
                                <h4>Funzione PHP per functions.php</h4>
                                <div class="code-container">
                                    <div class="code-header">
                                        <span>functions.php</span>
                                        <button class="copy-btn" onclick="copyToClipboard('wp-php-code')">
                                            <i class="fas fa-copy"></i> Copia
                                        </button>
                                    </div>
                                    <pre id="wp-php-code"><code>/**
 * Integrazione LeadAI per WordPress
 * Aggiungi questo codice al file functions.php del tuo tema
 */

// Hook per Contact Form 7
add_action('wpcf7_mail_sent', 'invia_a_leadai');

function invia_a_leadai($contact_form) {
    $submission = WPCF7_Submission::get_instance();
    
    if ($submission) {
        $posted_data = $submission->get_posted_data();
        
        // Mappa i campi del tuo form
        $lead_data = array(
            'name' => $posted_data['nome'] ?? '',
            'surname' => $posted_data['cognome'] ?? '',
            'email' => $posted_data['email'] ?? '',
            'phone' => $posted_data['telefono'] ?? '',
            'message' => $posted_data['messaggio'] ?? '',
            'clients_id' => <?= $client_id ?> // Il tuo ID cliente
        );
        
        // Invia a LeadAI
        $response = wp_remote_post('<?= $api_endpoint_url ?>', array(
            'method' => 'POST',
            'headers' => array('Content-Type' => 'application/json'),
            'body' => json_encode($lead_data),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            error_log('Errore invio LeadAI: ' . $response->get_error_message());
        } else {
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);
            
            if (!$data['success']) {
                error_log('Errore LeadAI: ' . $data['error']);
            }
        }
    }
}

// Shortcode per form personalizzato
add_shortcode('leadai_form', 'leadai_form_shortcode');

function leadai_form_shortcode($atts) {
    $atts = shortcode_atts(array(
        'title' => 'Contattaci',
        'submit_text' => 'Invia Richiesta'
    ), $atts);
    
    ob_start();
    ?>
    &lt;form id="leadai-wp-form" method="post"&gt;
        &lt;input type="hidden" name="action" value="leadai_submit"&gt;
        &lt;input type="hidden" name="leadai_nonce" value="&lt;?php echo wp_create_nonce('leadai_nonce'); ?&gt;"&gt;
        
        &lt;h3&gt;&lt;?php echo esc_html($atts['title']); ?&gt;&lt;/h3&gt;
        
        &lt;p&gt;
            &lt;label&gt;Nome *&lt;/label&gt;
            &lt;input type="text" name="nome" required&gt;
        &lt;/p&gt;
        
        &lt;p&gt;
            &lt;label&gt;Cognome *&lt;/label&gt;
            &lt;input type="text" name="cognome" required&gt;
        &lt;/p&gt;
        
        &lt;p&gt;
            &lt;label&gt;Email *&lt;/label&gt;
            &lt;input type="email" name="email" required&gt;
        &lt;/p&gt;
        
        &lt;p&gt;
            &lt;label&gt;Telefono *&lt;/label&gt;
            &lt;input type="tel" name="telefono" required&gt;
        &lt;/p&gt;
        
        &lt;p&gt;
            &lt;label&gt;Messaggio *&lt;/label&gt;
            &lt;textarea name="messaggio" required&gt;&lt;/textarea&gt;
        &lt;/p&gt;
        
        &lt;p&gt;
            &lt;button type="submit"&gt;&lt;?php echo esc_html($atts['submit_text']); ?&gt;&lt;/button&gt;
        &lt;/p&gt;
    &lt;/form&gt;
    &lt;?php
    return ob_get_clean();
}</code></pre>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Webhook -->
                    <div id="webhook-panel" class="settings-panel">
                        <div class="settings-box">
                            <h3><i class="fa-solid fa-code"></i> Configurazione Webhook</h3>
                            <p class="settings-desc">Puoi integrare LeadAI tramite webhook a servizi esterni come Zapier, Typeform, Make.com, ecc.</p>
                            
                            <div class="settings-grid">
                                <div class="settings-box">
                                    <h4>URL Endpoint</h4>
                                    <div class="webhook-url-container">
                                        <input type="text" value="<?= $api_endpoint_url ?>" readonly class="webhook-url">
                                        <button class="copy-btn" onclick="copyToClipboard('webhook-url')">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="settings-box">
                                    <h4>Metodo HTTP</h4>
                                    <code>POST</code>
                                </div>
                            </div>
                            
                            <div class="settings-box">
                                <h4>Parametri richiesti (JSON)</h4>
                                <div class="code-container">
                                    <div class="code-header">
                                        <span>webhook-payload.json</span>
                                        <button class="copy-btn" onclick="copyToClipboard('webhook-json')">
                                            <i class="fas fa-copy"></i> Copia
                                        </button>
                                    </div>
                                    <pre id="webhook-json"><code>{
  "name": "Mario",
  "surname": "Rossi", 
  "email": "mario@example.com",
  "phone": "+39 123 456 7890",
  "message": "Vorrei informazioni sui vostri servizi",
  "clients_id": <?= $client_id ?>
}</code></pre>
                                </div>
                            </div>
                            
                            <div class="settings-box">
                                <h4>Headers richiesti</h4>
                                <div class="code-container">
                                    <div class="code-header">
                                        <span>HTTP Headers</span>
                                        <button class="copy-btn" onclick="copyToClipboard('webhook-headers')">
                                            <i class="fas fa-copy"></i> Copia
                                        </button>
                                    </div>
                                    <pre id="webhook-headers"><code>Content-Type: application/json
Accept: application/json</code></pre>
                                </div>
                            </div>

                            <div class="settings-box" style="border: 1px solid var(--message-box-border);">
                                <h4>Esempio di configurazione Zapier</h4>
                                <ol style="line-height: 1.8;">
                                    <li>Crea un nuovo Zap</li>
                                    <li>Scegli il trigger (es. "New Form Submission")</li>
                                    <li>Aggiungi azione "Webhooks by Zapier"</li>
                                    <li>Seleziona "POST"</li>
                                    <li>URL: <code><?= $api_endpoint_url ?></code></li>
                                    <li>Payload Type: JSON</li>
                                    <li>Mappa i campi come mostrato sopra</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="notification-container"></div>
    <script src="assets/js/dashboard.js"></script>
    <script src="assets/js/settings.js"></script>
    <script>
        function copyToClipboard(elementId) {
            const element = document.getElementById(elementId);
            const text = elementId === 'webhook-url' ? element.value : element.textContent;
            
            navigator.clipboard.writeText(text).then(() => {
                showNotification('📋 Codice copiato negli appunti!', 'success');
            }).catch(err => {
                console.error('Errore copia:', err);
                showNotification('⚠️ Errore durante la copia', 'error');
            });
        }
    </script>
</body>