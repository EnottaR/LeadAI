document.addEventListener("DOMContentLoaded", function () {

    document.querySelectorAll(".status-select").forEach((select) => {
        updateStatusColor(select);

        select.addEventListener("change", function () {
            const leadId = this.getAttribute("data-lead-id");
            const newStatus = this.value;

            fetch("includes/update-status.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ lead_id: leadId, status_id: newStatus }),
            })
            .then((response) => response.json())
            .then((data) => {
                showNotification(data.message, data.status === "success" ? "success" : "error");
                if (data.status === "success") {
                    updateStatusColor(select);
                }
            })
            .catch(error => {
                console.error("Errore:", error);
                showNotification("⚠️ Errore durante l'aggiornamento dello status.", "error");
            });
        });
    });

    function updateStatusColor(selectElement) {
        selectElement.classList.remove(...selectElement.classList);
        selectElement.classList.add("status-select", "status-" + selectElement.value);
    }

    const downloadCsvBtn = document.getElementById("download-csv");
    if (downloadCsvBtn) {
        downloadCsvBtn.addEventListener("click", function () {
            window.location.href = "includes/functions/csv-export.php";
        });
    }

});

// Info del browser, lato client
// meno solido e più propenso a crollare per motivo x, come ad esempio una versione Edge MOLTO vecchia.
// Meglio salvare le info nel db, valutare se creare campi appositi, se necessario.
function detectBrowser() {
    const userAgent = navigator.userAgent;
    let browser = 'Sconosciuto';
    
    if (userAgent.indexOf('Chrome') > -1) {
        browser = 'Chrome';
    } else if (userAgent.indexOf('Firefox') > -1) {
        browser = 'Firefox';
    } else if (userAgent.indexOf('Safari') > -1) {
        browser = 'Safari';
    } else if (userAgent.indexOf('Edge') > -1) {
        browser = 'Edge';
    } else if (userAgent.indexOf('Opera') > -1) {
        browser = 'Opera';
    }
    
    return browser;
}

function detectOS() {
    const userAgent = navigator.userAgent;
    let os = 'Sconosciuto';
    
    if (userAgent.indexOf('Windows') > -1) {
        os = 'Windows';
    } else if (userAgent.indexOf('Mac') > -1) {
        os = 'macOS';
    } else if (userAgent.indexOf('Linux') > -1) {
        os = 'Linux';
    } else if (userAgent.indexOf('Android') > -1) {
        os = 'Android';
    } else if (userAgent.indexOf('iOS') > -1) {
        os = 'iOS';
    }
    
    return os;
}

// Funzione per determinare l'origine del lead
function getLeadTypeFromUrl(url) {
    if (!url || url === 'Non disponibile') {
        return 'Semplice/Organico';
    }
    
    // Check sull'URL se contiene /gad dopo il .com
    if (url.match(/:\/\/[^\/]+\/gad/i)) {
        return 'Google ADS';
    }
    
    return 'Semplice/Organico';
}

function openOffCanvas(leadData) {
    // SICUREZZA
    fetch('includes/functions/decrypt-lead-data.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ 
            lead_id: leadData.id
        })
    })
    .then(response => response.json())
    .then(decryptedData => {
        if (decryptedData.error) {
            showNotification("⚠️ " + decryptedData.error, "error");
            return;
        }
        
        const browserInfo = detectBrowser() + ' su ' + detectOS();
        
        const leadSource = leadData.lead_source_url || (window.location.origin + '/leadAI/contact_form.php');
        
        const leadType = leadData.lead_type || getLeadTypeFromUrl(leadSource);
        
        let content = `
            <div class="lead-detail-section" style="overflow-wrap: break-word;">
                <p><strong>Questi i dati inseriti nel modulo presente alla pagina:</strong> ${leadSource}</p>
                <p><strong>Da utente con indirizzo IP:</strong> ${leadData.ip || 'Non disponibile'}</p>
                <p><strong>Browser/Sistema operativo:</strong> ${browserInfo}</p>
            </div>
            
            <div class="lead-detail-section">
                <h4>Dati Inseriti</h4>
                <div class="data-grid">
                    <div class="data-item">
                        <span class="data-label">Fonte del lead (lead_source):</span>
                        <span class="data-value">${leadSource}</span>
                    </div>
                    <div class="data-item">
                        <span class="data-label">Tipologia Lead:</span>
                        <span class="data-value" style="font-weight: bold; color: ${leadType === 'Google ADS' ? '#4285f4' : '#34a853'};">
                            ${leadType === 'Google ADS' ? 'Google ADS' : 'Semplice/Organico'}
                        </span>
                    </div>
                    <div class="data-item">
                        <span class="data-label">Nome (first_name):</span>
                        <span class="data-value">${leadData.name}</span>
                    </div>
                    <div class="data-item">
                        <span class="data-label">Cognome (last_name):</span>
                        <span class="data-value">${leadData.surname}</span>
                    </div>
                    <div class="data-item">
                        <span class="data-label">Email:</span>
                        <span class="data-value">${leadData.email}</span>
                    </div>
                    <div class="data-item">
                        <span class="data-label">Telefono (phone):</span>
                        <span class="data-value">${decryptedData.phone}</span>
                    </div>
                    <div class="data-item">
                        <span class="data-label">Note (description):</span>
                        <span class="data-value">${decryptedData.message}</span>
                    </div>
                    <div class="data-item">
                        <span class="data-label">privacy:</span>
                        <span class="data-value">on</span>
                    </div>
                </div>
            </div>`;

        const offcanvasTitle = document.querySelector('.offcanvas-header h3');
        if (offcanvasTitle) {
            const typeIcon = leadType === 'Google ADS' ? '' : '';
            offcanvasTitle.innerHTML = `Dettagli Lead <span style="font-size: 14px; color: ${leadType === 'Google ADS' ? '#4285f4' : '#34a853'}; border: 2px solid ${leadType === 'Google ADS' ? '#4285f4' : '#34a853'}; margin-left: 10px; padding: 7px 10px; border-radius: 20px;">${typeIcon} ${leadType}</span>`;
        }

        document.getElementById('offcanvasContent').innerHTML = content;
        document.getElementById('leadOffCanvas').classList.add('active');
        document.getElementById('offcanvasOverlay').classList.add('active');
    })
    .catch(error => {
        console.error('Errore nel caricamento dei dati:', error);
        showNotification("⚠️ Errore nel caricamento dei dettagli del lead.", "error");
    });
}

