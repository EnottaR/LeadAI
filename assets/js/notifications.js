document.addEventListener("DOMContentLoaded", function () {
  const notificaBtn = document.getElementById("notifica-btn");
  const notificaBox = document.getElementById("notifica-box");
  const notificaContenuto = document.getElementById("notifica-contenuto");
  const chiudiNotifica = document.getElementById("chiudi-notifica");
  const notificationDot = document.getElementById("new-lead-notification");

  let newLeadsExist = false;

  if (!notificaBtn || !notificaBox || !notificationDot) return;

  function checkNewLeads() {
    fetch("includes/new-lead-alert.php")
      .then((response) => response.json())
      .then((data) => {
        if (data.status === "success" && data.new_leads > 0) {
          newLeadsExist = true;

          // Verifico se ci sono nuovi lead dall'ultima lettura
          const lastReadTime = localStorage.getItem("lastReadLeadTime");
          const lastLeadTime = new Date(data.leads[0].created_at).getTime();

          if (!lastReadTime || lastLeadTime > parseInt(lastReadTime)) {
            notificationDot.style.display = "block";
          } else {
            // Mostra sempre il pallino se ci sono nuovi lead
            notificationDot.style.display = "block";
          }

          let leadHtml = "";
          data.leads.slice(0, 5).forEach((lead) => {
            leadHtml += `<div class="notifica-lead">
                            🟢 Nuovo lead da <span>${lead.name} ${lead.surname}</span><br>
                            <small>${lead.created_at}</small>
                        </div>`;
          });

          notificaContenuto.innerHTML = leadHtml;
        } else {
          newLeadsExist = false;
          notificationDot.style.display = "none";
          notificaContenuto.innerHTML = `
        <div style="text-align: center; padding: 15px 10px;">
            <img src="assets/img/notification-box.svg" alt="Nessuna notifica" style="width: 90px; height: 80px; margin-bottom: 10px;">
            <p style="font-weight: bold; margin-bottom: 5px;">Nessuna nuova notifica</p>
            <p style="color: #777; font-size: 13px;">Non hai nuove notifiche al momento.</p>
        </div>
    `;
        }
      })
      .catch((error) =>
        console.error("Errore nel controllo nuovi lead:", error)
      );
  }

  notificaBtn.addEventListener("click", function () {
    notificaBox.classList.toggle("show");
    checkNewLeads();

    if (newLeadsExist) {
      localStorage.setItem("lastReadLeadTime", Date.now());
      setTimeout(() => {
        notificationDot.style.display = "none";
      }, 3000);
    }
  });

  document.addEventListener("click", function (event) {
    if (
      !notificaBox.contains(event.target) &&
      !notificaBtn.contains(event.target)
    ) {
      notificaBox.classList.remove("show");
    }
  });

  chiudiNotifica.addEventListener("click", function () {
    notificaBox.classList.remove("show");
  });

  // Check necessario: controllo ogni 30 secondi se ci sono nuovi lead non registrati
  setInterval(checkNewLeads, 30000);
  checkNewLeads();
});