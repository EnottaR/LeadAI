<?php
function decryptData($encryptedData, $iv, $encryption_key) {
    if (!$encryptedData || !$iv || !$encryption_key) {
        return "Errore: dati mancanti!";
    }

    if (ctype_xdigit($iv) && strlen($iv) === 32) {
        $iv = hex2bin($iv);
    }

    $decrypted = openssl_decrypt($encryptedData, 'aes-256-cbc', $encryption_key, 0, $iv);
    return $decrypted !== false && $decrypted !== null ? $decrypted : "Errore: decriptazione fallita!";
}
?>