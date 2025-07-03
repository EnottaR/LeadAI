<?php
require __DIR__ . '/../../includes/db.php';
session_start();

if (!isset($_SESSION['loggedin']) || !isset($_SESSION['user_id'])) {
    die("Accesso negato.");
}

$client_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT email FROM clients WHERE id = ?");
$stmt->bind_param("i", $client_id);
$stmt->execute();
$stmt->bind_result($user_email);
$stmt->fetch();
$stmt->close();

if (!$user_email) {
    die("Errore: Nessuna email trovata per l'utente.");
}

$stmt = $conn->prepare("
    SELECT p.name, p.surname, l.created_at 
    FROM leads l
    JOIN personas p ON l.personas_id = p.id
    WHERE l.clients_id = ? 
    ORDER BY l.created_at DESC 
    LIMIT 1
");
$stmt->bind_param("i", $client_id);
$stmt->execute();
$result = $stmt->get_result();
$lead = $result->fetch_assoc();
$stmt->close();

if (!$lead) {
    die("Errore: Nessun lead trovato.");
}

$data_lead = date("d M Y - H:i", strtotime($lead['created_at']));

// Template + invio mail utente loggato
$subject = "📌 Nuovo Lead Ricevuto!";
$message = "Ciao, hai ricevuto un nuovo lead!\n\n";
$message .= "Nome: " . $lead['name'] . " " . $lead['surname'] . "\n";
$message .= "Data Ricezione: " . $data_lead . "\n\n";
$message .= "Accedi al tuo account per visualizzarlo.";

$headers = "From: no-reply@leadai.com\r\n"; // verificare gli header in futuro, finiscono in spam
$headers .= "Reply-To: no-reply@leadai.com\r\n"; // idem come sopra
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

mail($user_email, $subject, $message, $headers);

echo "Email inviata a " . $user_email;
?>