function closeOffCanvas() {
    document.getElementById('leadOffCanvas').classList.remove('active');
    document.getElementById('offcanvasOverlay').classList.remove('active');
    
    const offcanvasTitle = document.querySelector('.offcanvas-header h3');
    if (offcanvasTitle) {
        offcanvasTitle.innerHTML = 'Dettagli Lead';
    }
}

// Eliminazione lead
let selectedLeads = new Set();
let isMultiSelectMode = false;

document.addEventListener("DOMContentLoaded", function () {
    initializeDeleteFunctionality();
    
    document.querySelectorAll(".status-select").forEach((select) => {
    });
});

function initializeDeleteFunctionality() {
    addBulkDeleteButtons();
    
    addSelectionCheckboxes();
    
    addSingleDeleteButtons();
    
    setupDeleteEventListeners();
}

function addBulkDeleteButtons() {
    const headerActions = document.querySelector('.header-actions');
    if (!headerActions) return;
    
    const multiSelectBtn = document.createElement('button');
    multiSelectBtn.id = 'toggle-multi-select';
    multiSelectBtn.className = 'btn-secondary';
    multiSelectBtn.innerHTML = '<i class="fas fa-check-square"></i> Seleziona';
    multiSelectBtn.style.display = 'none';
    
    const deleteSelectedBtn = document.createElement('button');
    deleteSelectedBtn.id = 'delete-selected';
    deleteSelectedBtn.className = 'btn-primary';
    deleteSelectedBtn.innerHTML = '<i class="fas fa-trash"></i> Elimina selezionati';
    deleteSelectedBtn.style.display = 'none';
    
    const cancelSelectBtn = document.createElement('button');
    cancelSelectBtn.id = 'cancel-select';
    cancelSelectBtn.className = 'btn-secondary';
    cancelSelectBtn.innerHTML = '<i class="fas fa-times"></i> Annulla';
    cancelSelectBtn.style.display = 'none';
    
    headerActions.appendChild(multiSelectBtn);
    headerActions.appendChild(deleteSelectedBtn);
    headerActions.appendChild(cancelSelectBtn);
    
    // Mostra il pulsante solo se ci sono lead
    const leadsTable = document.querySelector('.leads-table tbody');
    if (leadsTable && leadsTable.children.length > 0) {
        const firstRow = leadsTable.children[0];
        if (!firstRow.querySelector('td[colspan]')) {
            multiSelectBtn.style.display = 'inline-block';
        }
    }
}

