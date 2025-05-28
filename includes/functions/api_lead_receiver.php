<?php
/**
 * API Lead Receiver - Endpoint per collegare LeadAI a qualsiasi form
 * LeadAI Project - 2025
 * Ancora in fase di testing, utilizzare con cautela
 */

date_default_timezone_set('Europe/Rome');

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Blocco l'accesso diretto all'api da URL
if (!isset($_SERVER['HTTP_USER_AGENT']) || 
    (isset($_SERVER['HTTP_SEC_FETCH_DEST']) && $_SERVER['HTTP_SEC_FETCH_DEST'] === 'document') ||
    (isset($_SERVER['HTTP_SEC_FETCH_MODE']) && $_SERVER['HTTP_SEC_FETCH_MODE'] === 'navigate')) {
    
    http_response_code(403);
    echo json_encode([
        'success' => false, 
        'error' => 'Accesso negato. Non puoi accedere a questa risorsa direttamente dal browser.',
        'code' => 'DIRECT_ACCESS_FORBIDDEN'
    ]);
    exit;
}

// Gestore preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Verifica che sia solo POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false, 
        'error' => 'Metodo non consentito. Questa API accetta solo richieste POST.',
        'allowed_methods' => ['POST']
    ]);
    exit;
}

// Controllo l'esistenza del fil db prima di procedere
$db_file = __DIR__ . '/db.php';
if (!file_exists($db_file)) {
    // Non ricordo se ho fatto una concatenazione, devo ricontrollare meglio *Raffaele
	// Ciclo tra le alternative (dovrebbero essere 2 file db)
    $alternative_paths = [
        '../db.php',
        '../../db.php',
        dirname(__DIR__) . '/db.php',
        dirname(dirname(__DIR__)) . '/db.php'
    ];
    
    $db_found = false;
    foreach ($alternative_paths as $path) {
        if (file_exists($path)) {
            $db_file = $path;
            $db_found = true;
            break;
        }
    }
    
    if (!$db_found) {
        http_response_code(500);
        echo json_encode([
            'success' => false, 
            'error' => 'Configurazione server non corretta. Contattare l\'amministratore.',
            'code' => 'CONFIG_ERROR'
        ]);
        exit;
    }
}

try {
    $pdo = require_once $db_file;
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'error' => 'Errore di connessione al database. Contattare l\'amministratore.',
        'code' => 'DB_CONNECTION_ERROR'
    ]);
    exit;
}

function logActivity($message, $level = 'INFO') {
    $log_dir = __DIR__ . '/logs';
    if (!is_dir($log_dir)) {
        $log_dir = dirname(__DIR__) . '/logs';
        if (!is_dir($log_dir)) {
            mkdir($log_dir, 0755, true);
        }
    }
    
    $timestamp = date('[Y-m-d H:i:s]');
    $logEntry = "$timestamp [$level] $message" . PHP_EOL;
    error_log($logEntry, 3, $log_dir . '/lead_api.log');
}

function getClientIP() {
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    
    if (strpos($ip, ',') !== false) {
        $ip = explode(',', $ip)[0];
    }
    return trim($ip);
}

function getBrowserInfo() {
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'API Request';
    
    // Rilevo se è una chiamata API
    if (strpos($userAgent, 'curl') !== false || 
        strpos($userAgent, 'Postman') !== false ||
        strpos($userAgent, 'API') !== false ||
        strpos($userAgent, 'webhook') !== false) {
        return 'API Call';
    }
    
    $browser = 'Sconosciuto';
    $os = 'Sconosciuto';
    
    if (strpos($userAgent, 'Chrome') !== false) {
        $browser = 'Chrome';
    } elseif (strpos($userAgent, 'Firefox') !== false) {
        $browser = 'Firefox';
    } elseif (strpos($userAgent, 'Safari') !== false) {
        $browser = 'Safari';
    } elseif (strpos($userAgent, 'Edge') !== false) {
        $browser = 'Edge';
    } elseif (strpos($userAgent, 'Opera') !== false) {
        $browser = 'Opera';
    }
    
    if (strpos($userAgent, 'Windows') !== false) {
        $os = 'Windows';
    } elseif (strpos($userAgent, 'Mac') !== false) {
        $os = 'macOS';
    } elseif (strpos($userAgent, 'Linux') !== false) {
        $os = 'Linux';
    } elseif (strpos($userAgent, 'Android') !== false) {
        $os = 'Android';
    } elseif (strpos($userAgent, 'iOS') !== false) {
        $os = 'iOS';
    }
    
    return $browser . ' su ' . $os;
}

function getLeadType($referer_url) {
    if (empty($referer_url)) {
        return 'Semplice/Organico';
    }
    
    // Controlla se l'URL contiene /gad dopo il dominio
    if (preg_match('/:\/\/[^\/]+\/gad/i', $referer_url)) {
        return 'Google ADS';
    }
    
    return 'Semplice/Organico';
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data && !empty($_POST)) {
    $data = $_POST;
}

