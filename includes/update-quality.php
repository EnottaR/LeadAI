<?php
require 'db.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$lead_id = intval($data['lead_id']);
$quality_rating = isset($data['quality_rating']) && $data['quality_rating'] !== '' ? intval($data['quality_rating']) : null;

$stmt = $conn->prepare("UPDATE leads SET quality_rating = ? WHERE id = ?");
if ($quality_rating === null) {
    $stmt->bind_param("si", $quality_rating, $lead_id);
} else {
    $stmt->bind_param("ii", $quality_rating, $lead_id);
}
$stmt->execute();
$stmt->close();

echo json_encode(["status" => "success"]);
$conn->close();
?>