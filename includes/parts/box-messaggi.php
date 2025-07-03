<?php
require 'includes/db.php';
require_once 'includes/functions/decrypt.php';
session_start();

if (!isset($_SESSION['loggedin']) || !isset($_SESSION['user_id'])) {
    echo "<p style='text-align:center; font-size:14px; color:#666;'>Nessun messaggio disponibile.</p>";
    exit;
}

$client_id = $_SESSION['user_id'];

// Recuperiamo la chiave di crittografia dell'utente loggato
$stmt = $conn->prepare("SELECT encryption_key FROM clients WHERE id = ?");
$stmt->bind_param("i", $client_id);
$stmt->execute();
$stmt->bind_result($encryption_key);
$stmt->fetch();
$stmt->close();

// Controlliamo quanti messaggi totali ci sono
$stmt = $conn->prepare("SELECT COUNT(*) FROM leads WHERE clients_id = ?");
$stmt->bind_param("i", $client_id);
$stmt->execute();
$stmt->bind_result($total_leads);
$stmt->fetch();
$stmt->close();

$showViewAll = ($total_leads > 0); // Mostra il link se ci sono almeno 1 messaggio

// Recuperiamo SOLO gli ultimi 5 messaggi (ordinati dal più recente)
$stmt = $conn->prepare("
    SELECT l.id, p.name, p.surname, l.message, l.created_at
    FROM leads l
    JOIN personas p ON l.personas_id = p.id
    WHERE l.clients_id = ?
    ORDER BY l.created_at DESC
    LIMIT 5
");
$stmt->bind_param("i", $client_id);
$stmt->execute();
$result = $stmt->get_result();
$leads = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

setlocale(LC_TIME, 'it_IT.UTF-8');
?>

<div class="messages-section">
    <button class="messages-close">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-x-circle">
            <circle cx="12" cy="12" r="10" />
            <line x1="15" y1="9" x2="9" y2="15" />
            <line x1="9" y1="9" x2="15" y2="15" />
        </svg>
    </button>
    <div class="projects-section-header">
        <p>Ultimi Messaggi</p>
    </div>
    <div class="messages">
        <?php if (!empty($leads) && is_array($leads)): ?>
            <?php foreach ($leads as $lead): ?>
                <div class="message-box">
                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($lead['name'] . '+' . $lead['surname']) ?>&background=random&color=fff" alt="profile image">
                    <div class="message-content">
                        <div class="message-header">
                            <div class="name">
                                <?= htmlspecialchars($lead['name'] . ' ' . strtoupper(substr($lead['surname'], 0, 1)) . '.') ?>
                            </div>
                            <div class="star-checkbox">
                                <input type="checkbox" id="star-<?= $lead['id']; ?>">
                                <label for="star-<?= $lead['id']; ?>">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-star">
                                        <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2" />
                                    </svg>
                                </label>
                            </div>
                        </div>
                        <p class="messaggio-linea">
                            <?= htmlspecialchars(decryptData($lead['message'], null, $encryption_key)); ?>
                        </p>
                        <p class="messaggio-linea tempo">
                            <?= strftime("%d %b", strtotime($lead['created_at'])); ?>
                        </p>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="text-align:center; font-size:14px; color:#666;">Nessun messaggio disponibile.</p>
        <?php endif; ?>
    </div>

    <?php if ($showViewAll): ?>
        <div class="visualizza-tutti-container">
            <a href="leads.php" class="visualizza-tutti">Visualizza tutti</a>
        </div>
    <?php endif; ?>
</div>