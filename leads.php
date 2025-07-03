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

$leads_per_page = 10;
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($current_page - 1) * $leads_per_page;

$filtro_nome = isset($_GET['filtro_nome']) ? trim($_GET['filtro_nome']) : '';
$filtro_status = isset($_GET['filtro_status']) ? intval($_GET['filtro_status']) : '';
$filtro_mese = isset($_GET['filtro_mese']) ? intval($_GET['filtro_mese']) : '';
$filtro_anno = isset($_GET['filtro_anno']) ? intval($_GET['filtro_anno']) : '';

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

$where_conditions = ["l.clients_id = ?"];
$params = [$client_id];
$param_types = "i";

if (!empty($filtro_nome)) {
    $where_conditions[] = "(p.name LIKE ? OR p.surname LIKE ? OR CONCAT(p.name, ' ', p.surname) LIKE ?)";
    $search_term = "%{$filtro_nome}%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $param_types .= "sss";
}

if (!empty($filtro_status)) {
    $where_conditions[] = "l.status_id = ?";
    $params[] = $filtro_status;
    $param_types .= "i";
}

if (!empty($filtro_mese) && !empty($filtro_anno)) {
    $where_conditions[] = "YEAR(l.created_at) = ? AND MONTH(l.created_at) = ?";
    $params[] = $filtro_anno;
    $params[] = $filtro_mese;
    $param_types .= "ii";
} elseif (!empty($filtro_anno)) {
    $where_conditions[] = "YEAR(l.created_at) = ?";
    $params[] = $filtro_anno;
    $param_types .= "i";
} elseif (!empty($filtro_mese)) {
    $where_conditions[] = "MONTH(l.created_at) = ?";
    $params[] = $filtro_mese;
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

$quality_options = [
    1 => 'Spam',
    2 => 'Non in target', 
    3 => 'In target ma bassa qualità',
    4 => 'Lead Buono',
    5 => 'Lead Ottimo'
];

$main_query = "
    SELECT l.id, p.name, p.surname, p.email, l.phone, l.message, l.status_id, l.quality_rating, l.created_at, 
           l.ip, l.lead_source_url, l.lead_type
    FROM leads l
    JOIN personas p ON l.personas_id = p.id
    WHERE {$where_clause}
    ORDER BY l.created_at DESC
    LIMIT ? OFFSET ?
";

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

function generatePaginationLinks($current_page, $total_pages, $max_links = 5)
{
    $links = [];

    $filter_params = [];
    if (!empty($_GET['filtro_nome'])) $filter_params['filtro_nome'] = $_GET['filtro_nome'];
    if (!empty($_GET['filtro_status'])) $filter_params['filtro_status'] = $_GET['filtro_status'];
    if (!empty($_GET['filtro_mese'])) $filter_params['filtro_mese'] = $_GET['filtro_mese'];
    if (!empty($_GET['filtro_anno'])) $filter_params['filtro_anno'] = $_GET['filtro_anno'];

    $base_url = http_build_query($filter_params);
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

$years = [];
$current_year = date('Y');
for ($i = $current_year; $i >= ($current_year - 3); $i--) {
    $years[] = $i;
}

$months = [
    1 => 'Gennaio',
    2 => 'Febbraio',
    3 => 'Marzo',
    4 => 'Aprile',
    5 => 'Maggio',
    6 => 'Giugno',
    7 => 'Luglio',
    8 => 'Agosto',
    9 => 'Settembre',
    10 => 'Ottobre',
    11 => 'Novembre',
    12 => 'Dicembre'
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
                        <button type="button" id="clear-filters" class="clear-filters-btn" onclick="clearAllFilters()">
                            <i class="fas fa-times"></i> Pulisci filtri
                        </button>
                    </div>

                    <form method="GET" action="leads.php" class="filter-form" id="filter-form">
                        <div class="filter-row">
                            <div class="filter-group">
                                <label for="filtro_nome">Nome o Cognome</label>
                                <input type="text"
                                    id="filtro_nome"
                                    name="filtro_nome"
                                    placeholder="Scrivi un nome oppure un cognome"
                                    value="<?= htmlspecialchars($filtro_nome) ?>"
                                    class="filter-input">
                            </div>

                            <div class="filter-group">
                                <label for="filtro_status">Status</label>
                                <select id="filtro_status" name="filtro_status" class="filter-select">
                                    <option value="">Tutti gli status</option>
                                    <?php foreach ($status_options as $id => $label): ?>
                                        <option value="<?= $id ?>" <?= ($id == $filtro_status) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($label) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="filter-group">
                                <label for="filtro_mese">Mese</label>
                                <select id="filtro_mese" name="filtro_mese" class="filter-select">
                                    <option value="">Tutti i mesi</option>
                                    <?php foreach ($months as $num => $name): ?>
                                        <option value="<?= $num ?>" <?= ($num == $filtro_mese) ? 'selected' : '' ?>>
                                            <?= $name ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="filter-group">
                                <label for="filtro_anno">Anno</label>
                                <select id="filtro_anno" name="filtro_anno" class="filter-select">
                                    <option value="">Tutti gli anni</option>
                                    <?php foreach ($years as $year): ?>
                                        <option value="<?= $year ?>" <?= ($year == $filtro_anno) ? 'selected' : '' ?>>
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

                    <?php if (!empty($filtro_nome) || !empty($filtro_status) || !empty($filtro_mese) || !empty($filtro_anno)): ?>
                        <div class="active-filters">
                            <?php if (!empty($filtro_nome)): ?>
                                <span class="filter-tag">
                                    Nome: "<?= htmlspecialchars($filtro_nome) ?>"
                                    <?php
                                    $remove_params = $_GET;
                                    unset($remove_params['filtro_nome']);
                                    $remove_url = '?' . http_build_query(array_filter($remove_params));
                                    if ($remove_url === '?') $remove_url = 'leads.php';
                                    ?>
                                    <a href="<?= $remove_url ?>" class="remove-filter">×</a>
                                </span>
                            <?php endif; ?>
                            <?php if (!empty($filtro_status)): ?>
                                <span class="filter-tag">
                                    Status: <?= htmlspecialchars($status_options[$filtro_status]) ?>
                                    <?php
                                    $remove_params = $_GET;
                                    unset($remove_params['filtro_status']);
                                    $remove_url = '?' . http_build_query(array_filter($remove_params));
                                    if ($remove_url === '?') $remove_url = 'leads.php';
                                    ?>
                                    <a href="<?= $remove_url ?>" class="remove-filter">×</a>
                                </span>
                            <?php endif; ?>
                            <?php if (!empty($filtro_mese)): ?>
                                <span class="filter-tag">
                                    Mese: <?= $months[$filtro_mese] ?>
                                    <?php
                                    $remove_params = $_GET;
                                    unset($remove_params['filtro_mese']);
                                    $remove_url = '?' . http_build_query(array_filter($remove_params));
                                    if ($remove_url === '?') $remove_url = 'leads.php';
                                    ?>
                                    <a href="<?= $remove_url ?>" class="remove-filter">×</a>
                                </span>
                            <?php endif; ?>
                            <?php if (!empty($filtro_anno)): ?>
                                <span class="filter-tag">
                                    Anno: <?= $filtro_anno ?>
                                    <?php
                                    $remove_params = $_GET;
                                    unset($remove_params['filtro_anno']);
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
        <th>Qualità lead</th>
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
                                                <i class="fa-solid fa-ellipsis"></i>
                                            </button>
                                        </td>
                                        <td><?= htmlspecialchars($lead['name']); ?></td>
                                        <td><?= htmlspecialchars($lead['surname']); ?></td>
                                        <td><?= htmlspecialchars($lead['email']); ?></td>
                                        <td><?= decryptData($lead['phone'], null, $encryption_key) ?></td>
                                        <td>
                                            <select class="status-select <?= 'status-' . $lead['status_id']; ?>" data-lead-id="<?= $lead['id']; ?>">
                                                <?php foreach ($status_options as $id => $label): ?>
                                                    <option value="<?= $id; ?>" <?= ($id == $lead['status_id']) ? 'selected' : ''; ?>>
                                                        <?= htmlspecialchars($label); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
										<td>
    <select class="quality-select" data-lead-id="<?= $lead['id']; ?>" style="font-size: 12px; padding: 2px 4px;">
        <option value="">Seleziona</option>
        <option value="1" <?= ($lead['quality_rating'] == 1) ? 'selected' : ''; ?>>(1) Spam</option>
        <option value="2" <?= ($lead['quality_rating'] == 2) ? 'selected' : ''; ?>>(2) Non in target</option>
        <option value="3" <?= ($lead['quality_rating'] == 3) ? 'selected' : ''; ?>>(3) In target ma bassa qualità</option>
        <option value="4" <?= ($lead['quality_rating'] == 4) ? 'selected' : ''; ?>>(4) Lead Buono</option>
        <option value="5" <?= ($lead['quality_rating'] == 5) ? 'selected' : ''; ?>>(5) Lead Ottimo</option>
    </select>
</td>
                                        <td class="orario-creazione"><?= strftime("%d %b %Y • %H:%M", strtotime($lead['created_at'])); ?></td>
                                        <td class="lead-messaggio"><?= decryptData($lead['message'], null, $encryption_key) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9" style="text-align: center; padding: 40px; color: var(--secondary-color);">
                                        <i class="fas fa-search" style="font-size: 48px; margin-bottom: 15px; opacity: 0.3;"></i><br>
                                        <?php if (!empty($filtro_nome) || !empty($filtro_status) || !empty($filtro_mese) || !empty($filtro_anno)): ?>
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
    <script src="assets/js/leads-new.js"></script>
	
	    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const filterSection = document.getElementById('filter-section');
            const toggleButton = document.getElementById('toggle-filters');
            const filterCount = document.getElementById('filter-count');
            const filterInputs = document.querySelectorAll('#filter-form input, #filter-form select');
            let timeout;

            if (toggleButton && filterSection) {
                const hasActiveFilters = <?= (!empty($filtro_nome) || !empty($filtro_status) || !empty($filtro_mese) || !empty($filtro_anno)) ? 'true' : 'false' ?>;

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

            const clearBtn = document.getElementById('pulisci-filtri');
            if (clearBtn) {
                clearBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    clearAllFilters();
                });
            }

            const downloadBtn = document.getElementById('download-csv');
            if (downloadBtn) {
                downloadBtn.addEventListener('click', function(e) {
                    e.preventDefault();

                    const params = new URLSearchParams(window.location.search);
                    const csvUrl = 'includes/functions/csv-export.php?' + params.toString();

                    window.location.href = csvUrl;
                });
            }

            updateFilterCount();
        });

        function clearAllFilters() {
            window.location.href = 'leads.php';
        }
		
		document.querySelectorAll('.quality-select').forEach(select => {
    select.addEventListener('change', function() {
        fetch('includes/update-quality.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                lead_id: this.dataset.leadId,
                quality_rating: this.value
            })
        });
    });
});
    </script>
</body>