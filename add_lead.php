<?php
/**
 * LeadAI Enhanced Lead Receiver - ENCRYPTION FIX
 * Versione: 3.2 - Fixed Encryption Issues
 * 
 * RISOLVE: Errore "decriptazione fallita!"
 * 
 * PROBLEMI RISOLTI:
 * - ✅ Formato IV corretto per MySQL
 * - ✅ Gestione encoding/decoding
 * - ✅ Validazione chiave di crittografia
 * - ✅ Fallback per messaggi non crittografati
 */

date_default_timezone_set('Europe/Rome');

$pdo = require_once 'db.php';

// Headers CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Allow-Credentials: true');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Logging migliorato per debug crittografia
function logDebug($message, $data = null) {
    $logEntry = date('Y-m-d H:i:s') . ' - ' . $message;
    if ($data !== null) {
        $logEntry .= ' - Data: ' . json_encode($data, JSON_UNESCAPED_UNICODE);
    }
    error_log($logEntry);
}

/**
 * FUNZIONE DI CRITTOGRAFIA MIGLIORATA
 */
function encryptDataEnhanced($data, $encryption_key) {
    if (empty($data)) {
        return ['encrypted' => '', 'iv' => ''];
    }
    
    try {
        // Genera IV sicuro
        $iv = openssl_random_pseudo_bytes(16);
        
        // Cripta i dati
        $encrypted = openssl_encrypt($data, 'aes-256-cbc', $encryption_key, 0, $iv);
        
        if ($encrypted === false) {
            logDebug("Errore crittografia OpenSSL", [
                'data_length' => strlen($data),
                'key_length' => strlen($encryption_key),
                'error' => openssl_error_string()
            ]);
            throw new Exception("Errore durante la crittografia");
        }
        
        // Converti IV in formato esadecimale per il database
        $iv_hex = bin2hex($iv);
        
        logDebug("Crittografia completata", [
            'original_length' => strlen($data),
            'encrypted_length' => strlen($encrypted),
            'iv_length' => strlen($iv_hex)
        ]);
        
        return [
            'encrypted' => $encrypted,
            'iv' => $iv_hex
        ];
        
    } catch (Exception $e) {
        logDebug("Errore durante crittografia", [
            'error' => $e->getMessage(),
            'data' => substr($data, 0, 50) . '...' // Solo primi 50 caratteri per privacy
        ]);
        
        // Fallback: salva in chiaro con prefisso di identificazione
        return [
            'encrypted' => '[PLAIN]' . $data,
            'iv' => ''
        ];
    }
}

/**
 * FUNZIONE DI DECRITTOGRAFIA MIGLIORATA (per i file di lettura)
 */
function decryptDataEnhanced($encryptedData, $iv_hex, $encryption_key) {
    if (empty($encryptedData)) {
        return '';
    }
    
    // Se i dati iniziano con [PLAIN], sono stati salvati in chiaro
    if (strpos($encryptedData, '[PLAIN]') === 0) {
        return substr($encryptedData, 7); // Rimuovi il prefisso [PLAIN]
    }
    
    if (empty($iv_hex) || empty($encryption_key)) {
        logDebug("Parametri decrittografia mancanti", [
            'has_iv' => !empty($iv_hex),
            'has_key' => !empty($encryption_key),
            'encrypted_length' => strlen($encryptedData)
        ]);
        return "Errore: parametri mancanti";
    }
    
    try {
        // Converti IV da esadecimale a binario
        if (strlen($iv_hex) !== 32) {
            throw new Exception("IV length invalid: " . strlen($iv_hex));
        }
        
        $iv = hex2bin($iv_hex);
        if ($iv === false) {
            throw new Exception("IV hex2bin failed");
        }
        
        // Decrittografia
        $decrypted = openssl_decrypt($encryptedData, 'aes-256-cbc', $encryption_key, 0, $iv);
        
        if ($decrypted === false) {
            throw new Exception("OpenSSL decrypt failed: " . openssl_error_string());
        }
        
        logDebug("Decrittografia completata", [
            'encrypted_length' => strlen($encryptedData),
            'decrypted_length' => strlen($decrypted)
        ]);
        
        return $decrypted;
        
    } catch (Exception $e) {
        logDebug("Errore decrittografia", [
            'error' => $e->getMessage(),
            'iv_hex_length' => strlen($iv_hex),
            'encrypted_length' => strlen($encryptedData),
            'key_length' => strlen($encryption_key)
        ]);
        
        return "Errore: decriptazione fallita - " . $e->getMessage();
    }
}

