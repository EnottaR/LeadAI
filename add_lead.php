<?php
// Il db nel server ha una discrepanza nell'orario
// Potrebbe essere necessario aggiornare direttamente il date_format del server
// Settando il timezone in Europa riesco ad avvicinarmi quanto posso, ma i lead sono segnati con orari non veri, 1 ora indietro
date_default_timezone_set('Europe/Rome');

$pdo = require_once 'db.php';

function getClientIP() {
    // Pesco l'ip dell'utente e verifico se è proxato oppure dietro un bilanciatore di carico
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        // necessario: rilevo più indirizzi IP? Prendo solo il primo per valido
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        // Nessun proxy, vado con l'IP remoto
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    
    // followup della riga 11
    if (strpos($ip, ',') !== false) {
        $ip = explode(',', $ip)[0];
    }
    return trim($ip);
}

// Qui intercetto l'urser agent del browser e lo stampo tra i lead
function getBrowserInfo() {
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Non disponibile';
    
    // Se ho commesso degli errori, l'user agent è "sconosciuto"
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
    
    // Recupero i dati OS
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

// NUOVA FUNZIONE: Determina la tipologia del lead basata sull'URL di origine
function getLeadType($referer_url) {
    if (empty($referer_url)) {
        return 'Semplice/Organico';
    }
    
    // Controlla se l'URL contiene /gad dopo il dominio
    if (preg_match('/\.com\/gad/i', $referer_url)) {
        return 'Google ADS';
    }
    
    return 'Semplice/Organico';
}

$name = $_POST['name'] ?? '';
$surname = $_POST['surname'] ?? '';
$email = $_POST['email'] ?? '';
$phone = $_POST['phone'] ?? '';
$message = $_POST['message'] ?? '';
$ip = getClientIP();
$browser_info = getBrowserInfo();
$lead_source = $_SERVER['HTTP_REFERER'] ?? 'Non disponibile'; // URL completo della pagina
$clients_id = $_POST['clients_id'] ?? ''; // clients_id passato dal form

// NUOVA VARIABILE: Determina la tipologia del lead
$lead_type = getLeadType($lead_source);

// verifico se il cliente esiste in base al clients_id
$queryClient = "SELECT id, encryption_key, email FROM clients WHERE id = :clients_id LIMIT 1";
$stmtClient = $pdo->prepare($queryClient);
$stmtClient->bindParam(':clients_id', $clients_id);
$stmtClient->execute();
$client = $stmtClient->fetch(PDO::FETCH_ASSOC);

if ($client) {
    // cifratura dei dati sensibili (telefono e messaggio)
    $encryption_key = $client['encryption_key'];
    $iv = openssl_random_pseudo_bytes(16); // IV casuale di 16 byte, autogenerato

    // dati sensibili cifrati
    $encryptedPhone = openssl_encrypt($phone, 'aes-256-cbc', $encryption_key, 0, $iv);
    $encryptedMessage = openssl_encrypt($message, 'aes-256-cbc', $encryption_key, 0, $iv);

    // Check se la persona già esiste nel db
    $queryPersonas = "SELECT id FROM personas WHERE email = :email LIMIT 1";
    $stmtPersonas = $pdo->prepare($queryPersonas);
    $stmtPersonas->bindParam(':email', $email);
    $stmtPersonas->execute();
    $persona = $stmtPersonas->fetch(PDO::FETCH_ASSOC);

    // Se esiste, uso il suo personas_id esistente
    if ($persona) {
        $personas_id = $persona['id'];
    } else {
        // se la persona non esiste, la inserisco
        $queryInsertPersonas = "INSERT INTO personas (name, surname, email, created_at) 
                                 VALUES (:name, :surname, :email, NOW())";
        $stmtInsertPersonas = $pdo->prepare($queryInsertPersonas);
        $stmtInsertPersonas->bindParam(':name', $name);
        $stmtInsertPersonas->bindParam(':surname', $surname);
        $stmtInsertPersonas->bindParam(':email', $email);

        if ($stmtInsertPersonas->execute()) {
            $personas_id = $pdo->lastInsertId();
        } else {
            echo "errore nell'inserimento della persona.";
            exit;
        }
    }

    // CORREZIONE: Estrai solo il dominio per il controllo del sito web, 
    // ma mantieni l'URL completo per lead_source_url
    $refererDomain = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST);
    
    if ($refererDomain) {
        // Pulisco il dominio dal www se presente
        $refererDomain = preg_replace('/^www\./', '', $refererDomain);
    } else {
        echo "errore: nessun referer trovato.";
        exit;
    }

    // Controllo che il sito inserito sia di proprietà del cliente usando SOLO il dominio
    $queryWebsite = "SELECT id, name, url FROM websites WHERE url LIKE :url AND clients_id = :clients_id LIMIT 1";
    $stmtWebsite = $pdo->prepare($queryWebsite);

    // Usa la wildcard per LIKE nel parametro, li wrappo con % intorno al valore,
    // questo nel caso per qualche assurdo motivo l'url mi viene passato "smontato"
    $likeUrl = '%' . $refererDomain . '%';

    $stmtWebsite->bindParam(':url', $likeUrl);
    $stmtWebsite->bindParam(':clients_id', $client['id']);
    $stmtWebsite->execute();
    $website = $stmtWebsite->fetch(PDO::FETCH_ASSOC);

    if ($website) {
        // CORREZIONE: Salva l'URL COMPLETO (non solo il dominio) in lead_source_url
        $insertLeadQuery = "INSERT INTO leads (phone, message, ip, status_id, created_at, clients_id, personas_id, websites_id, iv, lead_source_url, lead_type)
                            VALUES (:phone, :message, :ip, 1, NOW(), :clients_id, :personas_id, :websites_id, :iv, :lead_source_url, :lead_type)";
        $stmtLead = $pdo->prepare($insertLeadQuery);

        $stmtLead->bindParam(':phone', $encryptedPhone);
        $stmtLead->bindParam(':message', $encryptedMessage);
        $stmtLead->bindParam(':ip', $ip);
        $stmtLead->bindParam(':clients_id', $client['id']);
        $stmtLead->bindParam(':personas_id', $personas_id);
        $stmtLead->bindParam(':websites_id', $website['id']);
        $stmtLead->bindParam(':iv', $iv);
        $stmtLead->bindParam(':lead_source_url', $lead_source); // URL COMPLETO
        $stmtLead->bindParam(':lead_type', $lead_type);

        if ($stmtLead->execute()) {
            echo "lead inserito con successo!";
            
            // EMAIL DI NOTIFICA AL PROPRIETARIO DELL'ACCOUNT
            // Dovrebbe funzionare correttamente, sto effettuando test ma non mi capacito:
            // mg-adv.com ha un mailer? E' una cosa abbastanza comune che mi ritrovo con i siti in production,
            // alcuni form a volte non caricano l'header della mail e vengono bloccati oppure finiscono in spam
            $client_email = $client['email'];
            $website_name = $website['name'];
            
            $subject = "Nuovo LEAD - " . $website_name . " (" . $lead_type . ")";
            
            $email_body = "Questi i dati inseriti nel modulo presente alla pagina " . $lead_source . " da utente con indirizzo IP: " . $ip . " e browser/sistema operativo " . $browser_info . "\n\n";
            $email_body .= "🎯 TIPOLOGIA LEAD: " . $lead_type . "\n";
            $email_body .= "🌐 URL ORIGINE: " . $lead_source . "\n\n";
            $email_body .= "Dati Inseriti:\n";
            $email_body .= "lead_source: " . $lead_source . "\n";
            $email_body .= "first_name: " . $name . "\n";
            $email_body .= "last_name: " . $surname . "\n";
            $email_body .= "email: " . $email . "\n";
            $email_body .= "phone: " . $phone . "\n";
            $email_body .= "description: " . $message . "\n";
            $email_body .= "privacy: on\n\n";
            $email_body .= "---\n";
            $email_body .= "Questo messaggio è stato generato automaticamente da LeadAI.\n";
            $email_body .= "Per gestire questo lead, accedi al tuo pannello di controllo.";
            
            $headers = array();
            $headers[] = "MIME-Version: 1.0";
            $headers[] = "Content-type: text/plain; charset=UTF-8";
            $headers[] = "From: LeadAI System <noreply@mg-adv.com>";
            $headers[] = "Reply-To: noreply@mg-adv.com";
            $headers[] = "Return-Path: noreply@mg-adv.com";
            $headers[] = "X-Mailer: PHP/" . phpversion();
            $headers[] = "X-Priority: 1";
            $headers[] = "X-MSMail-Priority: High";
            $headers[] = "Importance: High";
            
            $headers_string = implode("\r\n", $headers);
            
            $mail_sent = mail($client_email, $subject, $email_body, $headers_string);
            
            if ($mail_sent) {
                echo "\nEmail di notifica inviata con successo a: " . $client_email;
                error_log("LeadAI: Email inviata a " . $client_email . " - " . date('Y-m-d H:i:s'));
            } else {
                echo "\nErrore nell'invio dell'email di notifica.";
                error_log("LeadAI: Errore invio email a " . $client_email . " - " . date('Y-m-d H:i:s'));
            }
            
        } else {
            echo "errore nell'inserimento del lead.";
        }
    } else {
        echo "il sito non appartiene al cliente.";
    }
} else {
    echo "cliente non trovato.";
}
?>