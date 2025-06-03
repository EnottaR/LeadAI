<?php
$pdo = require_once 'db.php';

// ottieni il clients_id dalla query string
$clients_id = $_GET['clients_id'] ?? 0;

if ($clients_id == 0) {
    echo "errore: clients_id non valido.";
    exit;
}

// funzione per ottenere la label dello status
function getStatusLabel($status_id, $client_type) {
    global $pdo;
    
    // query per recuperare la label in base al leads_status_id e clients_type
    $queryStatus = "SELECT label 
                    FROM status_labels 
                    WHERE leads_status_id = :status_id 
                    AND clients_type = :client_type 
                    LIMIT 1";
    $stmtStatus = $pdo->prepare($queryStatus);
    $stmtStatus->bindParam(':status_id', $status_id);
    $stmtStatus->bindParam(':client_type', $client_type);
    $stmtStatus->execute();
    $status = $stmtStatus->fetch(PDO::FETCH_ASSOC);
    
    return $status ? $status['label'] : 'non definito';
}

// funzione per decriptare i dati sensibili
function decryptData($encryptedData, $encryption_key, $iv) {
    // decripta il dato usando AES-256-CBC
    return openssl_decrypt($encryptedData, 'aes-256-cbc', $encryption_key, 0, $iv);
}

// query per ottenere tutti i leads del client
$queryLeads = "
    SELECT 
        leads.id AS lead_id, 
        leads.phone, 
        leads.message, 
        leads.ip, 
        leads.status_id, 
        leads.status_updated_at, 
        leads.notes, 
        leads.created_at AS lead_created_at,
        leads.iv AS lead_iv,
        personas.name, 
        personas.surname, 
        personas.email,
        clients.encryption_key,
        clients.type
    FROM leads
    INNER JOIN personas ON leads.personas_id = personas.id
    INNER JOIN clients ON leads.clients_id = clients.id
    WHERE leads.clients_id = :clients_id
    ORDER BY leads.created_at DESC
";
$stmtLeads = $pdo->prepare($queryLeads);
$stmtLeads->bindParam(':clients_id', $clients_id);
$stmtLeads->execute();
$leads = $stmtLeads->fetchAll(PDO::FETCH_ASSOC);

// verifica che ci siano leads
if (empty($leads)) {
    echo "non ci sono leads per questo client.";
    exit;
}

// raggruppamento per personas
$groupedLeads = [];
foreach ($leads as $lead) {
    $personKey = $lead['name'] . ' ' . $lead['surname'] . ' ' . $lead['email'];
    if (!isset($groupedLeads[$personKey])) {
        $groupedLeads[$personKey] = [];
    }
    $groupedLeads[$personKey][] = $lead;
}

?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lead del Cliente</title>
	
	<style>
	body {font-size:14px; font-family:helvetica;}
	.lead {margin-left:100px;}
	</style>
</head>
<body>

    <h1>Lead per il cliente</h1>

    <?php foreach ($groupedLeads as $personKey => $group): ?>
        <div class="lead-group">
            <h3><?php echo htmlspecialchars($group[0]['name'] . ' ' . $group[0]['surname'] . ' (' . $group[0]['email'] . ')'); ?></h3>
            <?php foreach ($group as $lead): ?>
                <div class="lead">
                    <p><strong>ID Lead:</strong> <?php echo $lead['lead_id']; ?></p>
                    
                    <!-- Decripta i dati del lead -->
                    <?php
                    $decryptedPhone = decryptData($lead['phone'], $lead['encryption_key'], $lead['lead_iv']);
                    $decryptedMessage = decryptData($lead['message'], $lead['encryption_key'], $lead['lead_iv']);
                    ?>
                    
                    <p><strong>Telefono:</strong> <?php echo $decryptedPhone; ?></p>
                    <p><strong>Messaggio:</strong> <?php echo htmlspecialchars($decryptedMessage); ?></p>
                    <p><strong>IP:</strong> <?php echo $lead['ip']; ?></p>
                    <p><strong>Status:</strong> <?php echo getStatusLabel($lead['status_id'], $lead['type']); ?></p>
                    <p><strong>Status aggiornato il:</strong> <?php echo $lead['status_updated_at']; ?></p>
                    <p><strong>Note:</strong> <?php echo htmlspecialchars($lead['notes']); ?></p>
                    <p><strong>Creato il:</strong> <?php echo $lead['lead_created_at']; ?><br><br></p>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endforeach; ?>

</body>
</html>