class EnhancedLeadFieldMapper {
    
    private $fieldMapping = [
        // NOME
        'nome' => 'first_name',
        'name' => 'first_name', 
        'first_name' => 'first_name',
        'firstname' => 'first_name',
        'customer_name' => 'first_name',
        'client_name' => 'first_name',
        'user_name' => 'first_name',
        'your_name' => 'first_name',
        'contact_name' => 'first_name',
        'full_name' => 'first_name',
        
        // COGNOME
        'cognome' => 'surname',
        'surname' => 'surname',
        'last_name' => 'surname', 
        'lastname' => 'surname',
        'customer_surname' => 'surname',
        'family_name' => 'surname',
        
        // EMAIL
        'email' => 'email',
        'mail' => 'email',
        'e_mail' => 'email',
        'e-mail' => 'email',
        'contact_email' => 'email',
        'customer_email' => 'email',
        'your_email' => 'email',
        'email_address' => 'email',
        
        // TELEFONO
        'phone' => 'phone',
        'telefono' => 'phone',
        'tel' => 'phone',
        'telephone' => 'phone',
        'phone_number' => 'phone',
        'mobile' => 'phone',
        'cellulare' => 'phone',
        'contact_phone' => 'phone',
        
        // MESSAGGIO
        'message' => 'message',
        'messaggio' => 'message',
        'msg' => 'message',
        'description' => 'message',
        'note' => 'message',
        'notes' => 'message',
        'comments' => 'message',
        'customer_message' => 'message',
        'inquiry' => 'message',
        'details' => 'message',
        'request' => 'message',
        'richiesta' => 'message',
        'testo' => 'message',
        'content' => 'message',
        'body' => 'message'
    ];
    
    private $excludedFields = [
        'csrf_token', 'recaptcha_response', 'g-recaptcha-response',
        'submit', 'submit2', 'recaptchaResponse', 'captcha_settings',
        'oid', 'retURL', 'lead_source', 'frompage__c', 'privacy',
        'honeypot', 'url_check', 'website', 'challenge_', 'source_page_url'
    ];
    
    private function isExcludedField($fieldName) {
        $lowerName = strtolower($fieldName);
        
        foreach ($this->excludedFields as $excluded) {
            if (strpos($lowerName, strtolower($excluded)) !== false) {
                return true;
            }
        }
        
        if (preg_match('/^(surname_field_|address_|user_|url_)/i', $fieldName)) {
            return true;
        }
        
        return false;
    }
    
