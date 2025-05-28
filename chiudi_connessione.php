<?php
/**
 * Disconnessione Forzata Utente
 * Utilizzo: /chiudi_connessione.php?email=raffaele.bordo@outlook.it&token=SECURE_TOKEN
 */

require_once 'includes/db.php';
require_once 'includes/auth.php';

// Token di sicurezza statico (cambialo con qualcosa di più sicuro)
define('FORCE_DISCONNECT_TOKEN', 'LeadAI_2025_ForceLogout_RB');

// Header JSON per response API-style
header('Content-Type: application/json');

// Verifica che sia una richiesta GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Metodo non consentito. Utilizzare GET.',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit;
}

// Recupera parametri dalla URL
$email = isset($_GET['email']) ? trim($_GET['email']) : '';
$token = isset($_GET['token']) ? trim($_GET['token']) : '';

// Log dell'accesso per sicurezza
$log_message = "[" . date('Y-m-d H:i:s') . "] Force disconnect attempt for: " . ($email ?: 'NO_EMAIL') . " from IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN');
error_log($log_message, 3, __DIR__ . '/logs/chiudi_connessione.log');

// Validazione parametri
if (empty($email)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Parametro email mancante.',
        'usage' => 'chiudi_connessione.php?email=user@domain.com&token=YOUR_TOKEN',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit;
}

if (empty($token)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Token di sicurezza mancante.',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit;
}

// Verifica token di sicurezza
if ($token !== FORCE_DISCONNECT_TOKEN) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Token di sicurezza non valido.',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
    // Log tentativo non autorizzato
    error_log("[SECURITY] Invalid token attempt from IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN'), 3, __DIR__ . '/logs/chiudi_connessione.log');
    exit;
}

// Validazione formato email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Formato email non valido.',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit;
}

try {
    // Cerca l'utente nel database
    $stmt = $conn->prepare("SELECT id, name, surname, session_token, session_expires FROM clients WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Utente non trovato con email: ' . $email,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        $stmt->close();
        exit;
    }
    
    $user = $result->fetch_assoc();
    $stmt->close();
    
    // Verifica se l'utente ha una sessione attiva
    $has_active_session = !empty($user['session_token']) && 
                         !empty($user['session_expires']) && 
                         strtotime($user['session_expires']) > time();
    
    if (!$has_active_session) {
        echo json_encode([
            'success' => true,
            'message' => 'L\'utente ' . $user['name'] . ' ' . $user['surname'] . ' non ha sessioni attive.',
            'user_info' => [
                'id' => $user['id'],
                'name' => $user['name'] . ' ' . $user['surname'],
                'email' => $email,
                'had_session' => false
            ],
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        exit;
    }
    
    // Forza la disconnessione
    $disconnect_result = forceLogout($conn, $user['id']);
    
    if ($disconnect_result) {
        // Log dell'azione per audit
        $audit_message = "[SUCCESS] Force disconnect for user ID: " . $user['id'] . " (" . $email . ") from IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN');
        error_log($audit_message, 3, __DIR__ . '/logs/chiudi_connessione.log');
        
        echo json_encode([
            'success' => true,
            'message' => 'Sessione disconnessa con successo.',
            'user_info' => [
                'id' => $user['id'],
                'name' => $user['name'] . ' ' . $user['surname'],
                'email' => $email,
                'had_session' => true,
                'session_expired_at' => $user['session_expires']
            ],
            'action_performed' => 'session_terminated',
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    } else {
        throw new Exception('Errore durante la disconnessione della sessione.');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Errore interno: ' . $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
    // Log dell'errore
    error_log("[ERROR] Chiusura connessione fallita: " . $e->getMessage(), 3, __DIR__ . '/logs/chiudi_connessione.log');
} finally {
    $conn->close();
}
?>