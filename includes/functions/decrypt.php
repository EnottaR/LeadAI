<?php
/**
 * Sistema di decriptazione Base64 per LeadAI
 * Versione 2.0 - Risolve definitivamente i problemi di corruzione
 */

function encryptLeadData($data, $encryption_key) {
    if (empty($data)) {
        return '';
    }
    
    // Genera IV random di 16 byte
    $iv = random_bytes(16);
    
    // Cripta i dati
    $encrypted = openssl_encrypt($data, 'aes-256-cbc', $encryption_key, OPENSSL_RAW_DATA, $iv);
    
    if ($encrypted === false) {
        throw new Exception("Errore durante la crittografia dei dati");
    }
    
    // Combina IV + dati crittografati e converte in Base64
    $combined = $iv . $encrypted;
    $base64_result = base64_encode($combined);
    
    return $base64_result;
}

function decryptLeadData($encrypted_data, $encryption_key) {
    if (empty($encrypted_data)) {
        return '[Dato vuoto]';
    }
    
    // Prova prima il nuovo formato (Base64)
    $raw_data = base64_decode($encrypted_data, true);
    
    if ($raw_data !== false && strlen($raw_data) >= 16) {
        // Estrae IV (primi 16 byte) e dati crittografati (resto)
        $iv = substr($raw_data, 0, 16);
        $encrypted = substr($raw_data, 16);
        
        // Decripta
        $decrypted = openssl_decrypt($encrypted, 'aes-256-cbc', $encryption_key, OPENSSL_RAW_DATA, $iv);
        
        if ($decrypted !== false) {
            return $decrypted;
        }
    }
    
    // Se fallisce, potrebbe essere un dato del vecchio formato
    // Restituisce messaggio che indica che serve migrazione
    return '[Dato da migrare - formato vecchio]';
}

// Funzione di compatibilità per il vecchio sistema
function decryptData($encryptedData, $iv, $encryption_key) {
    // Se abbiamo un IV separato, è il vecchio formato
    if (!empty($iv)) {
        // Converte IV da hex se necessario
        if (is_string($iv) && ctype_xdigit($iv) && strlen($iv) == 32) {
            $iv_binary = hex2bin($iv);
        } else {
            $iv_binary = $iv;
        }
        
        // Prova il vecchio metodo di decriptazione
        if (strlen($iv_binary) == 16) {
            $old_decrypted = openssl_decrypt($encryptedData, 'aes-256-cbc', $encryption_key, 0, $iv_binary);
            if ($old_decrypted !== false) {
                return $old_decrypted;
            }
        }
    }
    
    // Altrimenti usa il nuovo sistema
    return decryptLeadData($encryptedData, $encryption_key);
}
?>