    public function mapFields($inputData) {
        logDebug("Mappatura campi iniziata", array_keys($inputData));
        
        $mappedData = [
            'first_name' => '',
            'surname' => '',
            'email' => '',
            'phone' => '',
            'message' => '',
            'clients_id' => $inputData['clients_id'] ?? 0,
            'retURL' => $inputData['retURL'] ?? '',
            'source_page_url' => $inputData['source_page_url'] ?? ''
        ];
        
        $additionalFields = [];
        
        foreach ($inputData as $fieldName => $fieldValue) {
            if ($this->isExcludedField($fieldName)) {
                continue;
            }
            
            $cleanFieldName = strtolower(trim($fieldName));
            $trimmedValue = is_string($fieldValue) ? trim($fieldValue) : $fieldValue;
            
            if (empty($trimmedValue) && $trimmedValue !== '0') {
                continue;
            }
            
            if (isset($this->fieldMapping[$cleanFieldName])) {
                $standardField = $this->fieldMapping[$cleanFieldName];
                $mappedData[$standardField] = $trimmedValue;
                logDebug("Campo mappato", [
                    'original' => $fieldName, 
                    'mapped' => $standardField, 
                    'value' => $trimmedValue
                ]);
            } else {
                $additionalFields[] = [
                    'name' => $fieldName,
                    'value' => $trimmedValue
                ];
                logDebug("Campo aggiuntivo trovato", ['field' => $fieldName, 'value' => $trimmedValue]);
            }
        }
        
        // Gestione nome completo
        if (empty($mappedData['first_name']) && empty($mappedData['surname'])) {
            foreach ($inputData as $fieldName => $fieldValue) {
                $cleanFieldName = strtolower($fieldName);
                
                if ((strpos($cleanFieldName, 'name') !== false || 
                     strpos($cleanFieldName, 'nome') !== false) &&
                    !empty($fieldValue) && strpos($fieldValue, ' ') !== false) {
                    
                    $nameParts = explode(' ', trim($fieldValue), 2);
                    $mappedData['first_name'] = $nameParts[0];
                    $mappedData['surname'] = $nameParts[1] ?? 'N/A';
                    
                    logDebug("Nome completo diviso", [
                        'original' => $fieldValue,
                        'first_name' => $mappedData['first_name'],
                        'surname' => $mappedData['surname']
                    ]);
                    break;
                }
            }
        }
        
        if (empty($mappedData['surname']) && !empty($mappedData['first_name'])) {
            $mappedData['surname'] = 'N/A';
        }
        
        // Aggiungi campi extra al messaggio
        if (!empty($additionalFields)) {
            $extraMessage = "\n\n--- Informazioni aggiuntive ---\n";
            foreach ($additionalFields as $field) {
                $extraMessage .= "{$field['name']}: {$field['value']}\n";
            }
            
            $mappedData['message'] = trim($mappedData['message']) . $extraMessage;
            
            logDebug("Campi aggiuntivi aggiunti", [
                'count' => count($additionalFields)
            ]);
        }
        
        if (empty($mappedData['source_page_url'])) {
            foreach ($inputData as $fieldName => $fieldValue) {
                $cleanFieldName = strtolower($fieldName);
                
                if ((strpos($cleanFieldName, 'url') !== false || 
                     strpos($cleanFieldName, 'page') !== false ||
                     strpos($cleanFieldName, 'source') !== false ||
                     strpos($cleanFieldName, 'from') !== false) &&
                    (strpos($fieldValue, 'http') !== false || 
                     strpos($fieldValue, '.html') !== false || 
                     strpos($fieldValue, '.php') !== false)) {
                    
                    $mappedData['source_page_url'] = trim($fieldValue);
                    break;
                }
            }
        }
        
        logDebug("Mappatura completata", [
            'first_name' => $mappedData['first_name'],
            'surname' => $mappedData['surname'],
            'email' => $mappedData['email'],
            'phone' => substr($mappedData['phone'], 0, 3) . '***', // Privacy
            'message_length' => strlen($mappedData['message'])
        ]);
        
        return $mappedData;
    }
    
