<?php
/**
 * LeadAI - Enhanced Lead Receiver
*/

date_default_timezone_set('Europe/Rome');

$pdo = require_once 'db.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

class LeadFieldMapper {
    
    // Mappatura dei campi, questo per non modificare eventualmente i campi form esistenti
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
        'content' => 'message',
        
        // URL
        'source_page_url' => 'source_page_url',
        'current_page' => 'source_page_url',
        'page_url' => 'source_page_url',
        'frompage_url' => 'source_page_url',
        'frompage__url' => 'source_page_url',
        'origin_url' => 'source_page_url',
        'referrer_url' => 'source_page_url'
    ];
    
    public function mapFields($inputData) {
        $mappedData = [
            'first_name' => '',
            'surname' => '',
            'email' => '',
            'phone' => '',
            'message' => '',
            'clients_id' => $inputData['clients_id'] ?? 0,
            'retURL' => $inputData['retURL'] ?? '',
            'source_page_url' => ''
        ];
        
        foreach ($inputData as $fieldName => $fieldValue) {
            $cleanFieldName = strtolower(trim($fieldName));
            
            if (isset($this->fieldMapping[$cleanFieldName])) {
                $standardField = $this->fieldMapping[$cleanFieldName];
                $mappedData[$standardField] = trim($fieldValue);
            }
        }
        
        if (empty($mappedData['source_page_url'])) {
            foreach ($inputData as $fieldName => $fieldValue) {
                $cleanFieldName = strtolower($fieldName);
                
                if ((strpos($cleanFieldName, 'url') !== false || 
                     strpos($cleanFieldName, 'page') !== false ||
                     strpos($cleanFieldName, 'source') !== false ||
                     strpos($cleanFieldName, 'from') !== false) &&
                    (strpos($fieldValue, 'http') !== false || strpos($fieldValue, '.html') !== false || strpos($fieldValue, '.php') !== false)) {
                    
                    $mappedData['source_page_url'] = trim($fieldValue);
                    break;
                }
            }
        }
        
        if (empty($mappedData['first_name']) && empty($mappedData['surname'])) {
            foreach ($inputData as $fieldName => $fieldValue) {
                $cleanFieldName = strtolower($fieldName);
                
                if ((strpos($cleanFieldName, 'name') !== false || 
                     strpos($cleanFieldName, 'nome') !== false) &&
                    strpos($fieldValue, ' ') !== false) {
                    
                    $nameParts = explode(' ', trim($fieldValue), 2);
                    $mappedData['first_name'] = $nameParts[0];
                    $mappedData['surname'] = $nameParts[1] ?? 'N/A';
                    break;
                }
            }
        }
        
        // Fallback: cognome vuoto = N/A
        if (empty($mappedData['surname'])) {
            $mappedData['surname'] = 'N/A';
        }
        
        return $mappedData;
    }
    
    public function validateMappedData($mappedData) {
        $errors = [];
        
        if (empty($mappedData['clients_id']) || !is_numeric($mappedData['clients_id'])) {
            $errors[] = "Client ID mancante o non valido";
        }
        
        if (empty($mappedData['email']) || !filter_var($mappedData['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Email mancante o non valida";
        }
        
        if (empty($mappedData['first_name'])) {
            $errors[] = "Nome mancante";
        }
        
        return $errors;
    }
}

function getClientIP() {
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        if (strpos($ip, ',') !== false) {
            $ip = explode(',', $ip)[0];
        }
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    return trim($ip);
}

function getBrowserInfo() {
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Non disponibile';
    
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

function getLeadType($refererUrl) {
    if (empty($refererUrl)) {
        return 'Semplice/Organico';
    }
    
    // Verifico se contiene parametri da Google ADS
    if (preg_match('/[?&](gclid|utm_source=google|utm_medium=cpc)/i', $refererUrl) ||
        preg_match('/\.com\/gad/i', $refererUrl)) {
        return 'Google ADS';
    }
    
    return 'Semplice/Organico';
}

// Processing primario
try {
    $mapper = new LeadFieldMapper();
    
    $inputData = [];
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        
        if (strpos($contentType, 'application/json') !== false) {
            $jsonInput = file_get_contents('php://input');
            $inputData = json_decode($jsonInput, true) ?: [];
        } else {
            $inputData = $_POST;
        }
    } elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $inputData = $_GET;
    }
    
    $mappedData = $mapper->mapFields($inputData);
    
    $validationErrors = $mapper->validateMappedData($mappedData);
    
    if (!empty($validationErrors)) {
        http_response_code(400);
        
        if (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'errors' => $validationErrors,
                'message' => 'Dati non validi: ' . implode(', ', $validationErrors)
            ]);
        } else {
            echo "Errore: " . implode(', ', $validationErrors);
        }
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
    
    $lead_source = $mappedData['source_page_url'] ?? $_SERVER['HTTP_REFERER'] ?? 'Direct Access';
    $lead_type = getLeadType($lead_source);
    $full_lead_source_url = $lead_source;
    
    $queryClient = "SELECT id, encryption_key, email FROM clients WHERE id = :clients_id LIMIT 1";
    $stmtClient = $pdo->prepare($queryClient);
    $stmtClient->bindParam(':clients_id', $clients_id);
    $stmtClient->execute();
    $client = $stmtClient->fetch(PDO::FETCH_ASSOC);
    
    if (!$client) {
        http_response_code(404);
        
        if (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Cliente non trovato'
            ]);
        } else {
            echo "Errore: Cliente non trovato";
        }
        exit;
    }
    
    $encryption_key = $client['encryption_key'];
    $iv = random_bytes(16);
    
    $encryptedPhone = openssl_encrypt($phone, 'aes-256-cbc', $encryption_key, 0, $iv);
    $encryptedMessage = openssl_encrypt($message, 'aes-256-cbc', $encryption_key, 0, $iv);
    
    $queryPersonas = "SELECT id FROM personas WHERE email = :email LIMIT 1";
    $stmtPersonas = $pdo->prepare($queryPersonas);
    $stmtPersonas->bindParam(':email', $email);
    $stmtPersonas->execute();
    $persona = $stmtPersonas->fetch(PDO::FETCH_ASSOC);
    
    if ($persona) {
        $personas_id = $persona['id'];
    } else {
        $queryInsertPersonas = "INSERT INTO personas (name, surname, email, created_at) 
                                VALUES (:name, :surname, :email, NOW())";
        $stmtInsertPersonas = $pdo->prepare($queryInsertPersonas);
        $stmtInsertPersonas->bindParam(':name', $first_name);
        $stmtInsertPersonas->bindParam(':surname', $surname);
        $stmtInsertPersonas->bindParam(':email', $email);
        
        if ($stmtInsertPersonas->execute()) {
            $personas_id = $pdo->lastInsertId();
        } else {
            throw new Exception("Errore nell'inserimento della persona");
        }
    }
    
    // Estrazione dominio dall'url
    $refererDomain = parse_url($full_lead_source_url, PHP_URL_HOST);
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
        $stmtLead->bindParam(':lead_source_url', $full_lead_source_url);
        $stmtLead->bindParam(':lead_type', $lead_type);
        
        if ($stmtLead->execute()) {
            $lead_id = $pdo->lastInsertId();
            
            // Email notifica lead
            $client_email = $client['email'];
            $website_name = $website['name'];
            
            $subject = "Nuovo LEAD - Sito " . $website_name . " (" . $lead_type . ")";
            $email_body = "Nuovo lead ricevuto da " . $full_lead_source_url . "\n\n";
            $email_body .= "Tipo di lead: " . $lead_type . "\n";
            $email_body .= "Origine: " . $full_lead_source_url . "\n\n";
            $email_body .= "Di seguito i dati inseriti:\n";
            $email_body .= "Nome: " . $first_name . " " . $surname . "\n";
            $email_body .= "Email: " . $email . "\n";
            $email_body .= "Telefono: " . $phone . "\n";
            $email_body .= "Messaggio: " . $message . "\n\n";
            $email_body .= "IP: " . $ip . "\n";
            $email_body .= "Browser: " . $browser_info . "\n";
            
            $headers = "From: LeadAI System <noreply@mg-adv.com>\r\n";
            $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
            
            mail($client_email, $subject, $email_body, $headers);
            
            if (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'lead_id' => $lead_id,
                    'message' => 'Lead inserito con successo',
                    'lead_type' => $lead_type,
                    'source_url' => $full_lead_source_url
                ]);
            } else {
                echo "Lead inserito con successo! ID: " . $lead_id;
                
                if (!empty($mappedData['retURL'])) {
                    header("Location: " . $mappedData['retURL']);
                    exit;
                }
            }
            
        } else {
            throw new Exception("Errore nell'inserimento del lead");
        }
        
    } else {
        throw new Exception("Il sito non appartiene al cliente o non è verificato");
    }
    
} catch (Exception $e) {
    http_response_code(500);
    
    if (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Errore del server: ' . $e->getMessage()
        ]);
    } else {
        echo "Errore: " . $e->getMessage();
    }
}
?>