if (!$data) {
    http_response_code(400);
    echo json_encode([
        'success' => false, 
        'error' => 'Nessun dato ricevuto. Assicurarsi di inviare i dati in formato JSON o form-data.',
        'code' => 'NO_DATA'
    ]);
    logActivity("Nessun dato ricevuto - IP: " . getClientIP(), 'ERROR');
    exit;
}

$required_fields = ['name', 'surname', 'email', 'phone', 'message', 'clients_id'];
$missing_fields = [];

foreach ($required_fields as $field) {
    if (empty($data[$field])) {
        $missing_fields[] = $field;
    }
}

if (!empty($missing_fields)) {
    http_response_code(400);
    echo json_encode([
        'success' => false, 
        'error' => 'Campi obbligatori mancanti: ' . implode(', ', $missing_fields),
        'code' => 'MISSING_FIELDS',
        'required_fields' => $required_fields
    ]);
    logActivity("Campi mancanti: " . implode(', ', $missing_fields) . " - IP: " . getClientIP(), 'ERROR');
    exit;
}

// Sanifico i dati
$name = htmlspecialchars(trim($data['name']));
$surname = htmlspecialchars(trim($data['surname']));
$email = filter_var(trim($data['email']), FILTER_VALIDATE_EMAIL);
$phone = htmlspecialchars(trim($data['phone']));
$message = htmlspecialchars(trim($data['message']));
$clients_id = intval($data['clients_id']);

$lead_source = $_SERVER['HTTP_REFERER'] ?? 'API Call';
$lead_type = getLeadType($lead_source);

if (!$email) {
    http_response_code(400);
    echo json_encode([
        'success' => false, 
        'error' => 'Formato email non valido',
        'code' => 'INVALID_EMAIL'
    ]);
    logActivity("Email non valida: " . $data['email'], 'ERROR');
    exit;
}

if (strlen($name) > 100 || strlen($surname) > 100) {
    http_response_code(400);
    echo json_encode([
        'success' => false, 
        'error' => 'Nome o cognome troppo lungo (massimo 100 caratteri)',
        'code' => 'FIELD_TOO_LONG'
    ]);
    exit;
}

if (strlen($message) > 2000) {
    http_response_code(400);
    echo json_encode([
        'success' => false, 
        'error' => 'Messaggio troppo lungo (massimo 2000 caratteri)',
        'code' => 'MESSAGE_TOO_LONG'
    ]);
    exit;
}

