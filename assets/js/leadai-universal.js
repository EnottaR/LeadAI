/**
 * LeadAI Universal Integration Script
 * Versione: 0.05
 * 
 * Questo script può essere integrato in qualsiasi sito web per inviare
 * automaticamente i lead a LeadAI senza modificare i form esistenti.
 * WIP, NON UTILIZZARE
 */

class LeadAIUniversal {
    constructor(config = {}) {
        this.config = {
            clientId: config.clientId || null,
            endpoint: config.endpoint || 'https://mg-adv.com/leadAI/add_lead.php',
            apiEndpoint: config.apiEndpoint || 'https://mg-adv.com/leadAI/includes/functions/api_lead_receiver.php',
            returnUrl: config.returnUrl || '',
            debug: config.debug || false,
            autoDetect: config.autoDetect !== false, // Default true
            ...config
        };
        
        // Mappatura estesa dei campi - può essere personalizzata
        this.fieldMapping = {
            // NOME - tutte le possibili varianti
            'nome': 'name',
            'name': 'name',
            'first_name': 'name',
            'firstname': 'name',
            'customer_name': 'name',
            'client_name': 'name',
            'user_name': 'name',
            'nome_cliente': 'name',
            'your_name': 'name',
            'full_name': 'name',
            'contact_name': 'name',
            
            // COGNOME
            'cognome': 'surname',
            'surname': 'surname',
            'last_name': 'surname',
            'lastname': 'surname',
            'customer_surname': 'surname',
            'client_surname': 'surname',
            'family_name': 'surname',
            'cognome_cliente': 'surname',
            
            // EMAIL
            'email': 'email',
            'mail': 'email',
            'e_mail': 'email',
            'e-mail': 'email',
            'contact_email': 'email',
            'customer_email': 'email',
            'user_email': 'email',
            'your_email': 'email',
            'email_address': 'email',
            'indirizzo_email': 'email',
            
            // TELEFONO
            'phone': 'phone',
            'telefono': 'phone',
            'tel': 'phone',
            'telephone': 'phone',
            'phone_number': 'phone',
            'mobile': 'phone',
            'cellulare': 'phone',
            'contact_phone': 'phone',
            'customer_phone': 'phone',
            'your_phone': 'phone',
            'numero_telefono': 'phone',
            
            // MESSAGGIO
            'message': 'message',
            'messaggio': 'message',
            'msg': 'message',
            'description': 'message',
            'note': 'message',
            'notes': 'message',
            'comments': 'message',
            'customer_message': 'message',
            'inquiry': 'message',
            'details': 'message',
            'request': 'message',
            'richiesta': 'message',
            'testo': 'message',
            'content': 'message',
			'description': 'message',
            'body': 'message'
        };
        
        this.log('LeadAI Universal Integration inizializzato', this.config);
        
        if (this.config.autoDetect) {
            this.autoDetectForms();
        }
    }
    
    /**
     * Logging di debug
     */
    log(...args) {
        if (this.config.debug) {
            console.log('[LeadAI]', ...args);
        }
    }
    
    /**
     * Rilevamento automatico dei form nella pagina
     */
    autoDetectForms() {
        document.addEventListener('DOMContentLoaded', () => {
            const forms = document.querySelectorAll('form');
            this.log(`Rilevati ${forms.length} form nella pagina`);
            
            forms.forEach((form, index) => {
                // Controlla se il form ha campi che potrebbero essere lead
                if (this.isLeadForm(form)) {
                    this.log(`Form ${index + 1} identificato come potenziale form lead`);
                    this.attachToForm(form);
                }
            });
        });
    }
    
    /**
     * Verifica se un form contiene campi tipici di un lead form
     */
    isLeadForm(form) {
        const inputs = form.querySelectorAll('input, textarea, select');
        const fieldNames = Array.from(inputs).map(input => 
            input.name.toLowerCase() || input.id.toLowerCase()
        );
        
        // Cerca almeno un campo email e uno di contatto
        const hasEmail = fieldNames.some(name => 
            Object.keys(this.fieldMapping).some(mapping => 
                mapping.includes('email') && name.includes(mapping)
            )
        );
        
        const hasContact = fieldNames.some(name => 
            Object.keys(this.fieldMapping).some(mapping => 
                (mapping.includes('name') || mapping.includes('phone') || mapping.includes('message')) 
                && name.includes(mapping)
            )
        );
        
        return hasEmail && hasContact;
    }
    