function addSelectionCheckboxes() {
    const tableRows = document.querySelectorAll('.leads-table tbody tr');
    
    tableRows.forEach(row => {
        if (row.querySelector('td[colspan]')) return;
        
        const firstCell = row.querySelector('td');
        if (!firstCell) return;
        
        const checkbox = document.createElement('input');
        checkbox.type = 'checkbox';
        checkbox.className = 'lead-select-checkbox';
        checkbox.style.display = 'none';
        checkbox.style.marginBottom = '10px';
        
        const detailsBtn = row.querySelector('.details-btn');
        if (detailsBtn) {
            const leadData = detailsBtn.getAttribute('onclick');
            const leadIdMatch = leadData.match(/id["']:\s*(\d+)/);
            if (leadIdMatch) {
                checkbox.setAttribute('data-lead-id', leadIdMatch[1]);
            }
        }
        
        firstCell.appendChild(checkbox);
    });
}

function addSingleDeleteButtons() {
    const detailsButtons = document.querySelectorAll('.details-btn');
    
    detailsButtons.forEach(btn => {
        const cell = btn.parentElement;
        
        const deleteBtn = document.createElement('button');
        deleteBtn.className = 'details-btn';
        deleteBtn.style.backgroundColor = '#dc3545';
        deleteBtn.style.marginTop = '15px';
        deleteBtn.innerHTML = '<i class="fas fa-trash"></i>';
        deleteBtn.title = 'Elimina lead';
        
        const onclickAttr = btn.getAttribute('onclick');
        if (onclickAttr) {
            const leadData = onclickAttr.match(/openOffCanvas\((.*)\)/);
            if (leadData) {
                deleteBtn.setAttribute('data-lead-info', leadData[1]);
            }
        }
        
        cell.appendChild(deleteBtn);
    });
}

function setupDeleteEventListeners() {
    document.addEventListener('click', function(e) {
        if (e.target.closest('#toggle-multi-select')) {
            toggleMultiSelectMode();
        }
        
        if (e.target.closest('#cancel-select')) {
            exitMultiSelectMode();
        }
        
        if (e.target.closest('#delete-selected')) {
            deleteSelectedLeads();
        }
        
        if (e.target.closest('.details-btn[style*="background-color: rgb(220, 53, 69)"]')) {
            const btn = e.target.closest('.details-btn[style*="background-color: rgb(220, 53, 69)"]');
            deleteSingleLead(btn);
        }
    });
    
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('lead-select-checkbox')) {
            updateSelectedLeads();
        }
    });
}

function toggleMultiSelectMode() {
    isMultiSelectMode = !isMultiSelectMode;
    
    const multiSelectBtn = document.getElementById('toggle-multi-select');
    const deleteSelectedBtn = document.getElementById('delete-selected');
    const cancelSelectBtn = document.getElementById('cancel-select');
    const checkboxes = document.querySelectorAll('.lead-select-checkbox');
    
    if (isMultiSelectMode) {
        multiSelectBtn.style.display = 'none';
        deleteSelectedBtn.style.display = 'inline-block';
        cancelSelectBtn.style.display = 'inline-block';
        
        checkboxes.forEach(cb => {
            cb.style.display = 'inline-block';
        });
        
        showNotification('<i class="fas fa-info-circle"></i> Seleziona i lead da eliminare', 'info');
    } else {
        exitMultiSelectMode();
    }
}

function exitMultiSelectMode() {
    isMultiSelectMode = false;
    selectedLeads.clear();
    
    const multiSelectBtn = document.getElementById('toggle-multi-select');
    const deleteSelectedBtn = document.getElementById('delete-selected');
    const cancelSelectBtn = document.getElementById('cancel-select');
    const checkboxes = document.querySelectorAll('.lead-select-checkbox');
    
    multiSelectBtn.style.display = 'inline-block';
    deleteSelectedBtn.style.display = 'none';
    cancelSelectBtn.style.display = 'none';
    
    checkboxes.forEach(cb => {
        cb.style.display = 'none';
        cb.checked = false;
    });
    
    document.querySelector('.leads-table').style.backgroundColor = '';
    
    updateDeleteSelectedButton();
}

function updateSelectedLeads() {
    selectedLeads.clear();
    
    const checkboxes = document.querySelectorAll('.lead-select-checkbox:checked');
    checkboxes.forEach(cb => {
        const leadId = cb.getAttribute('data-lead-id');
        if (leadId) {
            selectedLeads.add(leadId);
        }
    });
    
    updateDeleteSelectedButton();
}

function updateDeleteSelectedButton() {
    const deleteBtn = document.getElementById('delete-selected');
    if (!deleteBtn) return;
    
    if (selectedLeads.size > 0) {
        deleteBtn.innerHTML = `<i class="fas fa-trash"></i> Elimina ${selectedLeads.size} lead${selectedLeads.size > 1 ? 's' : ''}`;
        deleteBtn.disabled = false;
        deleteBtn.style.opacity = '1';
    } else {
        deleteBtn.innerHTML = '<i class="fas fa-trash"></i> Elimina selezionati';
        deleteBtn.disabled = true;
        deleteBtn.style.opacity = '0.5';
    }
}

