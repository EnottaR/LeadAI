<?php
/**
 * Decrittazione dati lead (AES-256-CBC)
 * - Usa chiave hex (da tabella clients.encryption_key)
 * - Usa IV binario (direttamente da leads.iv, senza base64_decode)
 * - I dati sono cifrati e salvati come base64 (es: leads.phone, leads.message)
 */

function decryptData(string $encryptedBase64, string $hexKey, string $ivBinary): ?string {
    // Converti la chiave da esadecimale a binario
    $key = hex2bin($hexKey);
    if ($key === false) {
        throw new Exception("Chiave non valida (hex malformato)");
    }

    // Decodifica i dati da base64
    $ciphertext = base64_decode($encryptedBase64);
    if ($ciphertext === false) {
        throw new Exception("Base64 non valido nei dati da decifrare");
    }

    // Decrittazione AES-256-CBC
    $plaintext = openssl_decrypt(
        $ciphertext,
        'AES-256-CBC',
        $key,
        OPENSSL_RAW_DATA,
        $ivBinary
    );

    return $plaintext ?: null; // Ritorna null se fallisce
}

// Esempio d'uso (valori fittizi da sostituire):
/*
$iv = hex2bin('6c8f633f28a0b5c79b791343b4459268'); // oppure direttamente da query binaria
$key = 'f432fd7b65dfd7439c6b178e4b9b490587fce365612f59e537969bb99bed871c';
$encryptedPhone = '6n9CuYiMgzIPbNwpj6iTAg==';

$decryptedPhone = decryptData($encryptedPhone, $key, $iv);
echo "Telefono: " . $decryptedPhone;
*/
?>
