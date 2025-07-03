<?php
require '../includes/db.php';
session_start();

if (!isset($_SESSION['loggedin']) || !isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "message" => "⚠️ Utente non loggato."]);
    exit;
}

$client_id = $_SESSION['user_id'];
setlocale(LC_TIME, 'it_IT.UTF-8');

// Data di una settimana fa
$oneWeekAgo = date('Y-m-d H:i:s', strtotime('-1 week'));

$stmt = $conn->prepare("
    SELECT p.name, p.surname, l.created_at 
    FROM leads l
    JOIN personas p ON l.personas_id = p.id
    WHERE l.clients_id = ? 
    AND l.status_id = 1
    AND l.created_at > ?
    ORDER BY l.created_at DESC
");
$stmt->bind_param("is", $client_id, $oneWeekAgo);
$stmt->execute();
$result = $stmt->get_result();

$leads = [];
while ($row = $result->fetch_assoc()) {
    $dataFormattata = strftime("%d %b %H:%M", strtotime($row['created_at']));
    $leads[] = [
        "name" => $row['name'],
        "surname" => $row['surname'],
        "created_at" => $dataFormattata
    ];    
}

echo json_encode([
    "status" => "success",
    "new_leads" => count($leads),
    "leads" => $leads
]);

$stmt->close();
$conn->close();
?>