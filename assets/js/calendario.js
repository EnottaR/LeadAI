// Inizializza FullCalendar
// DA FINIRE / NON UTILIZZATO
document.addEventListener("DOMContentLoaded", function () {
    var calendarEl = document.getElementById("calendar");
    var calendar = new FullCalendar.Calendar(calendarEl, {
      locale: "it",
      initialView: "dayGridMonth",
      headerToolbar: {
        left: "prev,next today",
        center: "title",
        right: "dayGridMonth,timeGridWeek,timeGridDay",
      },
      buttonText: {
        today: "Data odierna",
        month: "Mese",
        week: "Settimana",
        day: "Giorno",
      },
      titleFormat: {
        year: "numeric",
        month: "long",
      },
      events: [
        {
          title: "Evento 1",
          start: "2025-01-10",
        },
        {
          title: "Evento 2",
          start: "2025-01-15",
          end: "2025-01-17",
        },
      ],
      editable: true,
      selectable: true,
      dateClick: function (info) {
        alert("Hai cliccato sulla data: " + info.dateStr);
      },
    });
  
    calendar.render();
  
    setTimeout(() => {
      let buttons = document.querySelectorAll(".fc-button");
  
      buttons.forEach((button) => {
        let tooltipText = "";
  
        if (button.classList.contains("fc-dayGridMonth-button")) {
          tooltipText = "Vista Mensile";
        } else if (button.classList.contains("fc-timeGridWeek-button")) {
          tooltipText = "Vista Settimanale";
        } else if (button.classList.contains("fc-timeGridDay-button")) {
          tooltipText = "Vista Giornaliera";
        } else if (button.classList.contains("fc-today-button")) {
          tooltipText = "Vai alla data odierna";
        } else if (button.classList.contains("fc-prev-button")) {
          tooltipText = "Precedente";
        } else if (button.classList.contains("fc-next-button")) {
          tooltipText = "Successivo";
        }
  
        button.removeAttribute("title");
  
        if (tooltipText !== "") {
          let tooltip = document.createElement("span");
          tooltip.classList.add("calendar-tooltip");
          tooltip.innerText = tooltipText;
  
          button.classList.add("calendar-button");
          button.appendChild(tooltip);
  
          button.addEventListener("mouseover", function () {
            tooltip.style.opacity = "1";
            tooltip.style.visibility = "visible";
          });
  
          button.addEventListener("mouseout", function () {
            tooltip.style.opacity = "0";
            tooltip.style.visibility = "hidden";
          });
        }
      });
    }, 500);
  });
  document.addEventListener("DOMContentLoaded", function () {
    var calendarEl = document.getElementById("calendar");
    var modal = document.getElementById("event-modal");
    var modalHeaderTitle = document.querySelector("#event-modal-header h3");
    var eventDateDisplay = document.getElementById("event-date-display");
    var eventDateDisplay = document.getElementById("event-date-display");
    var modalOverlay = document.createElement("div");
    modalOverlay.id = "modal-overlay";
    document.body.appendChild(modalOverlay);
  
    var saveEventBtn = document.getElementById("save-event");
    var closeEventBtn = document.getElementById("close-event");
    var deleteEventBtn = document.createElement("button");
  
    var eventTitleInput = document.getElementById("event-title");
    var eventStartTimeInput = document.getElementById("event-start-time");
    var eventEndTimeInput = document.getElementById("event-end-time");
    var eventNotesInput = document.getElementById("event-notes");
  
    var selectedDate = null;
    var selectedEvent = null;
  
    var calendar = new FullCalendar.Calendar(calendarEl, {
      locale: "it",
      initialView: "dayGridMonth",
      headerToolbar: {
        left: "prev,next today",
        center: "title",
        right: "dayGridMonth,timeGridWeek,timeGridDay",
      },
      buttonText: {
        today: "Data odierna",
        month: "Mese",
        week: "Settimana",
        day: "Giorno",
      },
      titleFormat: {
        year: "numeric",
        month: "long",
      },
      editable: true,
      selectable: true,
  
      dateClick: function (info) {
        selectedDate = new Date(info.dateStr);
        selectedEvent = null;
  
        modal.style.top = `${info.jsEvent.clientY}px`;
        modal.style.left = `${info.jsEvent.clientX}px`;
  
        eventTitleInput.value = "";
        eventStartTimeInput.value = "";
        eventEndTimeInput.value = "";
        eventNotesInput.value = "";
  
        deleteEventBtn.style.display = "none";
  
        eventDateDisplay.textContent = formatDate(selectedDate);
        modalHeaderTitle.textContent = "Aggiungi nuovo evento";
  
        modal.style.display = "block";
        if (window.innerWidth <= 768) {
          modalOverlay.classList.add("active");
        }
        setTimeout(() => modal.classList.add("show"), 10);
  
        makeModalDraggable(modal);
      },
  
      eventClick: function (info) {
        selectedEvent = info.event;
  
        modal.style.top = `${info.jsEvent.clientY}px`;
        modal.style.left = `${info.jsEvent.clientX}px`;
  
        let eventTitle = selectedEvent.title || "";
        let eventStartTime = selectedEvent.extendedProps.startTime || "";
        let eventEndTime = selectedEvent.extendedProps.endTime || "";
        let eventNotes = selectedEvent.extendedProps.notes || "";
  
        eventTitleInput.value = eventTitle;
        eventStartTimeInput.value = eventStartTime;
        eventEndTimeInput.value = eventEndTime;
        eventNotesInput.value = eventNotes;
  
        selectedDate = new Date(selectedEvent.start);
        eventDateDisplay.textContent = formatDate(selectedDate);
        modalHeaderTitle.textContent = "Modifica evento";
  
        deleteEventBtn.style.display = "block";
        deleteEventBtn.innerText = "Elimina evento";
        deleteEventBtn.style.background = "#dc3545";
        deleteEventBtn.style.color = "white";
        deleteEventBtn.style.border = "none";
        deleteEventBtn.style.padding = "8px";
        deleteEventBtn.style.marginTop = "10px";
        deleteEventBtn.style.borderRadius = "5px";
        deleteEventBtn.style.cursor = "pointer";
  
        if (!document.getElementById("delete-event")) {
          deleteEventBtn.setAttribute("id", "delete-event");
          modal.querySelector(".event-modal-content").appendChild(deleteEventBtn);
        }
  
        modal.style.display = "block";
        setTimeout(() => modal.classList.add("show"), 10);
      },
    });
  
    calendar.render();
  
    eventStartTimeInput.addEventListener("input", function () {
      if (
        eventEndTimeInput.value &&
        eventStartTimeInput.value > eventEndTimeInput.value
      ) {
        eventEndTimeInput.value = eventStartTimeInput.value;
      }
    });
  
    eventEndTimeInput.addEventListener("input", function () {
      if (eventEndTimeInput.value < eventStartTimeInput.value) {
        showNotification(
          "⚠️ L'orario di fine non può essere minore di quello di inizio!",
          "error"
        );
        eventEndTimeInput.value = eventStartTimeInput.value;
      }
    });
  
    saveEventBtn.addEventListener("click", function () {
      let eventTitle = eventTitleInput.value.trim();
      let eventStartTime = eventStartTimeInput.value.trim();
      let eventEndTime = eventEndTimeInput.value.trim();
      let eventNotes = eventNotesInput.value.trim();
  
      if (eventTitle === "") {
        showNotification("⚠️ Inserisci un titolo per l'evento!", "error");
        return;
      }
  
      let formattedStartTime = "";
      let formattedEndTime = "";
      let eventStart = new Date(selectedDate);
  
      if (eventStartTime) {
        let [hours, minutes] = eventStartTime.split(":").map(Number);
        formattedStartTime = `${hours.toString().padStart(2, "0")}:${minutes
          .toString()
          .padStart(2, "0")}`;
        eventStart.setHours(hours, minutes, 0);
      }
  
      if (eventEndTime) {
        let [endHours, endMinutes] = eventEndTime.split(":").map(Number);
        formattedEndTime = `${endHours.toString().padStart(2, "0")}:${endMinutes
          .toString()
          .padStart(2, "0")}`;
      }
  
      if (selectedEvent) {
        selectedEvent.setProp("title", eventTitle);
        selectedEvent.setExtendedProp("startTime", formattedStartTime);
        selectedEvent.setExtendedProp("endTime", formattedEndTime);
        selectedEvent.setExtendedProp("notes", eventNotes);
        selectedEvent.setStart(eventStart);
      } else {
        calendar.addEvent({
          title: eventTitle,
          start: eventStart,
          extendedProps: {
            startTime: formattedStartTime,
            endTime: formattedEndTime,
            notes: eventNotes,
          },
        });
      }
  
      modal.classList.remove("show");
      setTimeout(() => (modal.style.display = "none"), 300);
    });
  
    deleteEventBtn.addEventListener("click", function () {
      if (selectedEvent) {
        selectedEvent.remove();
        modal.classList.remove("show");
        setTimeout(() => (modal.style.display = "none"), 300);
      }
    });
  
    closeEventBtn.addEventListener("click", function () {
      modal.classList.remove("show");
      modalOverlay.classList.remove("active");
      setTimeout(() => (modal.style.display = "none"), 300);
    });
  
    function formatDate(date) {
      const giorniSettimana = [
        "Domenica",
        "Lunedì",
        "Martedì",
        "Mercoledì",
        "Giovedì",
        "Venerdì",
        "Sabato",
      ];
      const mesi = [
        "Gennaio",
        "Febbraio",
        "Marzo",
        "Aprile",
        "Maggio",
        "Giugno",
        "Luglio",
        "Agosto",
        "Settembre",
        "Ottobre",
        "Novembre",
        "Dicembre",
      ];
      return `${
        giorniSettimana[date.getDay()]
      }, ${date.getDate()} ${mesi[date.getMonth()]}`;
    }
  
    function makeModalDraggable(modal) {
      let offsetX = 0,
        offsetY = 0,
        isDragging = false;
  
      modal.style.cursor = "grab";
  
      modal.onmousedown = function (e) {
        if (
          e.target.tagName === "INPUT" ||
          e.target.tagName === "TEXTAREA" ||
          e.target.tagName === "BUTTON"
        ) {
          return;
        }
  
        isDragging = true;
        offsetX = e.clientX - modal.offsetLeft;
        offsetY = e.clientY - modal.offsetTop;
  
        modal.style.cursor = "grabbing";
  
        document.onmousemove = function (e) {
          if (isDragging) {
            let newX = e.clientX - offsetX;
            let newY = e.clientY - offsetY;
  
            let maxX = window.innerWidth - modal.clientWidth;
            let maxY = window.innerHeight - modal.clientHeight;
  
            newX = Math.max(0, Math.min(newX, maxX));
            newY = Math.max(0, Math.min(newY, maxY));
  
            modal.style.left = `${newX}px`;
            modal.style.top = `${newY}px`;
          }
        };
  
        document.onmouseup = function () {
          isDragging = false;
          modal.style.cursor = "grab";
          document.onmousemove = null;
          document.onmouseup = null;
        };
      };
    }
    function showNotification(message, type) {
      let notification = document.getElementById("notification-container");
      notification.textContent = message;
      notification.className = "show";
  
      if (type === "success") {
        notification.classList.add("success");
      } else {
        notification.classList.remove("success");
      }
  
      setTimeout(() => {
        notification.classList.remove("show");
      }, 3000);
    }
  });
  