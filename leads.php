<?php
require 'includes/auth-checker.php';
$pageTitle = "Leads | LeadAI";
include 'includes/parts/header.php';
require 'includes/db.php';
require_once 'includes/functions/decrypt.php';

if (!isset($_SESSION['loggedin']) || !isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$client_id = $_SESSION['user_id'];

// Parametri Paginazione
$leads_per_page = 10;
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($current_page - 1) * $leads_per_page;

// Parametri filtri
$filter_name = isset($_GET['filter_name']) ? trim($_GET['filter_name']) : '';
$filter_status = isset($_GET['filter_status']) ? intval($_GET['filter_status']) : '';
$filter_month = isset($_GET['filter_month']) ? intval($_GET['filter_month']) : '';
$filter_year = isset($_GET['filter_year']) ? intval($_GET['filter_year']) : '';

$stmt = $conn->prepare("SELECT encryption_key, type FROM clients WHERE id = ?");
$stmt->bind_param("i", $client_id);
$stmt->execute();
$stmt->bind_result($encryption_key, $client_type);
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

// Costruisco la query per i filtri
$where_conditions = ["l.clients_id = ?"];
$params = [$client_id];
$param_types = "i";

// Filtraggio per Nome / Cognome
if (!empty($filter_name)) {
    $where_conditions[] = "(p.name LIKE ? OR p.surname LIKE ? OR CONCAT(p.name, ' ', p.surname) LIKE ?)";
    $search_term = "%{$filter_name}%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $param_types .= "sss";
}

// Filtraggio per status
if (!empty($filter_status)) {
    $where_conditions[] = "l.status_id = ?";
    $params[] = $filter_status;
    $param_types .= "i";
}

// Filtraggio per mese + anno
if (!empty($filter_month) && !empty($filter_year)) {
    $where_conditions[] = "YEAR(l.created_at) = ? AND MONTH(l.created_at) = ?";
    $params[] = $filter_year;
    $params[] = $filter_month;
    $param_types .= "ii";
} elseif (!empty($filter_year)) {
    $where_conditions[] = "YEAR(l.created_at) = ?";
    $params[] = $filter_year;
    $param_types .= "i";
} elseif (!empty($filter_month)) {
    $where_conditions[] = "MONTH(l.created_at) = ?";
    $params[] = $filter_month;
    $param_types .= "i";
}

$where_clause = implode(" AND ", $where_conditions);

$count_query = "SELECT COUNT(*) FROM leads l JOIN personas p ON l.personas_id = p.id WHERE {$where_clause}";
$count_stmt = $conn->prepare($count_query);
if (!empty($params)) {
    $count_stmt->bind_param($param_types, ...$params);
}
$count_stmt->execute();
$count_stmt->bind_result($total_leads);
$count_stmt->fetch();
$count_stmt->close();

$total_pages = ceil($total_leads / $leads_per_page);

// Query principale per i filtri
$main_query = "
    SELECT l.id, p.name, p.surname, p.email, l.phone, l.message, l.status_id, l.created_at, 
           HEX(l.iv) as iv, l.ip
    FROM leads l
    JOIN personas p ON l.personas_id = p.id
    WHERE {$where_clause}
    ORDER BY l.created_at DESC
    LIMIT ? OFFSET ?
";

// Parametri per LIMIT e Offset
$params[] = $leads_per_page;
$params[] = $offset;
$param_types .= "ii";

$stmt = $conn->prepare($main_query);
$stmt->bind_param($param_types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$leads = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

setlocale(LC_TIME, 'it_IT.UTF-8');

function generatePaginationLinks($current_page, $total_pages, $max_links = 5) {
    $links = [];
    
    $filter_params = [];
    if (!empty($_GET['filter_name'])) $filter_params['filter_name'] = $_GET['filter_name'];
    if (!empty($_GET['filter_status'])) $filter_params['filter_status'] = $_GET['filter_status'];
    if (!empty($_GET['filter_month'])) $filter_params['filter_month'] = $_GET['filter_month'];
    if (!empty($_GET['filter_year'])) $filter_params['filter_year'] = $_GET['filter_year'];
    
    $base_url = '?' . http_build_query($filter_params);
    $separator = empty($filter_params) ? '?' : '&';
    
    $start = max(1, $current_page - floor($max_links / 2));
    $end = min($total_pages, $start + $max_links - 1);
    
    if ($end - $start + 1 < $max_links) {
        $start = max(1, $end - $max_links + 1);
    }
    
    if ($current_page > 1) {
        $links[] = ['type' => 'prev', 'page' => $current_page - 1, 'text' => '‹', 'url' => $base_url . $separator . 'page=' . ($current_page - 1)];
    }
    
    if ($start > 1) {
        $links[] = ['type' => 'page', 'page' => 1, 'text' => '1', 'url' => $base_url . $separator . 'page=1'];
        if ($start > 2) {
            $links[] = ['type' => 'ellipsis', 'text' => '...'];
        }
    }
    
    for ($i = $start; $i <= $end; $i++) {
        $links[] = [
            'type' => 'page', 
            'page' => $i, 
            'text' => $i, 
            'active' => $i == $current_page,
            'url' => $base_url . $separator . 'page=' . $i
        ];
    }
    
    if ($end < $total_pages) {
        if ($end < $total_pages - 1) {
            $links[] = ['type' => 'ellipsis', 'text' => '...'];
        }
        $links[] = ['type' => 'page', 'page' => $total_pages, 'text' => $total_pages, 'url' => $base_url . $separator . 'page=' . $total_pages];
    }
    
    if ($current_page < $total_pages) {
        $links[] = ['type' => 'next', 'page' => $current_page + 1, 'text' => '›', 'url' => $base_url . $separator . 'page=' . ($current_page + 1)];
    }
    
    return $links;
}

$pagination_links = generatePaginationLinks($current_page, $total_pages);

// Genera array per anni (cambia $current_year - [numero] per mostrare il range di anni)
$years = [];
$current_year = date('Y');
for ($i = $current_year; $i >= ($current_year - 3); $i--) {
    $years[] = $i;
}

// Array dei mesi
$months = [
    1 => 'Gennaio', 2 => 'Febbraio', 3 => 'Marzo', 4 => 'Aprile',
    5 => 'Maggio', 6 => 'Giugno', 7 => 'Luglio', 8 => 'Agosto',
    9 => 'Settembre', 10 => 'Ottobre', 11 => 'Novembre', 12 => 'Dicembre'
];
?>

<body>
    <div class="app-container">
        <?php include 'includes/parts/head.php'; ?>
        <div class="app-content">
            <?php include 'includes/parts/sidebar.php'; ?>
            <div class="projects-section" style="overflow: auto;">
                <div class="projects-section-header">
                    <p>I tuoi lead</p>
                    <div class="header-actions">
                        <button id="toggle-filters" class="btn-secondary filter-toggle-btn">
                            <i class="fa-solid fa-sliders"></i>Filtri
                            <span class="filter-count" id="filter-count" style="display: none;"></span>
                        </button>
                        <button id="download-csv" class="btn-primary">
                            <i class="fa-regular fa-file-excel"></i> Esporta in csv
                        </button>
                    </div>
                </div>
                
                <div class="filter-section" id="filter-section" style="display: none;">
                    <div class="filter-header">
                        <h4><i class="fa-solid fa-sliders"></i> Filtra i tuoi lead per:</h4>
                        <button type="button" id="clear-filters" class="clear-filters-btn">
                            <i class="fas fa-times"></i> Pulisci filtri
                        </button>
                    </div>
                    
                    <form method="GET" action="leads.php" class="filter-form" id="filter-form">
                        <div class="filter-row">
                            <div class="filter-group">
                                <label for="filter_name">Nome o Cognome</label>
                                <input type="text" 
                                       id="filter_name" 
                                       name="filter_name" 
                                       placeholder="Scrivi un nome oppure un cognome"
                                       value="<?= htmlspecialchars($filter_name) ?>"
                                       class="filter-input">
                            </div>
                            
                            <div class="filter-group">
                                <label for="filter_status">Status</label>
                                <select id="filter_status" name="filter_status" class="filter-select">
                                    <option value="">Tutti gli status</option>
                                    <?php foreach ($status_options as $id => $label): ?>
                                        <option value="<?= $id ?>" <?= ($id == $filter_status) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($label) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="filter-group">
                                <label for="filter_month">Mese</label>
                                <select id="filter_month" name="filter_month" class="filter-select">
                                    <option value="">Tutti i mesi</option>
                                    <?php foreach ($months as $num => $name): ?>
                                        <option value="<?= $num ?>" <?= ($num == $filter_month) ? 'selected' : '' ?>>
                                            <?= $name ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="filter-group">
                                <label for="filter_year">Anno</label>
                                <select id="filter_year" name="filter_year" class="filter-select">
                                    <option value="">Tutti gli anni</option>
                                    <?php foreach ($years as $year): ?>
                                        <option value="<?= $year ?>" <?= ($year == $filter_year) ? 'selected' : '' ?>>
                                            <?= $year ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="filter-group">
                                <button type="submit" class="filter-apply-btn">
                                    <i class="fas fa-search"></i> Applica
                                </button>
                            </div>
                        </div>
                    </form>
                    
                    <?php if (!empty($filter_name) || !empty($filter_status) || !empty($filter_month) || !empty($filter_year)): ?>
                        <div class="active-filters">
                            <?php if (!empty($filter_name)): ?>
                                <span class="filter-tag">
                                    Nome: "<?= htmlspecialchars($filter_name) ?>"
                                    <?php 
                                    $remove_params = $_GET;
                                    unset($remove_params['filter_name']);
                                    $remove_url = '?' . http_build_query(array_filter($remove_params));
                                    if ($remove_url === '?') $remove_url = 'leads.php';
                                    ?>
                                    <a href="<?= $remove_url ?>" class="remove-filter">×</a>
                                </span>
                            <?php endif; ?>
                            <?php if (!empty($filter_status)): ?>
                                <span class="filter-tag">
                                    Status: <?= htmlspecialchars($status_options[$filter_status]) ?>
                                    <?php 
                                    $remove_params = $_GET;
                                    unset($remove_params['filter_status']);
                                    $remove_url = '?' . http_build_query(array_filter($remove_params));
                                    if ($remove_url === '?') $remove_url = 'leads.php';
                                    ?>
                                    <a href="<?= $remove_url ?>" class="remove-filter">×</a>
                                </span>
                            <?php endif; ?>
                            <?php if (!empty($filter_month)): ?>
                                <span class="filter-tag">
                                    Mese: <?= $months[$filter_month] ?>
                                    <?php 
                                    $remove_params = $_GET;
                                    unset($remove_params['filter_month']);
                                    $remove_url = '?' . http_build_query(array_filter($remove_params));
                                    if ($remove_url === '?') $remove_url = 'leads.php';
                                    ?>
                                    <a href="<?= $remove_url ?>" class="remove-filter">×</a>
                                </span>
                            <?php endif; ?>
                            <?php if (!empty($filter_year)): ?>
                                <span class="filter-tag">
                                    Anno: <?= $filter_year ?>
                                    <?php 
                                    $remove_params = $_GET;
                                    unset($remove_params['filter_year']);
                                    $remove_url = '?' . http_build_query(array_filter($remove_params));
                                    if ($remove_url === '?') $remove_url = 'leads.php';
                                    ?>
                                    <a href="<?= $remove_url ?>" class="remove-filter">×</a>
                                </span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                </div>
                
                <div class="table-container">
                    <table class="leads-table">
                        <thead>
                            <tr>
                                <th>Dettagli</th>
                                <th>Nome</th>
                                <th>Cognome</th>
                                <th>Email</th>
                                <th>Telefono</th>
                                <th>Status</th>
                                <th>Creato il</th>
                                <th>Messaggio</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($leads) > 0): ?>
                                <?php foreach ($leads as $lead): ?>
                                    <tr>
                                        <td>
                                            <button class="details-btn" onclick="openOffCanvas(<?= htmlspecialchars(json_encode($lead), ENT_QUOTES, 'UTF-8') ?>)">
                                                <i class="fas fa-info-circle"></i>
                                            </button>
                                        </td>
                                        <td><?= htmlspecialchars($lead['name']); ?></td>
                                        <td><?= htmlspecialchars($lead['surname']); ?></td>
                                        <td><?= htmlspecialchars($lead['email']); ?></td>
                                        <td><?= decryptData($lead['phone'], $lead['iv'], $encryption_key) ?></td>
                                        <td>
                                            <select class="status-select <?= 'status-' . $lead['status_id']; ?>" data-lead-id="<?= $lead['id']; ?>">
                                                <?php foreach ($status_options as $id => $label): ?>
                                                    <option value="<?= $id; ?>" <?= ($id == $lead['status_id']) ? 'selected' : ''; ?>>
                                                        <?= htmlspecialchars($label); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                        <td class="orario-creazione"><?= strftime("%d %b %Y • %H:%M", strtotime($lead['created_at'])); ?></td>
                                        <td class="lead-messaggio"><?= decryptData($lead['message'], $lead['iv'], $encryption_key) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" style="text-align: center; padding: 40px; color: var(--secondary-color);">
                                        <i class="fas fa-search" style="font-size: 48px; margin-bottom: 15px; opacity: 0.3;"></i><br>
                                        <?php if (!empty($filter_name) || !empty($filter_status) || !empty($filter_month) || !empty($filter_year)): ?>
                                            Nessun lead corrisponde ai filtri selezionati.<br>
                                            <button onclick="clearAllFilters()" style="margin-top: 10px; background: var(--link-color-active-bg); color: white; border: none; padding: 8px 15px; border-radius: 5px; cursor: pointer;">
                                                Rimuovi tutti i filtri
                                            </button>
                                        <?php else: ?>
                                            Nessun lead disponibile.
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($total_pages > 1): ?>
                    <div class="pagination-container">
                        <div class="pagination-info">
                            <span>
                                Stai visualizzando i lead da <?= $offset + 1 ?> - <?= min($offset + $leads_per_page, $total_leads) ?> 
                                di <?= $total_leads ?>
                            </span>
                        </div>
                        <div class="pagination">
                            <?php foreach ($pagination_links as $link): ?>
                                <?php if ($link['type'] === 'ellipsis'): ?>
                                    <span class="pagination-ellipsis"><?= $link['text'] ?></span>
                                <?php else: ?>
                                    <a href="<?= $link['url'] ?>" 
                                       class="pagination-link <?= isset($link['active']) && $link['active'] ? 'active' : '' ?> <?= $link['type'] ?>"
                                       <?= isset($link['active']) && $link['active'] ? 'aria-current="page"' : '' ?>>
                                        <?= $link['text'] ?>
                                    </a>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div id="leadOffCanvas" class="lead-offcanvas">
        <div class="offcanvas-header">
            <h3>Dettagli Lead</h3>
            <button class="close-offcanvas" onclick="closeOffCanvas()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="offcanvas-content" id="offcanvasContent">
        </div>
    </div>

    <div id="offcanvasOverlay" class="offcanvas-overlay" onclick="closeOffCanvas()"></div>

    <div id="notification-container"></div>
    <script src="assets/js/dashboard.js"></script>
    <script src="assets/js/leads.js"></script>
    
    <script>
        // Auto-submit del form quando si cambia un filtro
        document.addEventListener('DOMContentLoaded', function() {
            const filterSection = document.getElementById('filter-section');
            const toggleButton = document.getElementById('toggle-filters');
            const filterCount = document.getElementById('filter-count');
            const filterInputs = document.querySelectorAll('#filter-form input, #filter-form select');
            let timeout;
            
            // Gestione toggle filtri
            if (toggleButton && filterSection) {
                // Controlla se ci sono filtri attivi all'avvio
                const hasActiveFilters = <?= (!empty($filter_name) || !empty($filter_status) || !empty($filter_month) || !empty($filter_year)) ? 'true' : 'false' ?>;
                
                if (hasActiveFilters) {
                    filterSection.style.display = 'block';
                    toggleButton.classList.add('active');
                    toggleButton.innerHTML = '<i class="fa-solid fa-sliders"></i> Nascondi Filtri <span class="filter-count" id="filter-count"></span>';
                    updateFilterCount();
                }
                
                toggleButton.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    if (filterSection.style.display === 'none' || !filterSection.style.display) {
                        // Mostra filtri
                        filterSection.style.display = 'block';
                        filterSection.style.opacity = '0';
                        filterSection.style.transform = 'translateY(-10px)';
                        
                        setTimeout(() => {
                            filterSection.style.transition = 'all 0.3s ease';
                            filterSection.style.opacity = '1';
                            filterSection.style.transform = 'translateY(0)';
                        }, 10);
                        
                        toggleButton.classList.add('active');
                        toggleButton.innerHTML = '<i class="fa-solid fa-sliders"></i> Nascondi Filtri <span class="filter-count" id="filter-count"></span>';
                        updateFilterCount();
                    } else {
                        // Nascondi filtri
                        filterSection.style.transition = 'all 0.3s ease';
                        filterSection.style.opacity = '0';
                        filterSection.style.transform = 'translateY(-10px)';
                        
                        setTimeout(() => {
                            filterSection.style.display = 'none';
                        }, 300);
                        
                        toggleButton.classList.remove('active');
                        toggleButton.innerHTML = '<i class="fa-solid fa-sliders"></i> Filtri <span class="filter-count" id="filter-count"></span>';
                        updateFilterCount();
                    }
                });
            }
            
            // Funzione per aggiornare il conteggio filtri
            function updateFilterCount() {
                const activeFilters = document.querySelectorAll('.filter-tag').length;
                const countElement = document.getElementById('filter-count');
                
                if (countElement) {
                    if (activeFilters > 0) {
                        countElement.textContent = activeFilters;
                        countElement.style.display = 'inline-block';
                    } else {
                        countElement.style.display = 'none';
                    }
                }
            }
            
            // Auto-submit per input filtri
            filterInputs.forEach(input => {
                if (input && input.type === 'text') {
                    input.addEventListener('input', function() {
                        clearTimeout(timeout);
                        timeout = setTimeout(() => {
                            document.getElementById('filter-form').submit();
                        }, 800);
                    });
                } else if (input) {
                    input.addEventListener('change', function() {
                        document.getElementById('filter-form').submit();
                    });
                }
            });
            
            // Pulsante per pulire tutti i filtri
            const clearBtn = document.getElementById('clear-filters');
            if (clearBtn) {
                clearBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    clearAllFilters();
                });
            }
            
            // Aggiorna URL per il download CSV con i filtri attuali
            const downloadBtn = document.getElementById('download-csv');
            if (downloadBtn) {
                downloadBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    // Costruisci URL con i filtri attuali
                    const params = new URLSearchParams(window.location.search);
                    const csvUrl = 'includes/functions/csv-export.php?' + params.toString();
                    
                    // Apri il download
                    window.location.href = csvUrl;
                });
            }
            
            // Inizializza il conteggio filtri
            updateFilterCount();
        });
        
        function clearAllFilters() {
            window.location.href = 'leads.php';
        }
    </script>
</body>