document.addEventListener('DOMContentLoaded', function() {
    
    // Elementi del DOM
    const filterForm = document.getElementById('filter-form');
    const filterNameInput = document.getElementById('filtro_nome');
    const filterStatusSelect = document.getElementById('filtro_status');
    const filterMonthSelect = document.getElementById('filtro_mese');
    const filterYearSelect = document.getElementById('filtro_anno');
    const clearFiltersBtn = document.getElementById('pulisci-filtri');
    const resultsCount = document.querySelector('.results-count');
    const leadsTable = document.querySelector('.leads-table');
    
    let filterTimeout;
    
    // Auto-submit per il campo nome (con debounce)
    if (filterNameInput) {
        filterNameInput.addEventListener('input', function() {
            clearTimeout(filterTimeout);
            showFilterLoading();
            
            filterTimeout = setTimeout(() => {
                submitFilterForm();
            }, 800); // Aspetta 800ms dopo l'ultimo carattere
        });
        
        // Mostra suggerimenti mentre digita
        filterNameInput.addEventListener('keyup', function() {
            if (this.value.length > 0) {
                this.classList.add('has-input');
            } else {
                this.classList.remove('has-input');
            }
        });
    }
    
    // Auto-submit per i select
    [filterStatusSelect, filterMonthSelect, filterYearSelect].forEach(select => {
        if (select) {
            select.addEventListener('change', function() {
                showFilterLoading();
                submitFilterForm();
            });
        }
    });
    
    // Pulsante clear filters
    if (clearFiltersBtn) {
        clearFiltersBtn.addEventListener('click', function(e) {
            e.preventDefault();
            clearAllFilters();
        });
    }
    
    // Funzione per mostrare loading durante il filtraggio
    function showFilterLoading() {
        if (leadsTable) {
            leadsTable.classList.add('loading');
        }
        
        if (resultsCount) {
            resultsCount.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Filtraggio in corso...';
        }
    }
    
    // Funzione per submit del form
    function submitFilterForm() {
        if (filterForm) {
            // Aggiungi un indicatore che il filtro è stato applicato via JavaScript
            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'ajax_filter';
            hiddenInput.value = '1';
            filterForm.appendChild(hiddenInput);
            
            filterForm.submit();
        }
    }
    
    // Funzione per pulire tutti i filtri
    function clearAllFilters() {
        showFilterLoading();
        window.location.href = window.location.pathname;
    }
    
    // Gestione URL con history API (per navigazione con frecce del browser)
    function updateURL() {
        const formData = new FormData(filterForm);
        const params = new URLSearchParams();
        
        for (let [key, value] of formData.entries()) {
            if (value && value.trim() !== '' && key !== 'ajax_filter') {
                params.append(key, value);
            }
        }
        
        const newURL = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
        history.pushState({}, '', newURL);
    }
    
    // Evidenzia i filtri attivi
    function highlightActiveFilters() {
        const inputs = filterForm.querySelectorAll('input, select');
        
        inputs.forEach(input => {
            if (input.value && input.value.trim() !== '') {
                input.classList.add('filter-active');
            } else {
                input.classList.remove('filter-active');
            }
        });
    }
    
    // Evidenzia i filtri attivi
    function highlightActiveFilters() {
        if (!filterForm) return;
        
        const inputs = filterForm.querySelectorAll('input, select');
        
        inputs.forEach(input => {
            if (input.value && input.value.trim() !== '') {
                input.classList.add('filter-active');
            } else {
                input.classList.remove('filter-active');
            }
        });
    }
    
    // Inizializza evidenziazione filtri attivi
    highlightActiveFilters();
    
    // Gestione rimozione singoli filtri dai tag
    document.querySelectorAll('.remove-filter').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            showFilterLoading();
            window.location.href = this.href;
        });
    });
    
    // Shortcut da tastiera per pulire filtri (Ctrl+R o Cmd+R)
    document.addEventListener('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 'r') {
            // Previeni il reload della pagina
            e.preventDefault();
            clearAllFilters();
        }
        
        // ESC per pulire il campo nome se è in focus
        if (e.key === 'Escape' && document.activeElement === filterNameInput) {
            filterNameInput.value = '';
            filterNameInput.blur();
            submitFilterForm();
        }
    });
    
    // Salva i filtri nel localStorage per mantenere lo stato
    function saveFiltersToStorage() {
        const filters = {
            name: filterNameInput?.value || '',
            status: filterStatusSelect?.value || '',
            month: filterMonthSelect?.value || '',
            year: filterYearSelect?.value || ''
        };
        
        localStorage.setItem('leads_filters', JSON.stringify(filters));
    }
    
    // Carica i filtri dal localStorage (opzionale)
    function loadFiltersFromStorage() {
        const savedFilters = localStorage.getItem('leads_filters');
        if (savedFilters) {
            try {
                const filters = JSON.parse(savedFilters);
                
                // Applica solo se la pagina non ha già dei filtri
                const hasExistingFilters = window.location.search.includes('filter_');
                
                if (!hasExistingFilters) {
                    if (filterNameInput && filters.name) filterNameInput.value = filters.name;
                    if (filterStatusSelect && filters.status) filterStatusSelect.value = filters.status;
                    if (filterMonthSelect && filters.month) filterMonthSelect.value = filters.month;
                    if (filterYearSelect && filters.year) filterYearSelect.value = filters.year;
                }
            } catch (e) {
                // Ignora errori di parsing
                localStorage.removeItem('leads_filters');
            }
        }
    }
    
    // Salva filtri quando cambiano
    [filterNameInput, filterStatusSelect, filterMonthSelect, filterYearSelect].forEach(element => {
        if (element) {
            element.addEventListener('change', saveFiltersToStorage);
        }
    });
    
    // Animazione per i tag dei filtri attivi
    document.querySelectorAll('.filter-tag').forEach(tag => {
        tag.style.animationDelay = Math.random() * 0.3 + 's';
        tag.classList.add('filter-tag-animate');
    });
    
    // Mostra suggerimenti contestuali
    function showFilterTips() {
        const tips = [
            "💡 Suggerimento: Usa Ctrl+R (o Cmd+R su Mac) per pulire rapidamente tutti i filtri",
            "🔍 Suggerimento: Il filtro per nome cerca sia nel nome che nel cognome",
            "📅 Suggerimento: Puoi combinare più filtri per una ricerca più precisa",
            "⚡ Suggerimento: I filtri si applicano automaticamente mentre digiti"
        ];
        
        const randomTip = tips[Math.floor(Math.random() * tips.length)];
        
        // Mostra il suggerimento se non ci sono risultati
        const noResultsMessage = document.querySelector('.leads-table tbody tr td[colspan="8"]');
        if (noResultsMessage && !noResultsMessage.textContent.includes('Nessun lead disponibile')) {
            const tipElement = document.createElement('div');
            tipElement.className = 'filter-tip';
            tipElement.innerHTML = `<small style="color: var(--secondary-color); font-style: italic;">${randomTip}</small>`;
            noResultsMessage.appendChild(tipElement);
        }
    }
    
    // Inizializza suggerimenti
    setTimeout(showFilterTips, 1000);
    
    // Debug: log filtri attivi (solo in modalità sviluppo)
    if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
        const activeFilters = {
            name: filterNameInput?.value,
            status: filterStatusSelect?.value,
            month: filterMonthSelect?.value,
            year: filterYearSelect?.value
        };
        
        const activeCount = Object.values(activeFilters).filter(v => v && v.trim() !== '').length;
        if (activeCount > 0) {
            console.log('🔍 Filtri attivi:', activeFilters);
        }
    }
});

