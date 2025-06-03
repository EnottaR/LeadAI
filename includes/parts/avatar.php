<?php
session_start();

// Mostra avatar con iniziali e colore dinamico
$nome = $_SESSION['name'] ?? 'Utente';
$cognome = $_SESSION['surname'] ?? 'Anonimo';
$iniziali = strtoupper(substr($nome, 0, 1) . substr($cognome, 0, 1));
$hash = md5($_SESSION['username'] ?? 'default');
$color = '#' . substr($hash, 0, 6);

echo '<div class="user-avatar" style="background-color: ' . htmlspecialchars($color) . ';">' . htmlspecialchars($iniziali) . '</div>';

?>