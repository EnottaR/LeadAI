<div id="modal-overlay"></div>
<div id="event-modal" class="event-modal">
    <div id="event-modal-header" class="event-modal-header">
        <h3 style="margin-bottom: 5px">Aggiungi nuovo evento</h3>
        <p id="event-date-display" class="event-date" style="margin-top: 0; margin-bottom: 10px"></p>
        <span id="close-event" class="close-modal">&times;</span>
    </div>
    <div class="event-modal-content">
        <input type="text" id="event-title" placeholder="Inserisci il titolo dell'evento" />
        <div class="time-container">
            <div class="time-column">
                <label for="event-start-time">Inizio</label>
                <input type="time" id="event-start-time" />
            </div>
            <div class="time-column">
                <label for="event-end-time">Fine</label>
                <input type="time" id="event-end-time" />
            </div>
        </div>

        <textarea id="event-notes" placeholder="Aggiungi descrizione"></textarea>

        <button id="save-event">Salva evento</button>
    </div>
</div>
