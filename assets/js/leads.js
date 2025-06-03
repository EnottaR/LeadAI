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
            <div class="lead-detail-section">
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