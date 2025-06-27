<?php
/**
 * LeadAI Lead Receiver - v 2.3.0
 * Gestione avanzata delle integrazioni, crittografia Base64
 * Sistema di ricezione lead con crittografia sicura
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

function logDebug($message, $data = null) {
    $logEntry = date('Y-m-d H:i:s') . ' - ' . $message;
    if ($data !== null) {
        $logEntry .= ' - Data: ' . json_encode($data, JSON_UNESCAPED_UNICODE);
    }
    error_log($logEntry);
}

// Include le funzioni di crittografia
require_once 'includes/functions/decrypt.php';

// Mappatura campi form
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
            'phone' => substr($mappedData['phone'], 0, 3) . '***',
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
    
    if (empty($inputData)) {
        throw new Exception("Nessun dato ricevuto");
    }
    
    $mappedData = $mapper->mapFields($inputData);
    
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
    
    // ===========================
    // SISTEMA DI CRITTOGRAFIA BASE64
    // ===========================
    
    $encryption_key = $client['encryption_key'];
    
    if (empty($encryption_key)) {
        logDebug("ERRORE: Chiave crittografia mancante per client", ['client_id' => $client['id']]);
        throw new Exception("Chiave di crittografia non configurata per il cliente");
    }
    
    try {
        $encryptedPhone = encryptLeadData($phone, $encryption_key);
        $encryptedMessage = encryptLeadData($message, $encryption_key);
        
        logDebug("Crittografia Base64 completata", [
            'phone_encrypted_length' => strlen($encryptedPhone),
            'message_encrypted_length' => strlen($encryptedMessage),
            'encryption_method' => 'AES-256-CBC + Base64'
        ]);
        
        // Test immediato della decriptazione per verifica
        $testPhone = decryptLeadData($encryptedPhone, $encryption_key);
        $testMessage = decryptLeadData($encryptedMessage, $encryption_key);
        
        if ($testPhone !== $phone || $testMessage !== $message) {
            throw new Exception("Verifica crittografia fallita - dati corrotti");
        }
        
        logDebug("Verifica crittografia SUPERATA", ['verification' => 'PASSED']);
        
    } catch (Exception $e) {
        logDebug("ERRORE durante la crittografia", ['error' => $e->getMessage()]);
        throw new Exception("Errore durante la crittografia dei dati: " . $e->getMessage());
    }
    
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
        
        // INSERIMENTO LEAD CON CRITTOGRAFIA BASE64
        $insertLeadQuery = "INSERT INTO leads (phone, message, ip, status_id, created_at, clients_id, personas_id, websites_id, lead_source_url, lead_type)
                           VALUES (:phone, :message, :ip, 1, NOW(), :clients_id, :personas_id, :websites_id, :lead_source_url, :lead_type)";
        $stmtLead = $pdo->prepare($insertLeadQuery);
        
        $stmtLead->bindParam(':phone', $encryptedPhone);
        $stmtLead->bindParam(':message', $encryptedMessage);
        $stmtLead->bindParam(':ip', $ip);
        $stmtLead->bindParam(':clients_id', $client['id']);
        $stmtLead->bindParam(':personas_id', $personas_id);
        $stmtLead->bindParam(':websites_id', $website['id']);
        $stmtLead->bindParam(':lead_source_url', $lead_source);
        $stmtLead->bindParam(':lead_type', $lead_type);
        
        if ($stmtLead->execute()) {
            $lead_id = $pdo->lastInsertId();
            
            logDebug("Lead inserito con successo", [
                'lead_id' => $lead_id,
                'encryption_system' => 'Base64 + AES-256-CBC'
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
            $email_body .= "Browser: " . $browser_info . "\n";
            $email_body .= "✅ Crittografia: Base64 + AES-256-CBC\n\n";
            $email_body .= "Accedi alla dashboard LeadAI per gestire questo lead.\n";
            
            $headers = "From: LeadAI Enhanced System <noreply@mg-adv.com>\r\n";
            $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
            
            mail($client_email, $subject, $email_body, $headers);
            
            // RISPOSTA SUCCESS
            $response = [
                'success' => true,
                'lead_id' => $lead_id,
                'message' => 'Lead inserito con successo',
                'data' => [
                    'lead_type' => $lead_type,
                    'source_url' => $lead_source,
                    'website' => $website_name,
                    'timestamp' => date('Y-m-d H:i:s'),
                    'client_id' => $client['id'],
                    'encryption_system' => 'Base64 + AES-256-CBC'
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
?>