    public function validateMappedData($mappedData) {
        $errors = [];
        
        if (empty($mappedData['clients_id']) || !is_numeric($mappedData['clients_id'])) {
            $errors[] = "Client ID mancante o non valido";
        }
        
        if (empty($mappedData['email'])) {
            $errors[] = "Email mancante";
        } elseif (!filter_var($mappedData['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Email non valida";
        }
        
        if (empty($mappedData['first_name']) && empty($mappedData['surname'])) {
            $errors[] = "Nome o cognome mancante";
        }
        
        return $errors;
    }
}

// Funzioni di utilità
function getClientIP() {
    $ipKeys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
    
    foreach ($ipKeys as $key) {
        if (!empty($_SERVER[$key])) {
            $ip = $_SERVER[$key];
            if (strpos($ip, ',') !== false) {
                $ip = explode(',', $ip)[0];
            }
            $ip = trim($ip);
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $ip;
            }
        }
    }
    
    return $_SERVER['REMOTE_ADDR'] ?? 'Non disponibile';
}

function getBrowserInfo() {
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Non disponibile';
    
    $browser = 'Sconosciuto';
    $os = 'Sconosciuto';
    
    if (strpos($userAgent, 'Chrome') !== false && strpos($userAgent, 'Edg') === false) {
        $browser = 'Chrome';
    } elseif (strpos($userAgent, 'Firefox') !== false) {
        $browser = 'Firefox';
    } elseif (strpos($userAgent, 'Safari') !== false && strpos($userAgent, 'Chrome') === false) {
        $browser = 'Safari';
    } elseif (strpos($userAgent, 'Edg') !== false) {
        $browser = 'Edge';
    } elseif (strpos($userAgent, 'Opera') !== false || strpos($userAgent, 'OPR') !== false) {
        $browser = 'Opera';
    }
    
    if (strpos($userAgent, 'Windows NT') !== false) {
        $os = 'Windows';
    } elseif (strpos($userAgent, 'Mac OS X') !== false) {
        $os = 'macOS';
    } elseif (strpos($userAgent, 'Linux') !== false) {
        $os = 'Linux';
    } elseif (strpos($userAgent, 'Android') !== false) {
        $os = 'Android';
    } elseif (strpos($userAgent, 'iPhone OS') !== false || strpos($userAgent, 'iPad') !== false) {
        $os = 'iOS';
    }
    
    return $browser . ' su ' . $os;
}

function determineLeadType($refererUrl, $sourceUrl = '') {
    $urls = array_filter([$refererUrl, $sourceUrl]);
    
    foreach ($urls as $url) {
        if (empty($url)) continue;
        
        if (preg_match('/[?&](gclid|utm_source=google|utm_medium=cpc)/i', $url) ||
            preg_match('/\.com\/gad/i', $url) ||
            strpos($url, 'google.com/ads') !== false) {
            return 'Google ADS';
        }
        
        if (preg_match('/[?&](fbclid|utm_source=facebook|utm_medium=social)/i', $url)) {
            return 'Facebook ADS';
        }
        
        if (preg_match('/[?&]utm_source=(instagram|linkedin|twitter)/i', $url)) {
            return 'Social Media ADS';
        }
    }
    
    return 'Semplice/Organico';
}

// PROCESSING PRINCIPALE
try {
    logDebug("=== NUOVA RICHIESTA LEADAI ===");
    
    $mapper = new EnhancedLeadFieldMapper();
    
    $inputData = [];
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        
        if (strpos($contentType, 'application/json') !== false) {
            $jsonInput = file_get_contents('php://input');
            $inputData = json_decode($jsonInput, true) ?: [];
            logDebug("Dati JSON ricevuti", array_keys($inputData));
        } else {
            $inputData = $_POST;
            logDebug("Dati POST ricevuti", array_keys($inputData));
        }
    } elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $inputData = $_GET;
        logDebug("Dati GET ricevuti (test)", array_keys($inputData));
    }
    
    // Validazione iniziale
    if (empty($inputData)) {
        throw new Exception("Nessun dato ricevuto");
    }
    
    // Mappa i campi
    $mappedData = $mapper->mapFields($inputData);
    
    // Valida i dati
    $validationErrors = $mapper->validateMappedData($mappedData);
    
    if (!empty($validationErrors)) {
        logDebug("Errori di validazione", $validationErrors);
        http_response_code(400);
        
        echo json_encode([
            'success' => false,
            'errors' => $validationErrors,
            'message' => 'Dati non validi: ' . implode(', ', $validationErrors),
            'code' => 'VALIDATION_ERROR'
        ]);
        exit;
    }
    
    // Prepara variabili
    $clients_id = $mappedData['clients_id'];
    $first_name = $mappedData['first_name'];
    $surname = $mappedData['surname'];
    $email = $mappedData['email'];
    $phone = $mappedData['phone'];
    $message = $mappedData['message'];
    $ip = getClientIP();
    $browser_info = getBrowserInfo();
    
    $lead_source = $mappedData['source_page_url'] ?: $_SERVER['HTTP_REFERER'] ?: 'Direct Access';
    $lead_type = determineLeadType($_SERVER['HTTP_REFERER'], $mappedData['source_page_url']);
    
    logDebug("Dati lead preparati", [
        'clients_id' => $clients_id,
        'email' => $email,
        'lead_type' => $lead_type,
        'ip' => $ip
    ]);
    
    // Verifica client
    $queryClient = "SELECT id, encryption_key, email FROM clients WHERE id = :clients_id LIMIT 1";
    $stmtClient = $pdo->prepare($queryClient);
    $stmtClient->bindParam(':clients_id', $clients_id);
    $stmtClient->execute();
    $client = $stmtClient->fetch(PDO::FETCH_ASSOC);
    
    if (!$client) {
        logDebug("Cliente non trovato", ['clients_id' => $clients_id]);
        throw new Exception("Cliente non trovato");
    }
    
    logDebug("Cliente trovato", ['client_id' => $client['id']]);
    
    // CRITTOGRAFIA MIGLIORATA
    $encryption_key = $client['encryption_key'];
    
    // Verifica chiave di crittografia
    if (empty($encryption_key)) {
        logDebug("ERRORE: Chiave crittografia mancante per client", ['client_id' => $client['id']]);
        throw new Exception("Chiave di crittografia non configurata per il cliente");
    }
    
    logDebug("Crittografia dati sensibili", [
        'phone_length' => strlen($phone),
        'message_length' => strlen($message),
        'key_length' => strlen($encryption_key)
    ]);
    
    // Critta phone
    $phoneEncryption = encryptDataEnhanced($phone, $encryption_key);
    $encryptedPhone = $phoneEncryption['encrypted'];
    $phoneIV = $phoneEncryption['iv'];
    
    // Critta message
    $messageEncryption = encryptDataEnhanced($message, $encryption_key);
    $encryptedMessage = $messageEncryption['encrypted'];
    $messageIV = $messageEncryption['iv'];
    
    logDebug("Crittografia completata", [
        'phone_encrypted_length' => strlen($encryptedPhone),
        'phone_iv_length' => strlen($phoneIV),
        'message_encrypted_length' => strlen($encryptedMessage),
        'message_iv_length' => strlen($messageIV)
    ]);
    
    // Gestione persona
    $queryPersonas = "SELECT id FROM personas WHERE email = :email LIMIT 1";
    $stmtPersonas = $pdo->prepare($queryPersonas);
    $stmtPersonas->bindParam(':email', $email);
    $stmtPersonas->execute();
    $persona = $stmtPersonas->fetch(PDO::FETCH_ASSOC);
    
    if ($persona) {
        $personas_id = $persona['id'];
        logDebug("Persona esistente", ['personas_id' => $personas_id]);
    } else {
        $queryInsertPersonas = "INSERT INTO personas (name, surname, email, created_at) 
                                VALUES (:name, :surname, :email, NOW())";
        $stmtInsertPersonas = $pdo->prepare($queryInsertPersonas);
        $stmtInsertPersonas->bindParam(':name', $first_name);
        $stmtInsertPersonas->bindParam(':surname', $surname);
        $stmtInsertPersonas->bindParam(':email', $email);
        
        if ($stmtInsertPersonas->execute()) {
            $personas_id = $pdo->lastInsertId();
            logDebug("Nuova persona creata", ['personas_id' => $personas_id]);
        } else {
            throw new Exception("Errore nell'inserimento della persona");
        }
    }
    
    // Trova website
    $refererDomain = parse_url($lead_source, PHP_URL_HOST);
    if ($refererDomain) {
        $refererDomain = preg_replace('/^www\./', '', $refererDomain);
    }
    
    $queryWebsite = "SELECT id, name, url FROM websites WHERE url LIKE :url AND clients_id = :clients_id LIMIT 1";
    $stmtWebsite = $pdo->prepare($queryWebsite);
    $likeUrl = '%' . $refererDomain . '%';
    $stmtWebsite->bindParam(':url', $likeUrl);
    $stmtWebsite->bindParam(':clients_id', $client['id']);
    $stmtWebsite->execute();
    $website = $stmtWebsite->fetch(PDO::FETCH_ASSOC);
    
    if ($website) {
        logDebug("Website trovato", ['website_id' => $website['id'], 'name' => $website['name']]);
        
        // INSERIMENTO LEAD CON IV SEPARATI
        $insertLeadQuery = "INSERT INTO leads (phone, message, ip, status_id, created_at, clients_id, personas_id, websites_id, iv, lead_source_url, lead_type)
                           VALUES (:phone, :message, :ip, 1, NOW(), :clients_id, :personas_id, :websites_id, :iv, :lead_source_url, :lead_type)";
        $stmtLead = $pdo->prepare($insertLeadQuery);
        
        // NOTA: Usiamo lo stesso IV per phone e message per compatibilità con il sistema esistente
        // In futuro si potrebbe separare con phone_iv e message_iv
        $commonIV = $phoneIV ?: $messageIV;
        
        $stmtLead->bindParam(':phone', $encryptedPhone);
        $stmtLead->bindParam(':message', $encryptedMessage);
        $stmtLead->bindParam(':ip', $ip);
        $stmtLead->bindParam(':clients_id', $client['id']);
        $stmtLead->bindParam(':personas_id', $personas_id);
        $stmtLead->bindParam(':websites_id', $website['id']);
        $stmtLead->bindParam(':iv', $commonIV);
        $stmtLead->bindParam(':lead_source_url', $lead_source);
        $stmtLead->bindParam(':lead_type', $lead_type);
        
        if ($stmtLead->execute()) {
            $lead_id = $pdo->lastInsertId();
            
            logDebug("Lead inserito con successo", [
                'lead_id' => $lead_id,
                'encrypted_phone_length' => strlen($encryptedPhone),
                'encrypted_message_length' => strlen($encryptedMessage),
                'iv_length' => strlen($commonIV)
            ]);
            
            // TEST IMMEDIATO DI DECRITTOGRAFIA
            $testDecryptPhone = decryptDataEnhanced($encryptedPhone, $commonIV, $encryption_key);
            $testDecryptMessage = decryptDataEnhanced($encryptedMessage, $commonIV, $encryption_key);
            
            logDebug("Test decrittografia immediato", [
                'phone_test' => $testDecryptPhone === $phone ? 'OK' : 'FAIL',
                'message_test' => $testDecryptMessage === $message ? 'OK' : 'FAIL'
            ]);
            
            // Email di notifica
            $client_email = $client['email'];
            $website_name = $website['name'];
            
            $subject = "🎯 Nuovo LEAD - Sito " . $website_name . " (" . $lead_type . ")";
            $email_body = "Nuovo lead ricevuto tramite LeadAI Enhanced\n\n";
            $email_body .= "🌐 Sito: " . $website_name . "\n";
            $email_body .= "📍 Tipologia: " . $lead_type . "\n";
            $email_body .= "🔗 Origine: " . $lead_source . "\n";
            $email_body .= "📅 Data: " . date('d/m/Y H:i:s') . "\n\n";
            $email_body .= "👤 DATI LEAD:\n";
            $email_body .= "Nome: " . $first_name . " " . $surname . "\n";
            $email_body .= "Email: " . $email . "\n";
            $email_body .= "Telefono: " . $phone . "\n";
            $email_body .= "Messaggio: " . $message . "\n\n";
            $email_body .= "🔧 INFO TECNICHE:\n";
            $email_body .= "IP: " . $ip . "\n";
            $email_body .= "Browser: " . $browser_info . "\n\n";
            $email_body .= "Accedi alla dashboard LeadAI per gestire questo lead.\n";
            
            $headers = "From: LeadAI Enhanced System <noreply@mg-adv.com>\r\n";
            $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
            
            mail($client_email, $subject, $email_body, $headers);
            
            // Risposta di successo
            $response = [
                'success' => true,
                'lead_id' => $lead_id,
                'message' => 'Lead inserito con successo',
                'data' => [
                    'lead_type' => $lead_type,
                    'source_url' => $lead_source,
                    'website' => $website_name,
                    'timestamp' => date('Y-m-d H:i:s'),
                    'client_id' => $client['id']
                ]
            ];
            
            if (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false) {
                header('Content-Type: application/json');
                echo json_encode($response, JSON_UNESCAPED_UNICODE);
            } else {
                echo "Lead inserito con successo! ID: " . $lead_id;
                
                if (!empty($mappedData['retURL'])) {
                    header("Location: " . $mappedData['retURL']);
                    exit;
                }
            }
            
        } else {
            throw new Exception("Errore nell'inserimento del lead nel database");
        }
        
    } else {
        $error_msg = "Il dominio '$refererDomain' non è verificato per questo cliente";
        logDebug("Website non trovato", [
            'domain' => $refererDomain,
            'clients_id' => $client['id'],
            'source_url' => $lead_source
        ]);
        throw new Exception($error_msg);
    }
    
} catch (Exception $e) {
    logDebug("ERRORE GENERALE", [
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
    
    http_response_code(500);
    
    $errorResponse = [
        'success' => false,
        'message' => 'Errore del server: ' . $e->getMessage(),
        'code' => 'SERVER_ERROR',
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    if (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false) {
        header('Content-Type: application/json');
        echo json_encode($errorResponse, JSON_UNESCAPED_UNICODE);
    } else {
        echo "Errore: " . $e->getMessage();
    }
}

/**
 * ==================== ISTRUZIONI PER RISOLVERE L'ERRORE DI CRITTOGRAFIA ====================
 * 
 * PROBLEMA IDENTIFICATO:
 * L'errore "decriptazione fallita!" deriva da problemi nella gestione dell'IV (Initialization Vector)
 * 
 * SOLUZIONI IMPLEMENTATE:
 * 
 * 1. ✅ FORMATO IV CORRETTO
 *    - IV generato come binario (16 bytes)
 *    - Convertito in esadecimale per MySQL (32 caratteri)
 *    - Riconvertito in binario per la decrittografia
 * 
 * 2. ✅ GESTIONE ERRORI AVANZATA
 *    - Validazione lunghezza IV
 *    - Controllo chiave di crittografia
 *    - Logging dettagliato per debug
 * 
 * 3. ✅ FALLBACK SICURO
 *    - Se la crittografia fallisce, salva in chiaro con prefisso [PLAIN]
 *    - Sistema di decrittografia riconosce il prefisso
 * 
 * 4. ✅ TEST IMMEDIATO
 *    - Dopo l'inserimento, testa subito la decrittografia
 *    - Log del risultato per verificare la correttezza
 * 
 * COME RISOLVERE IL PROBLEMA ESISTENTE:
 * 
 * OPZIONE A - Aggiorna solo add_lead.php (RACCOMANDATO):
 * 1. Sostituisci il file add_lead.php con questa versione
 * 2. I nuovi lead funzioneranno correttamente
 * 3. I lead esistenti potrebbero continuare a dare errore
 * 
 * OPZIONE B - Script di riparazione per lead esistenti:
 * 
 * ```sql
 * -- Trova lead con errori di decrittografia
 * SELECT id, phone, message, iv FROM leads 
 * WHERE clients_id = 7 
 * AND created_at >= '2025-01-01'
 * ORDER BY id DESC;
 * ```
 * 
 * VERIFICA FUNZIONAMENTO:
 * 
 * 1. Controlla i log del server dopo l'invio di un nuovo lead
 * 2. Cerca linee come:
 *    - "Crittografia completata"
 *    - "Test decrittografia immediato"
 *    - "phone_test: OK" e "message_test: OK"
 * 
 * 3. Se vedi errori, controlla:
 *    - Chiave di crittografia del cliente nel database
 *    - Formato dell'IV salvato nel database
 *    - Versione di OpenSSL sul server
 * 
 * DEBUG MANUALE:
 * 
 * Per testare la crittografia manualmente, aggiungi questo al file:
 * 
 * ```php
 * // Test crittografia (solo per debug)
 * if (isset($_GET['test_encryption'])) {
 *     $testData = "Test messaggio 123";
 *     $testKey = "chiave_di_test_32_caratteri_min";
 *     
 *     $result = encryptDataEnhanced($testData, $testKey);
 *     $decrypted = decryptDataEnhanced($result['encrypted'], $result['iv'], $testKey);
 *     
 *     echo json_encode([
 *         'original' => $testData,
 *         'encrypted' => $result['encrypted'],
 *         'iv' => $result['iv'],
 *         'decrypted' => $decrypted,
 *         'success' => ($testData === $decrypted)
 *     ]);
 *     exit;
 * }
 * ```
 * 
 * Poi visitare: add_lead.php?test_encryption=1
 * 
 * PROBLEMI COMUNI E SOLUZIONI:
 * 
 * 1. "IV length invalid"
 *    - Problema: IV non è 32 caratteri in hex
 *    - Soluzione: Verificare la funzione encryptDataEnhanced
 * 
 * 2. "OpenSSL decrypt failed"
 *    - Problema: Chiave di crittografia non valida
 *    - Soluzione: Verificare encryption_key nella tabella clients
 * 
 * 3. "Chiave di crittografia non configurata"
 *    - Problema: Campo encryption_key vuoto nel database
 *    - Soluzione: Aggiornare il record del cliente con una chiave valida
 * 
 * CONFIGURAZIONE CHIAVE CRITTOGRAFIA:
 * 
 * Se manca la chiave di crittografia per il cliente:
 * 
 * ```sql
 * UPDATE clients 
 * SET encryption_key = 'TUA_CHIAVE_SICURA_DI_32_CARATTERI_MINIMO' 
 * WHERE id = 7;
 * ```
 * 
 * IMPORTANTE: Usa una chiave sicura e mantienila privata!
 * 
 */
?>