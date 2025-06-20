<?php
/**
 * Funzione di decrittografia con fix per lunghezza chiave
 * Versione: 3.3 - Fix definitivo per key length
 */

function decryptData($encryptedData, $iv, $encryption_key) {
    // Log per debug (rimuovi in produzione)
    if (defined('LEADAI_DEBUG') && LEADAI_DEBUG) {
        error_log("DecryptData - Key length: " . strlen($encryption_key) . ", IV: " . $iv);
    }
    
    // Controllo dati di input
    if (empty($encryptedData)) {
        return "";
    }
    
    if (empty($iv) || empty($encryption_key)) {
        return "Errore: parametri di decrittografia mancanti";
    }
    
    // Gestione dei messaggi salvati in chiaro (fallback del sistema enhanced)
    if (strpos($encryptedData, '[PLAIN]') === 0) {
        return substr($encryptedData, 7);
    }
    
    try {
        // FIX CHIAVE: AES-256-CBC richiede esattamente 32 byte (256 bit)
        $fixed_key = $encryption_key;
        
        // Se la chiave è in formato HEX, convertila in binario
        if (ctype_xdigit($encryption_key) && strlen($encryption_key) === 64) {
            $fixed_key = hex2bin($encryption_key);
            if ($fixed_key === false) {
                throw new Exception("Conversione chiave hex2bin fallita");
            }
        }
        
        // Se la chiave è troppo corta, espandila
        if (strlen($fixed_key) < 32) {
            $fixed_key = str_pad($fixed_key, 32, '0', STR_PAD_RIGHT);
        }
        // Se la chiave è troppo lunga, tagliala
        elseif (strlen($fixed_key) > 32) {
            $fixed_key = substr($fixed_key, 0, 32);
        }
        
        if (defined('LEADAI_DEBUG') && LEADAI_DEBUG) {
            error_log("Chiave fissata - Lunghezza originale: " . strlen($encryption_key) . 
                     ", Lunghezza finale: " . strlen($fixed_key));
        }
        
        // FIX IV: deve essere esattamente 16 byte
        $fixed_iv = $iv;
        
        if (is_string($iv)) {
            // Se l'IV è in formato esadecimale (32 caratteri), convertilo
            if (ctype_xdigit($iv) && strlen($iv) === 32) {
                $fixed_iv = hex2bin($iv);
                if ($fixed_iv === false) {
                    throw new Exception("Conversione IV hex2bin fallita");
                }
            }
            // Se l'IV non è della lunghezza giusta, sistemalo
            elseif (strlen($iv) !== 16) {
                if (strlen($iv) < 16) {
                    $fixed_iv = str_pad($iv, 16, '0', STR_PAD_RIGHT);
                } else {
                    $fixed_iv = substr($iv, 0, 16);
                }
            }
        }
        
        if (strlen($fixed_iv) !== 16) {
            throw new Exception("IV length ancora sbagliata dopo fix: " . strlen($fixed_iv) . " bytes");
        }
        
        if (defined('LEADAI_DEBUG') && LEADAI_DEBUG) {
            error_log("IV fissato - Lunghezza: " . strlen($fixed_iv));
        }
        
        // Tentativo di decrittografia con parametri corretti
        $decrypted = openssl_decrypt($encryptedData, 'aes-256-cbc', $fixed_key, 0, $fixed_iv);
        
        if ($decrypted === false) {
            $openssl_error = openssl_error_string();
            
            // Tentativo con algoritmo diverso se AES-256-CBC fallisce
            $decrypted_alt = openssl_decrypt($encryptedData, 'aes-128-cbc', substr($fixed_key, 0, 16), 0, $fixed_iv);
            if ($decrypted_alt !== false) {
                if (defined('LEADAI_DEBUG') && LEADAI_DEBUG) {
                    error_log("Decrittografia riuscita con AES-128-CBC");
                }
                return $decrypted_alt;
            }
            
            throw new Exception("OpenSSL decrypt failed: " . ($openssl_error ?: "Errore sconosciuto"));
        }
        
        // Verifica che il contenuto sia UTF-8 valido
        if (!mb_check_encoding($decrypted, 'UTF-8')) {
            // Prova a sistemare la codifica
            $decrypted = mb_convert_encoding($decrypted, 'UTF-8', 'auto');
        }
        
        if (defined('LEADAI_DEBUG') && LEADAI_DEBUG) {
            error_log("Decrittografia completata: " . substr($decrypted, 0, 50) . "...");
        }
        
        return $decrypted;
        
    } catch (Exception $e) {
        if (defined('LEADAI_DEBUG') && LEADAI_DEBUG) {
            error_log("Errore decrittografia: " . $e->getMessage());
        }
        
        // Fallback completo: prova a restituire almeno qualcosa
        if (strlen($encryptedData) < 100 && ctype_print($encryptedData)) {
            return "[DATI_NON_CRITTOGRAFATI] " . $encryptedData;
        }
        
        return "Errore: " . $e->getMessage();
    }
}

