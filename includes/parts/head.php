<?php
session_start();
?>
<div class="app-header">
    <div class="app-header-sinistra">
        <span id="menu-toggle" class="app-icon"></span>
        <p class="app-nome" style="margin-right: 5px;">LeadAI</p>
        <p style="font-size: 10px; color: var(--secondary-color); align-self: flex-end; margin: 0 15px 5px 0;">Ver. 0.9c  - Owlbear Build</p>
        <!-- <div class="search-wrapper">
            <input class="search-input" type="text" placeholder="Cerca">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor"
                stroke-linecap="round" stroke-linejoin="round" stroke-width="2" class="feather feather-search"
                viewBox="0 0 24 24">
                <defs></defs>
                <circle cx="11" cy="11" r="8"></circle>
                <path d="M21 21l-4.35-4.35"></path>
            </svg>
        </div> -->
    </div>
    <div class="app-header-destra">
		<button class="notifica-btn" id="notifica-btn">
    <span class="tooltip-text">Notifiche</span>
    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
        class="feather feather-bell">
        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9" />
        <path d="M13.73 21a2 2 0 0 1-3.46 0" />
    </svg>
    <span id="new-lead-notification" class="notification-dot" style="display: none;"></span>
</button>
<div id="notifica-box" class="notifica-box">
    <div class="notifica-header">
        <span>Notifiche</span>
        <button id="chiudi-notifica" class="chiudi-btn">&times;</button>
    </div>
    <div id="notifica-contenuto" class="notifica-contenuto">
        <p>Caricamento...</p>
    </div>
</div>
        <button class="switch-mode">
            <span class="tooltip-text">Passa alla modalità scura</span>
            <svg class="luna" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                stroke-width="2" width="24" height="24" viewBox="0 0 24 24">
                <defs></defs>
                <path d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"></path>
            </svg>
            <svg class="theme-icon sole hidden" fill="none" stroke="currentColor" stroke-linecap="round"
                stroke-linejoin="round" stroke-width="2" width="24" height="24" viewBox="0 0 24 24">
                <circle cx="12" cy="12" r="5"></circle>
                <line x1="12" y1="1" x2="12" y2="3"></line>
                <line x1="12" y1="21" x2="12" y2="23"></line>
                <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line>
                <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line>
                <line x1="1" y1="12" x2="3" y2="12"></line>
                <line x1="21" y1="12" x2="23" y2="12"></line>
                <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line>
                <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>
            </svg>
        </button>
        <!-- <button class="add-btn" title="Add New Project">
            <svg class="btn-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"
                class="feather feather-plus">
                <line x1="12" y1="5" x2="12" y2="19" />
                <line x1="5" y1="12" x2="19" y2="12" />
            </svg>
        </button> -->
        <button class="profilo-btn">
            <?php include 'includes/parts/avatar.php'; ?>
            <span>
                <?= htmlspecialchars($_SESSION['name'] ?? 'Ospite'); ?>
            </span>
        </button>
    </div>
    <button class="messages-btn">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
            class="feather feather-message-circle">
            <path
                d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z" />
        </svg>
    </button>
</div>
<script src="assets/js/notifications.js"></script>