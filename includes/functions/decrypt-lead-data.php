<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/decrypt.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['loggedin']) || !isset($_SESSION['user_id'])) {
    echo json_encode(["error" => "Accesso negato"]);
    exit;
}

$client_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['lead_id'])) {
    echo json_encode(["error" => "Dati mancanti"]);
    exit;
}

$lead_id = intval($data['lead_id']);

$stmt = $conn->prepare("SELECT l.phone, l.message, c.encryption_key
                        FROM leads l 
                        JOIN clients c ON l.clients_id = c.id 
                        WHERE l.id = ? AND l.clients_id = ?");
$stmt->bind_param("ii", $lead_id, $client_id);
$stmt->execute();
$result = $stmt->get_result();
$lead = $result->fetch_assoc();
$stmt->close();

if (!$lead) {
    echo json_encode(["error" => "Lead non trovato o accesso negato"]);
    exit;
}

$decryptedPhone = decryptData($lead['phone'], null, $lead['encryption_key']);
$decryptedMessage = decryptData($lead['message'], null, $lead['encryption_key']);

echo json_encode([
    "phone" => $decryptedPhone,
    "message" => $decryptedMessage
]);

$conn->close();
?>