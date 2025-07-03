<?php
require 'includes/auth-checker.php';
$pageTitle = "Dashboard | LeadAI";
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
            <div class="projects-section">
				<?php include 'includes/parts/progetto.php'; ?>
            </div>
			<?php include 'includes/parts/box-messaggi.php'; ?>
        </div>
    </div>
    <script src="assets/js/dashboard.js"></script>
</body>