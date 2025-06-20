<?php
require_once 'db.php';

// Token di sessione univoco
function generateSessionToken() {
    return bin2hex(random_bytes(32));
}

// Da questa funzione controllo se è già presente una connessione attiva
/*
function hasActiveSession($conn, $user_id) {
    $stmt = $conn->prepare("SELECT session_token, session_expires FROM clients WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($existing_token, $expires);
    $stmt->fetch();
    $stmt->close();
    
    // Nessun token attivo o token scaduto = nessuna connessione attiva
    if (!$existing_token || !$expires) {
        return false;
    }
    
    if (strtotime($expires) <= time()) {
        clearUserSession($conn, $user_id);
        return false;
    }
    
    return true;
}
*/

// Pulizia della sessione
function clearUserSession($conn, $user_id) {
    $stmt = $conn->prepare("UPDATE clients SET session_token = NULL, session_expires = NULL WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();
}

// Salva il token di sessione nel db
function saveSessionToken($conn, $user_id, $token) {
    // Durata sessione: 4 ore
    $expires = date('Y-m-d H:i:s', time() + (4 * 60 * 60));
    
    // MODIFICATO: Non cancelliamo più le sessioni esistenti, permettiamo sessioni multiple
    $stmt = $conn->prepare("UPDATE clients SET session_token = ?, session_expires = ? WHERE id = ?");
    $stmt->bind_param("ssi", $token, $expires, $user_id);
    $result = $stmt->execute();
    $stmt->close();
    
    return $result;
}

function register_user($conn, $nome, $cognome, $email, $password)
{
    try {
        $nome = ucfirst(strtolower(trim($nome)));
        $cognome = ucfirst(strtolower(trim($cognome)));

        if (email_exists($conn, $email)) {
            return [
                "type" => "error",
                "message" => "⚠️ Attenzione, l'indirizzo email inserito è già presente nei nostri sistemi."
            ];
        }

        $username = generate_unique_username($conn, $nome, $cognome);

        // Hash
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);

        $encryption_key = bin2hex(random_bytes(32));

        $stmt = $conn->prepare("INSERT INTO clients (name, surname, username, password, email, type, encryption_key) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $type = 1;
        $stmt->bind_param("sssssis", $nome, $cognome, $username, $hashed_password, $email, $type, $encryption_key);
        $stmt->execute();

        return [
            "type" => "success",
            "message" => "✅ Registrazione completata con successo! Ora puoi accedere."
        ];
    } catch (Exception $e) {
        return [
            "type" => "error",
            "message" => "❌ Errore nella registrazione. Riprova."
        ];
    }
}

function login_user($conn, $login, $password)
{
    try {
        $stmt = $conn->prepare("SELECT * FROM clients WHERE email = ? OR username = ?");
        $stmt->bind_param("ss", $login, $login);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            if (password_verify($password, $user['password'])) {
                
                // COMMENTATO: *** CONTROLLO SESSIONE ATTIVA ***
                // l'utente ha una sessione già attiva?
                /*
                if (hasActiveSession($conn, $user['id'])) {
                    return [
                        'status' => 'session_exists',
                        'message' => 'Questo account è già connesso da un altro dispositivo o browser. Disconnetti la sessione esistente per accedere.'
                    ];
                }
                */
                
                // Genera un nuovo token di sessione
                $session_token = generateSessionToken();
                
                // Salva il token nel db
                if (!saveSessionToken($conn, $user['id'], $session_token)) {
                    return false;
                }
                
                session_regenerate_id(true);
                
                $_SESSION['loggedin'] = true;
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['surname'] = $user['surname'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['session_token'] = $session_token;

                return true;
            }
        }
    } catch (Exception $e) {
        return false;
    }

    return false;
}

function validateCurrentSession($conn, $user_id, $session_token) {
    $stmt = $conn->prepare("SELECT session_token, session_expires FROM clients WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($db_token, $expires);
    $stmt->fetch();
    $stmt->close();
    
    // MODIFICATO: Controllo più permissivo per sessioni multiple
    // Controlla solo se la sessione non è scaduta, non il token specifico
    if (!$expires || strtotime($expires) <= time()) {
        clearUserSession($conn, $user_id);
        return false;
    }
    
    return true;
}

// Funzione per forzare il logout
function forceLogout($conn, $user_id) {
    clearUserSession($conn, $user_id);
    return true;
}

function email_exists($conn, $email)
{
    $stmt = $conn->prepare("SELECT id FROM clients WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    return $stmt->get_result()->num_rows > 0;
}

function generate_unique_username($conn, $nome, $cognome)
{
    $base_username = strtolower(substr($nome, 0, 1) . $cognome);
    $username = $base_username;
    $suffix = 1;

    while (true) {
        $stmt = $conn->prepare("SELECT id FROM clients WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        if ($stmt->get_result()->num_rows === 0) {
            return $username;
        }
        $username = $base_username . $suffix++;
    }
}
?>