try {
    $queryClient = "SELECT id, encryption_key, email FROM clients WHERE id = :clients_id LIMIT 1";
    $stmtClient = $pdo->prepare($queryClient);
    $stmtClient->bindParam(':clients_id', $clients_id);
    $stmtClient->execute();
    $client = $stmtClient->fetch(PDO::FETCH_ASSOC);
    
    if (!$client) {
        http_response_code(404);
        echo json_encode([
            'success' => false, 
            'error' => 'Cliente non trovato o non autorizzato',
            'code' => 'CLIENT_NOT_FOUND'
        ]);
        logActivity("Cliente non trovato: $clients_id - IP: " . getClientIP(), 'ERROR');
        exit;
    }
    
    $encryption_key = $client['encryption_key'];
    $iv = openssl_random_pseudo_bytes(16);
    $encryptedPhone = openssl_encrypt($phone, 'aes-256-cbc', $encryption_key, 0, $iv);
    $encryptedMessage = openssl_encrypt($message, 'aes-256-cbc', $encryption_key, 0, $iv);
    
    $queryPersonas = "SELECT id FROM personas WHERE email = :email LIMIT 1";
    $stmtPersonas = $pdo->prepare($queryPersonas);
    $stmtPersonas->bindParam(':email', $email);
    $stmtPersonas->execute();
    $persona = $stmtPersonas->fetch(PDO::FETCH_ASSOC);
    
    if ($persona) {
        $personas_id = $persona['id'];
        logActivity("Persona esistente trovata: $email (ID: $personas_id)");
    } else {
        $queryInsertPersonas = "INSERT INTO personas (name, surname, email, created_at) VALUES (:name, :surname, :email, NOW())";
        $stmtInsertPersonas = $pdo->prepare($queryInsertPersonas);
        $stmtInsertPersonas->bindParam(':name', $name);
        $stmtInsertPersonas->bindParam(':surname', $surname);
        $stmtInsertPersonas->bindParam(':email', $email);
        
        if ($stmtInsertPersonas->execute()) {
            $personas_id = $pdo->lastInsertId();
            logActivity("Nuova persona creata: $email (ID: $personas_id)");
        } else {
            throw new Exception("Errore nell'inserimento della persona");
        }
    }
    
    $queryWebsite = "SELECT id, name FROM websites WHERE clients_id = :clients_id LIMIT 1";
    $stmtWebsite = $pdo->prepare($queryWebsite);
    $stmtWebsite->bindParam(':clients_id', $clients_id);
    $stmtWebsite->execute();
    $website = $stmtWebsite->fetch(PDO::FETCH_ASSOC);
    
    if (!$website) {
        http_response_code(404);
        echo json_encode([
            'success' => false, 
            'error' => 'Nessun sito web configurato per questo cliente',
            'code' => 'NO_WEBSITE_CONFIGURED'
        ]);
        logActivity("Nessun sito configurato per cliente: $clients_id", 'ERROR');
        exit;
    }
    
    $ip = getClientIP();
    $insertLeadQuery = "INSERT INTO leads (phone, message, ip, status_id, created_at, clients_id, personas_id, websites_id, iv, lead_source_url, lead_type) VALUES (:phone, :message, :ip, 1, NOW(), :clients_id, :personas_id, :websites_id, :iv, :lead_source_url, :lead_type)";
    $stmtLead = $pdo->prepare($insertLeadQuery);
    $stmtLead->bindParam(':phone', $encryptedPhone);
    $stmtLead->bindParam(':message', $encryptedMessage);
    $stmtLead->bindParam(':ip', $ip);
    $stmtLead->bindParam(':clients_id', $clients_id);
    $stmtLead->bindParam(':personas_id', $personas_id);
    $stmtLead->bindParam(':websites_id', $website['id']);
    $stmtLead->bindParam(':iv', $iv);
    $stmtLead->bindParam(':lead_source_url', $lead_source);
    $stmtLead->bindParam(':lead_type', $lead_type);
    
    if ($stmtLead->execute()) {
        $lead_id = $pdo->lastInsertId();
        
        $client_email = $client['email'];
        $website_name = $website['name'];
        $browser_info = getBrowserInfo();
        
        $subject = "🚀 Nuovo Lead ricevuto via API - " . $website_name . " (" . $lead_type . ")"; // Aggiungo tipologia
        $email_body = "Hai ricevuto un nuovo lead tramite API:\n\n";
        $email_body .= "📋 DATI LEAD:\n";
        $email_body .= "━━━━━━━━━━━━━━━━━━━━\n";
        $email_body .= "🎯 TIPOLOGIA: $lead_type\n"; // NUOVA RIGA
        $email_body .= "👤 Nome: $name $surname\n";
        $email_body .= "📧 Email: $email\n";
        $email_body .= "📱 Telefono: $phone\n";
        $email_body .= "💬 Messaggio: $message\n\n";
        $email_body .= "🌐 DETTAGLI TECNICI:\n";
        $email_body .= "━━━━━━━━━━━━━━━━━━━━\n";
        $email_body .= "🌍 IP: $ip\n";
        $email_body .= "🖥️ Browser/OS: $browser_info\n";
        $email_body .= "🔗 Fonte: $lead_source\n";
        $email_body .= "🆔 Lead ID: $lead_id\n";
        $email_body .= "⏰ Data/Ora: " . date('d/m/Y H:i:s') . "\n\n";
        $email_body .= "─────────────────────\n";
        $email_body .= "Accedi al tuo pannello LeadAI per gestire questo lead.\n";
        $email_body .= "Questo messaggio è stato generato automaticamente alla compilazione del form.";
        
        $headers = array();
        $headers[] = "MIME-Version: 1.0";
        $headers[] = "Content-type: text/plain; charset=UTF-8";
        $headers[] = "From: LeadAI API <api@leadai.com>";
        $headers[] = "Reply-To: noreply@leadai.com";
        $headers[] = "X-Mailer: LeadAI API System";
        $headers[] = "X-Priority: 2";
        
        $headers_string = implode("\r\n", $headers);
        
        $mail_sent = mail($client_email, $subject, $email_body, $headers_string);
        
        if ($mail_sent) {
            logActivity("Email notifica inviata a: $client_email per lead ID: $lead_id");
        } else {
            logActivity("Errore invio email a: $client_email per lead ID: $lead_id", 'WARNING');
        }
        
        $response = [
            'success' => true,
            'message' => 'Lead salvato con successo',
            'data' => [
                'lead_id' => $lead_id,
                'timestamp' => date('Y-m-d H:i:s'),
                'client_id' => $clients_id,
                'persona_id' => $personas_id,
                'lead_type' => $lead_type,
                'email_sent' => $mail_sent
            ]
        ];
        
        echo json_encode($response);
        logActivity("Lead salvato con successo - ID: $lead_id, Email: $email, Cliente: $clients_id, Tipo: $lead_type");
        
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false, 
            'error' => 'Errore nel salvataggio del lead',
            'code' => 'SAVE_ERROR'
        ]);
        logActivity("Errore nel salvataggio del lead per cliente: $clients_id", 'ERROR');
    }
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'error' => 'Errore del database. Riprova più tardi.',
        'code' => 'DATABASE_ERROR'
    ]);
    logActivity("Errore PDO: " . $e->getMessage(), 'CRITICAL');
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'error' => 'Errore interno del server. Contattare l\'amministratore.',
        'code' => 'INTERNAL_ERROR'
    ]);
    logActivity("Eccezione generale: " . $e->getMessage(), 'CRITICAL');
}
?>