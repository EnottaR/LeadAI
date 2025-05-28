<?php
require 'includes/auth-checker.php';
$pageTitle = "Impostazioni | LeadAI";
include 'includes/parts/header.php';
session_start();
require 'includes/db.php';

if (!isset($_SESSION['loggedin']) || !isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$client_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT name, url FROM websites WHERE clients_id = ?");
$stmt->bind_param("i", $client_id);
$stmt->execute();
$stmt->bind_result($website_name, $website_url);
$stmt->fetch();
$stmt->close();

$stmt = $conn->prepare("SELECT company FROM clients WHERE id = ?");
$stmt->bind_param("i", $client_id);
$stmt->execute();
$stmt->bind_result($company_name);
$stmt->fetch();
$stmt->close();

?>

<body>
    <?php
    $current_page = basename($_SERVER['PHP_SELF']);
    ?>
    <div class="app-container">
        <?php include 'includes/parts/head.php'; ?>
        <div class="app-content">
            <?php include 'includes/parts/sidebar.php'; ?>
            <div class="projects-section" style="overflow: auto">
                <div class="projects-section-header">
                    <p>Impostazioni</p>
                </div>
				<?php include 'includes/parts/impostazioni.php'; ?>
            </div>
        </div>
    </div>
    <div id="notification-container"></div>
    <script src="assets/js/dashboard.js"></script>
    <script src="assets/js/settings.js"></script>
</body>
<?php include 'includes/parts/spinner.php'; ?>