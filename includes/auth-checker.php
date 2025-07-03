<?php
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

// Se l'utente non è loggato, forza il redirect alla login page
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit;
}

// *** CONTROLLO SE LA SESSIONE SIA VALIDA ***
// Verico: la sessione è valida e presente nel db?
if (isset($_SESSION['user_id']) && isset($_SESSION['session_token'])) {
    
    if (!validateCurrentSession($conn, $_SESSION['user_id'], $_SESSION['session_token'])) {
        // La sessione non è più valida, non c'è match sul token oppure è scaduto
        // Pulisco la sessione e torno alla login page
        
        $_SESSION = array();
        
        // Pulisco il cookie di sessione
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        session_destroy();
        
        header("Location: login.php?session_expired=1");
        exit;
    }
    
} else {
    // La sessione ha una malformazione (cookie disabilitato ad esempio), forzo logout
    header("Location: logout.php");
    exit;
}

$conn->close();
?>