    /**
     * Collega LeadAI a un form specifico
     */
    attachToForm(formSelector) {
        const form = typeof formSelector === 'string' 
            ? document.querySelector(formSelector) 
            : formSelector;
            
        if (!form) {
            this.log('Form non trovato:', formSelector);
            return;
        }
        
        this.log('Collegamento a form:', form);
        
        // Aggiungi campo client_id nascosto se non presente
        if (!form.querySelector('[name="clients_id"]') && this.config.clientId) {
            const clientIdInput = document.createElement('input');
            clientIdInput.type = 'hidden';
            clientIdInput.name = 'clients_id';
            clientIdInput.value = this.config.clientId;
            form.appendChild(clientIdInput);
        }
        
        // Intercetta l'invio del form
        form.addEventListener('submit', (e) => {
            e.preventDefault();
            this.handleFormSubmission(form, e);
        });
    }
    
    /**
     * Gestisce l'invio del form
     */
    async handleFormSubmission(form, originalEvent) {
        this.log('Intercettato invio form');
        
        try {
            // Raccogli i dati del form
            const formData = new FormData(form);
            const rawData = Object.fromEntries(formData.entries());
            
            this.log('Dati form originali:', rawData);
            
            // Mappa i dati per LeadAI
            const mappedData = this.mapFormData(rawData);
            
            this.log('Dati mappati per LeadAI:', mappedData);
            
            // Invia a LeadAI
            const result = await this.sendToLeadAI(mappedData);
            
            if (result.success) {
                this.log('Lead inviato con successo a LeadAI');
                
                // Se c'è un URL di ritorno, vai lì
                if (this.config.returnUrl) {
                    window.location.href = this.config.returnUrl;
                    return;
                }
                
                // Altrimenti esegui l'invio originale del form
                this.executeOriginalSubmit(form);
                
            } else {
                this.log('Errore invio a LeadAI:', result);
                
                // In caso di errore, procedi comunque con l'invio originale
                this.executeOriginalSubmit(form);
            }
            
        } catch (error) {
            this.log('Errore durante l\'elaborazione:', error);
            
            // In caso di errore, procedi con l'invio originale
            this.executeOriginalSubmit(form);
        }
    }
    
    /**
     * Esegue l'invio originale del form
     */
    executeOriginalSubmit(form) {
        // Rimuovi temporaneamente il listener per evitare loop
        const newForm = form.cloneNode(true);
        form.parentNode.replaceChild(newForm, form);
        
        // Invia il form
        newForm.submit();
    }
    
    /**
     * Mappa i dati del form nel formato LeadAI
     */
    mapFormData(rawData) {
        const mappedData = {
            clients_id: this.config.clientId
        };
        
        // Aggiungi URL di ritorno se configurato
        if (this.config.returnUrl) {
            mappedData.retURL = this.config.returnUrl;
        }
        
        // Mappa i campi del form
        for (const [fieldName, fieldValue] of Object.entries(rawData)) {
            const cleanFieldName = fieldName.toLowerCase().trim();
            
            if (this.fieldMapping[cleanFieldName]) {
                const leadaiField = this.fieldMapping[cleanFieldName];
                mappedData[leadaiField] = fieldValue;
            }
        }
        
        // Gestione intelligente del nome completo
        if (!mappedData.name && !mappedData.surname) {
            // Cerca campi che potrebbero contenere nome completo
            for (const [fieldName, fieldValue] of Object.entries(rawData)) {
                if (fieldName.toLowerCase().includes('name') && fieldValue.includes(' ')) {
                    const nameParts = fieldValue.trim().split(' ');
                    mappedData.name = nameParts[0];
                    mappedData.surname = nameParts.slice(1).join(' ') || 'N/A';
                    break;
                }
            }
        }
        
        // Assicurati che i campi obbligatori esistano
        if (!mappedData.name) mappedData.name = '';
        if (!mappedData.surname) mappedData.surname = 'N/A';
        if (!mappedData.email) mappedData.email = '';
        if (!mappedData.phone) mappedData.phone = '';
        if (!mappedData.message) mappedData.message = '';
        
        return mappedData;
    }
    
