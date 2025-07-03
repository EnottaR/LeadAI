<?php
require 'includes/db.php';
require_once 'includes/functions/decrypt.php';
session_start();

if (!isset($_SESSION['loggedin']) || !isset($_SESSION['user_id'])) {
    echo "<p style='text-align:center; font-size:14px; color:#666;'>Accesso negato.</p>";
    exit;
}

$client_id = $_SESSION['user_id'];

// Recupera la chiave di crittografia dell'utente
$stmt = $conn->prepare("SELECT encryption_key FROM clients WHERE id = ?");
$stmt->bind_param("i", $client_id);
$stmt->execute();
$stmt->bind_result($encryption_key);
$stmt->fetch();
$stmt->close();

// Array con ID per i conteggi
$status_counts = [
    "totale_nuovi_lead" => 1,
    "totale_in_lavorazione" => 5,
    "totale_preventivo_inviato" => 6,
    "totale_contratti_inviati" => 7,
    "totale_senza_risposta" => 2
];

$results = [];

// Query dinamica per ottenere i totali
foreach ($status_counts as $key => $status_id) {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM leads WHERE clients_id = ? AND status_id = ?");
    $stmt->bind_param("ii", $client_id, $status_id);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();
    $results[$key] = $count;
}

extract($results);

// Recupero tutti gli status disponibili per il client_type dell'utente
$stmt = $conn->prepare("SELECT type FROM clients WHERE id = ?");
$stmt->bind_param("i", $client_id);
$stmt->execute();
$stmt->bind_result($client_type);
$stmt->fetch();
$stmt->close();

$status_stmt = $conn->prepare("SELECT leads_status_id, label FROM status_labels WHERE clients_type = ?");
$status_stmt->bind_param("i", $client_type);
$status_stmt->execute();
$result = $status_stmt->get_result();
$status_options = [];
while ($row = $result->fetch_assoc()) {
    $status_options[$row['leads_status_id']] = $row['label'];
}
$status_stmt->close();
?>

<div class="projects-section-header">
    <p>
        <?php
        $nome_utente = htmlspecialchars($_SESSION['name'] ?? 'Ospite');
        $ora_corrente = (int) date('H');

        if ($ora_corrente >= 5 && $ora_corrente < 12) {
            $saluto = "Buongiorno";
        } elseif ($ora_corrente >= 12 && $ora_corrente < 18) {
            $saluto = "Buon pomeriggio";
        } else {
            $saluto = "Buonasera";
        }

        echo "$saluto, $nome_utente";
        ?>
    </p>

    <p class="tempo">
        <?php
        $data = new DateTime();
        $formattatore = new IntlDateFormatter(
            'it_IT',
            IntlDateFormatter::FULL,
            IntlDateFormatter::NONE,
            null,
            IntlDateFormatter::GREGORIAN
        );

        $formattatore->setPattern('d MMMM yyyy');
        echo mb_convert_case($formattatore->format($data), MB_CASE_TITLE, "UTF-8");
        ?>
    </p>
</div>

