<?php
require 'includes/auth-checker.php';
$pageTitle = "Integrazione | LeadAI";
include 'includes/parts/header.php';
require 'includes/db.php';

if (!isset($_SESSION['loggedin']) || !isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$client_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT name, surname, company FROM clients WHERE id = ?");
$stmt->bind_param("i", $client_id);
$stmt->execute();
$stmt->bind_result($client_name, $client_surname, $client_company);
$stmt->fetch();
$stmt->close();

$stmt = $conn->prepare("SELECT name, url FROM websites WHERE clients_id = ?");
$stmt->bind_param("i", $client_id);
$stmt->execute();
$stmt->bind_result($website_name, $website_url);
$stmt->fetch();
$stmt->close();

$base_url = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']);
$form_action_url = $base_url . '/add_lead.php';
$api_endpoint_url = $base_url . '/includes/functions/api_lead_receiver.php';

$display_name = $client_company ?: ($client_name . ' ' . $client_surname);
?>

<body>
    <?php $current_page = basename($_SERVER['PHP_SELF']); ?>
    <div class="app-container">
        <?php include 'includes/parts/head.php'; ?>
        <div class="app-content">
            <?php include 'includes/parts/sidebar.php'; ?>
            <div class="projects-section" style="overflow: auto;">
                <div class="projects-section-header">
                    <p>Integrare LeadAI nel tuo form</p>
                </div>
                <?php include 'includes/parts/integrazioni.php'; ?>
			</div>
			<div id="notification-container"></div>
            <script src="assets/js/dashboard.js"></script>
            <script src="assets/js/settings.js"></script>
			<script src="assets/js/script.js"></script>