/**
 * Funzione per controllare e sistemare la chiave di un cliente
 */
function fixClientEncryptionKey($conn, $client_id) {
    $stmt = $conn->prepare("SELECT encryption_key FROM clients WHERE id = ?");
    $stmt->bind_param("i", $client_id);
    $stmt->execute();
    $stmt->bind_result($current_key);
    $stmt->fetch();
    $stmt->close();
    
    echo "<h3>Controllo Chiave Client ID: {$client_id}</h3>";
    echo "<p><strong>Chiave attuale:</strong> " . ($current_key ? 'Presente (' . strlen($current_key) . ' caratteri)' : 'MANCANTE') . "</p>";
    
    if (empty($current_key)) {
        // Genera nuova chiave
        $new_key = bin2hex(random_bytes(32)); // 64 caratteri hex = 32 byte
        
        $stmt = $conn->prepare("UPDATE clients SET encryption_key = ? WHERE id = ?");
        $stmt->bind_param("si", $new_key, $client_id);
        
        if ($stmt->execute()) {
            echo "<p style='color: green;'>✅ Nuova chiave generata e salvata!</p>";
            echo "<p><strong>Nuova chiave:</strong> " . htmlspecialchars($new_key) . "</p>";
        } else {
            echo "<p style='color: red;'>❌ Errore nel salvare la nuova chiave!</p>";
        }
        $stmt->close();
        
    } elseif (strlen($current_key) !== 64 || !ctype_xdigit($current_key)) {
        echo "<p style='color: orange;'>⚠️ Chiave esistente non è nel formato corretto (dovrebbe essere 64 caratteri esadecimali)</p>";
        
        // Prova a sistemare la chiave esistente
        if (strlen($current_key) < 64) {
            $fixed_key = str_pad($current_key, 64, '0');
        } else {
            $fixed_key = substr($current_key, 0, 64);
        }
        
        // Assicurati che sia esadecimale
        if (!ctype_xdigit($fixed_key)) {
            $fixed_key = bin2hex(hash('sha256', $current_key, true));
        }
        
        $stmt = $conn->prepare("UPDATE clients SET encryption_key = ? WHERE id = ?");
        $stmt->bind_param("si", $fixed_key, $client_id);
        
        if ($stmt->execute()) {
            echo "<p style='color: green;'>✅ Chiave sistemata!</p>";
            echo "<p><strong>Chiave corretta:</strong> " . htmlspecialchars($fixed_key) . "</p>";
        }
        $stmt->close();
        
    } else {
        echo "<p style='color: green;'>✅ Chiave è nel formato corretto!</p>";
    }
}

/**
 * Test completo del sistema di crittografia
 */
function testCompleteEncryption($encryption_key) {
    echo "<h3>Test Completo Sistema Crittografia</h3>";
    
    $test_data = [
        "Telefono test" => "+39 333 123 4567",
        "Messaggio test" => "Messaggio di test con àccènti speciali €",
        "Dati complessi" => "Nome: Mario\nCognome: Rossi\nEmail: mario@test.it"
    ];
    
    foreach ($test_data as $label => $data) {
        echo "<h4>{$label}</h4>";
        
        try {
            // Simula esattamente add_lead.php enhanced
            $iv = openssl_random_pseudo_bytes(16);
            $iv_hex = bin2hex($iv);
            
            // Crittografia
            $encrypted = openssl_encrypt($data, 'aes-256-cbc', hex2bin($encryption_key), 0, $iv);
            
            if ($encrypted === false) {
                echo "<p style='color: red;'>❌ Crittografia fallita per: {$label}</p>";
                continue;
            }
            
            echo "<p>✅ Crittografia OK</p>";
            
            // Test decrittografia
            $decrypted = decryptData($encrypted, $iv_hex, $encryption_key);
            
            if ($decrypted === $data) {
                echo "<p style='color: green;'>✅ Decrittografia OK - Dati corrispondenti!</p>";
            } else {
                echo "<p style='color: red;'>❌ Decrittografia fallita</p>";
                echo "<p>Originale: " . htmlspecialchars($data) . "</p>";
                echo "<p>Decrittografato: " . htmlspecialchars($decrypted) . "</p>";
            }
            
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Errore: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
        
        echo "<hr>";
    }
}

// Abilita debug
define('LEADAI_DEBUG', true);
?>
