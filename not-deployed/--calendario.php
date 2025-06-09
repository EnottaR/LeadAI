<?php
require 'includes/auth-checker.php';	
$pageTitle = "Calendario | LeadAI";
include 'includes/parts/header.php';
?>

<body>
    <?php
    $current_page = basename($_SERVER['PHP_SELF']);
    ?>
    <div class="app-container">
        <?php include 'includes/parts/head.php'; ?>
        <div class="app-content">
            <?php include 'includes/parts/sidebar.php'; ?>
            <div class="projects-section" style="overflow: auto;">
                <div class="projects-section-header">
                    <p>Il tuo calendario</p>
                </div>
                <div class="sezione-calendario">
                    <div id="calendar"></div>
                </div>
				<?php include 'includes/parts/modale-calendario.php'; ?>
            </div>
        </div>
    </div>
	<div id="notification-container"></div>
    <script src="assets/js/dashboard.js"></script>
    <script src="assets/js/calendario.js"></script>
</body>