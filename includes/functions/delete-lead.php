<?php
require_once __DIR__ . '/../db.php';
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['loggedin']) || !isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "message" => "⚠️ Accesso negato."]);
    exit;
}

$client_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(["status" => "error", "message" => "⚠️ Nessun dato ricevuto"]);
    exit;
}

try {
    $conn->autocommit(FALSE);

    if (isset($data['lead_id'])) {
        $lead_id = intval($data['lead_id']);

        $stmt = $conn->prepare("SELECT p.name, p.surname FROM leads l JOIN personas p ON l.personas_id = p.id WHERE l.id = ? AND l.clients_id = ?");
        $stmt->bind_param("ii", $lead_id, $client_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $lead_info = $result->fetch_assoc();

        if (!$lead_info) {
            throw new Exception("Lead non trovato o accesso negato");
        }
        $stmt->close();

        $stmt = $conn->prepare("DELETE FROM leads WHERE id = ? AND clients_id = ?");
        $stmt->bind_param("ii", $lead_id, $client_id);

        if (!$stmt->execute()) {
            throw new Exception("Errore durante la cancellazione del lead");
        }
        $stmt->close();

        $conn->commit();
        echo json_encode([
            "status" => "success", 
            "message" => "<i class='fas fa-check-circle'></i> Lead di {$lead_info['name']} {$lead_info['surname']} cancellato con successo!",
            "deleted_count" => 1
        ]);

    }
    elseif (isset($data['lead_ids']) && is_array($data['lead_ids'])) {
        $lead_ids = array_map('intval', $data['lead_ids']);

        if (empty($lead_ids)) {
            throw new Exception("Nessun lead selezionato per la cancellazione");
        }

        $placeholders = str_repeat('?,', count($lead_ids) - 1) . '?';
        $stmt = $conn->prepare("SELECT COUNT(*) FROM leads WHERE id IN ($placeholders) AND clients_id = ?");

        $params = array_merge($lead_ids, [$client_id]);
        $types = str_repeat('i', count($lead_ids)) . 'i';
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();

        if ($count !== count($lead_ids)) {
            throw new Exception("Alcuni lead non sono stati trovati o l'accesso è negato");
        }

        $stmt = $conn->prepare("DELETE FROM leads WHERE id IN ($placeholders) AND clients_id = ?");
        $stmt->bind_param($types, ...$params);

        if (!$stmt->execute()) {
            throw new Exception("Errore durante la cancellazione dei lead");
        }

        $deleted_count = $stmt->affected_rows;
        $stmt->close();

        $conn->commit();
        echo json_encode([
            "status" => "success", 
            "message" => "<i class='fas fa-check-circle'></i> $deleted_count lead cancellati con successo!",
            "deleted_count" => $deleted_count
        ]);

    } else {
        throw new Exception("Parametri di cancellazione non validi");
    }

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode([
        "status" => "error", 
        "message" => "⚠️ " . $e->getMessage()
    ]);
}

$conn->autocommit(TRUE);
$conn->close();
?>