    /**
     * Invia i dati a LeadAI
     */
    async sendToLeadAI(data) {
        try {
            const response = await fetch(this.config.endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams(data)
            });
            
            const result = {
                success: response.ok,
                status: response.status,
                statusText: response.statusText
            };
            
            if (response.ok) {
                result.data = await response.text();
            } else {
                result.error = `HTTP ${response.status}: ${response.statusText}`;
            }
            
            return result;
            
        } catch (error) {
            return {
                success: false,
                error: error.message
            };
        }
    }
    
    /**
     * Metodo per invio manuale (senza form)
     */
    async sendLead(leadData) {
        const mappedData = {
            clients_id: this.config.clientId,
            name: leadData.name || leadData.nome || '',
            surname: leadData.surname || leadData.cognome || 'N/A',
            email: leadData.email || '',
            phone: leadData.phone || leadData.telefono || '',
            message: leadData.message || leadData.messaggio || '',
            ...leadData
        };
        
        return await this.sendToLeadAI(mappedData);
    }
    
    /**
     * Integrazione con Contact Form 7 (WordPress)
     */
    initContactForm7Integration() {
        document.addEventListener('wpcf7mailsent', (event) => {
            this.log('Contact Form 7 mail sent evento rilevato');
            
            const form = event.target;
            const formData = new FormData(form);
            const rawData = Object.fromEntries(formData.entries());
            
            const mappedData = this.mapFormData(rawData);
            this.sendToLeadAI(mappedData);
        });
    }
    
    /**
     * Integrazione con Gravity Forms (WordPress)
     */
    initGravityFormsIntegration() {
        jQuery(document).on('gform_confirmation_loaded', (event, formId) => {
            this.log('Gravity Form submission rilevata, Form ID:', formId);
            
            // Per Gravity Forms dobbiamo intercettare i dati prima dell'invio
            // Questo richiede configurazione aggiuntiva lato WordPress
        });
    }
    
    /**
     * Integrazione con Elementor Forms
     */
    initElementorIntegration() {
        jQuery(document).on('submit_success', '.elementor-form', (event) => {
            this.log('Elementor form submission rilevata');
            
            const form = event.target;
            const formData = new FormData(form);
            const rawData = Object.fromEntries(formData.entries());
            
            const mappedData = this.mapFormData(rawData);
            this.sendToLeadAI(mappedData);
        });
    }
    
    /**
     * Metodo helper per aggiungere campi nascosti a form esistenti
     */
    injectHiddenFields(formSelector) {
        const forms = document.querySelectorAll(formSelector);
        
        forms.forEach(form => {
            if (!form.querySelector('[name="clients_id"]')) {
                const clientIdInput = document.createElement('input');
                clientIdInput.type = 'hidden';
                clientIdInput.name = 'clients_id';
                clientIdInput.value = this.config.clientId;
                form.appendChild(clientIdInput);
                
                this.log('Campo clients_id aggiunto al form:', form);
            }
        });
    }
    
    /**
     * Test di connettività con LeadAI
     */
    async testConnection() {
        try {
            const testData = {
                clients_id: this.config.clientId,
                name: 'Test',
                surname: 'LeadAI',
                email: 'test@leadai.test',
                phone: '+39 123 456 7890',
                message: 'Messaggio di test connessione LeadAI'
            };
            
            const result = await this.sendToLeadAI(testData);
            
            this.log('Test connessione risultato:', result);
            
            return result;
            
        } catch (error) {
            this.log('Errore test connessione:', error);
            return { success: false, error: error.message };
        }
    }
}

// Inizializzazione automatica se LeadAI è configurato globalmente
if (typeof window.LeadAIConfig !== 'undefined') {
    window.LeadAI = new LeadAIUniversal(window.LeadAIConfig);
}

// Export per uso come modulo
if (typeof module !== 'undefined' && module.exports) {
    module.exports = LeadAIUniversal;
}

// AMD support
if (typeof define === 'function' && define.amd) {
    define([], function() {
        return LeadAIUniversal;
    });
}

/**
 * ESEMPI DI UTILIZZO:
 * 
 * 1. Configurazione base (in <head> del sito):
 * 
 * <script>
 * window.LeadAIConfig = {
 *     clientId: 7,
 *     endpoint: 'https://mg-adv.com/leadAI/add_lead.php',
 *     returnUrl: 'https://www.mgvision.com/grazie.html',
 *     debug: true
 * };
 * </script>
 * <script src="leadai-universal.js"></script>
 * 
 * 
 * 2. Integrazione manuale:
 * 
 * const leadai = new LeadAIUniversal({
 *     clientId: 7,
 *     endpoint: 'https://mg-adv.com/leadAI/add_lead.php'
 * });
 * 
 * // Collega a form specifico
 * leadai.attachToForm('#contact-form');
 * 
 * // Invio manuale
 * leadai.sendLead({
 *     name: 'Mario',
 *     surname: 'Rossi',
 *     email: 'mario@example.com',
 *     phone: '+39 123 456 7890',
 *     message: 'Ciao, vorrei informazioni'
 * });
 * 
 * 
 * 3. Per WordPress con Contact Form 7:
 * 
 * const leadai = new LeadAIUniversal({
 *     clientId: 7,
 *     autoDetect: false // Disabilita auto-rilevamento
 * });
 * 
 * leadai.initContactForm7Integration();
 * 
 * 
 * 4. Test connessione:
 * 
 * leadai.testConnection().then(result => {
 *     console.log('Test risultato:', result);
 * });
 */