function deleteSingleLead(button) {
    const leadInfoAttr = button.getAttribute('data-lead-info');
    if (!leadInfoAttr) {
        showNotification('⚠️ Errore: impossibile identificare il lead', 'error');
        return;
    }
    
    try {
        const leadData = Function('"use strict"; return (' + leadInfoAttr + ')')();
        
        if (!leadData.id) {
            showNotification('⚠️ Errore: ID lead non trovato', 'error');
            return;
        }
        
        const leadName = `${leadData.name} ${leadData.surname}`;
        
        if (confirm(`Sei sicuro di voler eliminare il lead di ${leadName}?\n\nQuesta azione non può essere annullata.`)) {
            performDelete([leadData.id], 'single');
        }
    } catch (error) {
        console.error('Errore parsing dati lead:', error);
        showNotification('⚠️ Errore nel parsing dei dati del lead', 'error');
    }
}

function deleteSelectedLeads() {
    if (selectedLeads.size === 0) {
        showNotification('⚠️ Seleziona almeno un lead da eliminare', 'error');
        return;
    }
    
    const count = selectedLeads.size;
    const message = count === 1 
        ? 'Sei sicuro di voler eliminare il lead selezionato?' 
        : `Sei sicuro di voler eliminare i ${count} lead selezionati?`;
    
    if (confirm(`${message}\n\nQuesta azione non può essere annullata.`)) {
        performDelete(Array.from(selectedLeads), 'multiple');
    }
}

function performDelete(leadIds, type) {
    const loadingMessage = type === 'single' ? 'Eliminazione in corso...' : `Eliminazione di ${leadIds.length} lead in corso...`;
    showNotification(`<i class="fas fa-spinner fa-spin"></i> ${loadingMessage}`, 'info');
    
    const payload = type === 'single' 
        ? { lead_id: leadIds[0] }
        : { lead_ids: leadIds };
    
    fetch('includes/functions/delete-lead.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(payload)
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            showNotification(data.message, 'success');
            
            leadIds.forEach(leadId => {
                removeLeadFromTable(leadId);
            });
            
            if (isMultiSelectMode) {
                exitMultiSelectMode();
            }
            
            updateDashboardCounters();
            
            checkEmptyTable();
            
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Errore eliminazione:', error);
        showNotification('⚠️ Errore di connessione durante l\'eliminazione', 'error');
    });
}

function removeLeadFromTable(leadId) {
    const checkboxes = document.querySelectorAll('.lead-select-checkbox');
    checkboxes.forEach(cb => {
        if (cb.getAttribute('data-lead-id') === leadId.toString()) {
            const row = cb.closest('tr');
            if (row) {
                row.style.transition = 'all 0.3s ease';
                row.style.opacity = '0';
                row.style.transform = 'translateX(-20px)';
                
                setTimeout(() => {
                    row.remove();
                }, 300);
            }
        }
    });
}

function updateDashboardCounters() {
    if (window.location.pathname.includes('dashboard')) {
        location.reload();
    }
}

function checkEmptyTable() {
    setTimeout(() => {
        const tableBody = document.querySelector('.leads-table tbody');
        if (tableBody && tableBody.children.length === 0) {
            const emptyRow = document.createElement('tr');
            emptyRow.innerHTML = `
                <td colspan="8" style="text-align: center; padding: 40px; color: var(--secondary-color);">
                    <i class="fas fa-inbox" style="font-size: 48px; margin-bottom: 15px; opacity: 0.3;"></i><br>
                    Nessun lead disponibile.
                </td>
            `;
            tableBody.appendChild(emptyRow);
            
            const multiSelectBtn = document.getElementById('toggle-multi-select');
            if (multiSelectBtn) {
                multiSelectBtn.style.display = 'none';
            }
        }
    }, 350);
}

function showNotification(message, type = 'success') {
    let notificationContainer = document.getElementById('notification-container');
    
    if (!notificationContainer) {
        notificationContainer = document.createElement('div');
        notificationContainer.id = 'notification-container';
        notificationContainer.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            max-width: 400px;
        `;
        document.body.appendChild(notificationContainer);
    }
    
    const notification = document.createElement('div');
    notification.style.cssText = `
        background: ${type === 'success' ? '#28a745' : type === 'error' ? '#dc3545' : '#17a2b8'};
        color: white;
        padding: 12px 16px;
        border-radius: 6px;
        margin-bottom: 10px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        transform: translateX(100%);
        transition: transform 0.3s ease;
        font-size: 14px;
        font-weight: 500;
    `;
    notification.innerHTML = message;
    
    notificationContainer.appendChild(notification);
    
    setTimeout(() => {
        notification.style.transform = 'translateX(0)';
    }, 100);
    
    setTimeout(() => {
        notification.style.transform = 'translateX(100%)';
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }, 5000);
}

window.deleteSingleLead = deleteSingleLead;
window.deleteSelectedLeads = deleteSelectedLeads;
window.toggleMultiSelectMode = toggleMultiSelectMode;