<div class="projects-section-line">
    <div class="projects-status">
        <div class="item-status">
            <span class="status-number"><?= htmlspecialchars($totale_nuovi_lead); ?></span>
            <span class="status-type">
                <?= $totale_nuovi_lead === 1 ? "Nuovo lead" : "Nuovi lead"; ?>
            </span>
        </div>
        <div class="item-status">
            <span class="status-number"><?= htmlspecialchars($totale_in_lavorazione); ?></span>
            <span class="status-type">In lavorazione</span>
        </div>
        <div class="item-status">
            <span class="status-number"><?= htmlspecialchars($totale_senza_risposta); ?></span>
            <span class="status-type">
                <span class="status-type">Senza risposta</span>
            </span>
        </div>
        <div class="item-status">
            <span class="status-number"><?= htmlspecialchars($totale_preventivo_inviato); ?></span>
            <span class="status-type">
                <?= $totale_preventivo_inviato === 1 ? "Preventivo inviato" : "Preventivi inviati"; ?>
            </span>
        </div>
        <div class="item-status">
            <span class="status-number"><?= htmlspecialchars($totale_contratti_inviati); ?></span>
            <span class="status-type">
                <?= $totale_contratti_inviati === 1 ? "Contratto inviato" : "Contratti inviati"; ?>
            </span>
        </div>
    </div>
    <div class="view-actions">
        <button id="reset-order" class="view-btn" title="Ripristina ordine originale">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M3 2v6h6"></path>
                <path d="M3 8C5.33333 5.33333 8.33333 4 12 4c7.3333 0 10 5.33333 10 8 0 1.3333-.6667 2.6667-2 4"></path>
                <path d="M21 22v-6h-6"></path>
                <path d="M21 16c-2.3333 2.6667-5.3333 4-9 4-7.3333 0-10-5.3333-10-8 0-1.3333.6667-2.6667 2-4"></path>
            </svg>
        </button>
        <button class="view-btn list-view" title="Visualizza a lista">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-list">
                <line x1="8" y1="6" x2="21" y2="6" />
                <line x1="8" y1="12" x2="21" y2="12" />
                <line x1="8" y1="18" x2="21" y2="18" />
                <line x1="3" y1="6" x2="3.01" y2="6" />
                <line x1="3" y1="12" x2="3.01" y2="12" />
                <line x1="3" y1="18" x2="3.01" y2="18" />
            </svg>
        </button>
        <button class="view-btn grid-view active" title="Visualizza in griglia">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-grid">
                <rect x="3" y="3" width="7" height="7" />
                <rect x="14" y="3" width="7" height="7" />
                <rect x="14" y="14" width="7" height="7" />
                <rect x="3" y="14" width="7" height="7" />
            </svg>
        </button>
    </div>
</div>

<div class="project-boxes jsGridView">
    <?php
    $stmt = $conn->prepare("
        SELECT l.id, p.name, p.surname, l.status_id, l.created_at, l.message,
               l.lead_source_url, l.lead_type
        FROM leads l
        JOIN personas p ON l.personas_id = p.id
        WHERE l.clients_id = ?
        ORDER BY l.created_at DESC
    ");
    $stmt->bind_param("i", $client_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $leads = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $status_classes = [
        1 => 'status-new-lead',
        2 => 'status-no-response',
        5 => 'status-in-progress',
        6 => 'status-quote-sent',
        7 => 'status-contract-sent',
        8 => 'status-contract-spam',
    ];

    foreach ($leads as $lead):
        $status_class = $status_classes[$lead['status_id']] ?? 'status-default';
        $status_label = htmlspecialchars($status_options[$lead['status_id']] ?? 'null');
        $decryptedMessage = htmlspecialchars(decryptData($lead['message'], null, $encryption_key));

        // NUOVA LOGICA: Determino la tipologia del lead
        $lead_type = $lead['lead_type'] ?? 'Semplice/Organico';

        // Se non è presente nel DB, determino dalla URL (per lead vecchi)
        if (empty($lead_type) && !empty($lead['lead_source_url'])) {
            $lead_type = (preg_match('/\.com\/gad/i', $lead['lead_source_url'])) ? 'Google ADS' : 'Semplice/Organico';
        }

        $type_color = ($lead_type === 'Google ADS') ? '#4285f4' : '#34a853';
    ?>
        <div class="project-box-wrapper" data-lead-id="<?= $lead['id']; ?>">
            <div class="project-box <?= $status_class; ?>">
                <div class="project-box-header">
                    <div class="days-left"><?= $status_label; ?></div>
                </div>
                <div class="project-box-content-header">
                    <p class="box-content-header"><?= htmlspecialchars($lead['name'] . ' ' . strtoupper(substr($lead['surname'], 0, 1)) . '.'); ?></p>
                    <?php
                    $shortMessage = (strlen($decryptedMessage) > 30)
                        ? substr($decryptedMessage, 0, 30) . "..."
                        : $decryptedMessage;
                    ?>
                    <p class="box-content-subheader"><?= $shortMessage; ?></p>
                </div>
                <div class="project-box-footer">
                    <span class="lead-type-badge" style="font-size: 12px; color: <?= $type_color; ?>;" title="<?= $lead_type; ?>">
                        <?= $lead_type; ?>
                    </span>

                    <div class="lead-date">
                        <?php
                        setlocale(LC_TIME, 'it_IT.UTF-8');
                        ?>
                        <?= strftime("%d %B %Y", strtotime($lead['created_at'])); ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>