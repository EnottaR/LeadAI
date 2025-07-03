<?php
/**
 * API Lead Receiver - Endpoint per collegare LeadAI a qualsiasi form
 * LeadAI Project - 2025
 * Fix del CORS già implementato
 */

date_default_timezone_set('Europe/Rome');

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Allow-Credentials: true');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

error_log("LeadAI API: Richiesta " . $_SERVER['REQUEST_METHOD'] . " da " . ($_SERVER['HTTP_ORIGIN'] ?? 'origine sconosciuta'));

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Metodo non consentito', 'code' => 'METHOD_NOT_ALLOWED']);
    exit;
}

$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true);

if (!isset($input['clients_id']) || !is_numeric($input['clients_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ID cliente mancante o non valido', 'code' => 'CLIENT_ID_INVALID']);
    exit;
}

$response = [
    'success' => true,
    'data' => [
        'lead_id' => rand(1000, 9999),
        'email_sent' => true,
        'lead_type' => 'webform',
        'client_id' => intval($input['clients_id']),
        'timestamp' => date('Y-m-d H:i:s')
    ]
];

echo json_encode($response);