// Stili CSS aggiuntivi tramite JavaScript
const additionalStyles = `
<style>
.filter-active {
    border-color: var(--link-color-active-bg) !important;
    box-shadow: 0 0 0 2px rgba(31, 28, 46, 0.1) !important;
}

.filter-tag-animate {
    animation: filterTagSlideIn 0.3s ease-out forwards;
}

@keyframes filterTagSlideIn {
    from {
        opacity: 0;
        transform: translateY(-10px) scale(0.9);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

.has-input {
    background-image: linear-gradient(45deg, transparent 40%, rgba(31, 28, 46, 0.05) 40%, rgba(31, 28, 46, 0.05) 60%, transparent 60%);
}

.filter-tip {
    margin-top: 15px;
    padding-top: 10px;
    border-top: 1px solid var(--message-box-border);
}

/* Effetto loading migliorato */
.leads-table.loading {
    position: relative;
}

.leads-table.loading::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(255, 255, 255, 0.8);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 10;
}

.dark .leads-table.loading::after {
    background: rgba(31, 40, 55, 0.8);
}

/* Hover effects per i filtri */
.filter-input:hover,
.filter-select:hover {
    border-color: var(--link-color-active-bg);
    transition: border-color 0.2s ease;
}

/* Indicatore conteggio caratteri per il campo nome */
.filter-input[type="text"]:focus + .char-counter {
    display: block;
}

.char-counter {
    display: none;
    font-size: 12px;
    color: var(--secondary-color);
    text-align: right;
    margin-top: 2px;
}
</style>
`;

// Aggiungi gli stili aggiuntivi al documento
document.head.insertAdjacentHTML('beforeend', additionalStyles);