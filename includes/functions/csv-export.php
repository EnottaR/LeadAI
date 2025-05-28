<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/decrypt.php';
session_start();

if (!isset($_SESSION['loggedin']) || !isset($_SESSION['user_id'])) {
    die("Accesso negato.");
}

$client_id = $_SESSION['user_id'];

setlocale(LC_TIME, 'it_IT.UTF-8');

// Splitto gli username, così scaricano il proprio csv senza pescare tutti i dati dalla tabella leads
$stmt_user = $conn->prepare("SELECT username, type FROM clients WHERE id = ?");
$stmt_user->bind_param("i", $client_id);
$stmt_user->execute();
$stmt_user->bind_result($username, $client_type);
$stmt_user->fetch();
$stmt_user->close();

$timestamp = date("Y-m-d");
$sanitized_username = preg_replace('/[^a-zA-Z0-9_-]/', '', $username);
$filename = "lead-esportati-{$timestamp}-{$sanitized_username}.csv";

// Header per il download
header('Content-Type: text/csv; charset=utf-8');
header("Content-Disposition: attachment; filename=\"$filename\"");
echo "\xEF\xBB\xBF"; // BOM: forzo la crittografia UTF per evitare di impallarci su caratteri speciali

$output = fopen('php://output', 'w');

// Nomi colonne
fputcsv($output, ['Lead_ID', 'Nome', 'Cognome', 'Email', 'Telefono', 'Status', 'Creato il', 'Messaggio'], ';');

// Recupero i lead con lo status corretto per tipologia di cliente 1-2
$stmt = $conn->prepare("
    SELECT l.id, p.name, p.surname, p.email, l.phone, l.message, 
           sl.label as status_label, l.created_at, HEX(l.iv) as iv
    FROM leads l
    JOIN personas p ON l.personas_id = p.id
    LEFT JOIN status_labels sl ON l.status_id = sl.leads_status_id AND sl.clients_type = ?
    WHERE l.clients_id = ?
    ORDER BY l.created_at DESC
");

$stmt->bind_param("ii", $client_type, $client_id);
$stmt->execute();
$result = $stmt->get_result();

$stmt_key = $conn->prepare("SELECT encryption_key FROM clients WHERE id = ?");
$stmt_key->bind_param("i", $client_id);
$stmt_key->execute();
$stmt_key->bind_result($encryption_key);
$stmt_key->fetch();
$stmt_key->close();

// Scrittura csv
while ($row = $result->fetch_assoc()) {
    $decryptedPhone = decryptData($row['phone'], $row['iv'], $encryption_key);
    $decryptedMessage = str_replace("\n", " ", decryptData($row['message'], $row['iv'], $encryption_key));

    fputcsv($output, [
        $row['id'],
        $row['name'],
        $row['surname'],
        $row['email'],
        $decryptedPhone,
        $row['status_label'] ?: 'null', // nessun valore valido, rimando a null
        strftime("%d %b %Y - %H:%M", strtotime($row['created_at'])),
        $decryptedMessage
    ], ';');
}

fclose($output);
$stmt->close();
$conn->close();
?>
