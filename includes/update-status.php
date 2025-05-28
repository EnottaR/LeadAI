<?php
require 'db.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(["status" => "error", "message" => "⚠️ Nessun dato ricevuto"]);
    exit;
}

if (!isset($data['lead_id']) || !isset($data['status_id'])) {
    echo json_encode(["status" => "error", "message" => "⚠️ Dati mancanti!"]);
    exit;
}

$lead_id = intval($data['lead_id']);
$status_id = intval($data['status_id']);

if (!$conn) {
    echo json_encode(["status" => "error", "message" => "⚠️ Errore di connessione al database"]);
    exit;
}

// Controllo se il lead esiste
$stmt = $conn->prepare("SELECT p.email, p.name, p.surname FROM leads l JOIN personas p ON l.personas_id = p.id WHERE l.id = ?");
if (!$stmt) {
    echo json_encode(["status" => "error", "message" => "⚠️ Errore nella preparazione della query"]);
    exit;
}
$stmt->bind_param("i", $lead_id);
$stmt->execute();
$stmt->bind_result($lead_email, $lead_name, $lead_surname);
$stmt->fetch();
$stmt->close();

if (!$lead_email) {
    echo json_encode(["status" => "error", "message" => "⚠️ Lead non trovato o senza email"]);
    exit;
}

// Aggiorna lo status
$stmt = $conn->prepare("UPDATE leads SET status_id = ?, status_updated_at = NOW() WHERE id = ?");
if (!$stmt) {
    echo json_encode(["status" => "error", "message" => "⚠️ Errore nella preparazione dell'aggiornamento"]);
    exit;
}

$stmt->bind_param("ii", $status_id, $lead_id);
$success = $stmt->execute();
$stmt->close();

if (!$success) {
    echo json_encode(["status" => "error", "message" => "⚠️ Errore durante l'aggiornamento"]);
    exit;
}

// Invio mail + template se lo status viene cambiato in Contattato senza risposta
if ($status_id == 2) {
    $subject = "📌 Ti abbiamo contattato!";
    $message = "Ciao $lead_name,\n\n";
    $message .= "Abbiamo provato a contattarti ma non abbiamo ricevuto risposta.\n";
    $message .= "Se hai ancora bisogno di informazioni, rispondi a questa email.\n\n";
    $message .= "Cordiali saluti,\nIl Team LeadAI";

    $headers = "From: no-reply@tuodominio.com\r\n";
    $headers .= "Reply-To: no-reply@tuodominio.com\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

    mail($lead_email, $subject, $message, $headers);
}

echo json_encode(["status" => "success", "message" => "<i class='fas fa-check-circle'></i> Status aggiornato con successo!"]);
$conn->close();
?>
