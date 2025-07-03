document.addEventListener("DOMContentLoaded", function () {
  const notificaBtn = document.getElementById("notifica-btn");
  const notificaBox = document.getElementById("notifica-box");
  const notificaContenuto = document.getElementById("notifica-contenuto");
  const chiudiNotifica = document.getElementById("chiudi-notifica");
  const notificationDot = document.getElementById("new-lead-notification");

  let newLeadsExist = false;
  let checkInterval = null;
  let lastKnownLeadCount = 0;

  if (!notificaBtn || !notificaBox || !notificationDot) return;

  initNotificationSystem();

  function initNotificationSystem() {
    loadInitialState();
    
    startPeriodicCheck();
    
    handleVisibilityChange();
  }

  function loadInitialState() {
    checkNewLeads().then(() => {
      const hasUnreadNotifications = getUnreadNotificationState();
      updateNotificationDot(hasUnreadNotifications);
    });
  }

  function checkNewLeads() {
    return fetch("includes/new-lead-alert.php")
      .then((response) => response.json())
      .then((data) => {
        if (data.status === "success") {
          const currentLeadCount = data.new_leads;
          
          if (currentLeadCount > lastKnownLeadCount && lastKnownLeadCount > 0) {
            markAsUnread();
            showNewLeadAlert(data.leads[0]);
          }
          
          lastKnownLeadCount = currentLeadCount;
          newLeadsExist = currentLeadCount > 0;

          updateNotificationContent(data);
          
          const hasUnread = getUnreadNotificationState();
          updateNotificationDot(hasUnread && newLeadsExist);
        } else {
          newLeadsExist = false;
          updateNotificationDot(false);
          updateNotificationContent({ leads: [], new_leads: 0 });
        }
      })
      .catch((error) => {
        console.error("Errore nel controllo nuovi lead:", error);
      });
  }

  function updateNotificationContent(data) {
    if (data.new_leads > 0) {
      let leadHtml = "";
      data.leads.slice(0, 5).forEach((lead) => {
        leadHtml += `<div class="notifica-lead">
                        🟢 Nuovo lead da <span>${lead.name} ${lead.surname}</span><br>
                        <small>${lead.created_at}</small>
                    </div>`;
      });
      notificaContenuto.innerHTML = leadHtml;
    } else {
      notificaContenuto.innerHTML = `
        <div style="text-align: center; padding: 15px 10px;">
            <img src="assets/img/notification-box.svg" alt="Nessuna notifica" style="width: 90px; height: 80px; margin-bottom: 10px;">
            <p style="font-weight: bold; margin-bottom: 5px;">Nessuna nuova notifica</p>
            <p style="color: #777; font-size: 13px;">Non hai nuove notifiche al momento.</p>
        </div>`;
    }
  }

  function updateNotificationDot(show) {
    if (show) {
      notificationDot.style.display = "block";
      notificationDot.classList.add("pulse-animation");
      
      if (!document.title.includes("(•)")) {
        document.title = "(•) " + document.title;
      }
    } else {
      notificationDot.style.display = "none";
      notificationDot.classList.remove("pulse-animation");
      
      document.title = document.title.replace("(•) ", "");
    }
  }

  function markAsRead() {
    const currentTime = Date.now();
    localStorage.setItem("lastReadNotificationTime", currentTime);
    localStorage.setItem("notificationsRead", "true");
    updateNotificationDot(false);
  }

  function markAsUnread() {
    localStorage.setItem("notificationsRead", "false");
    updateNotificationDot(true);
  }

  function getUnreadNotificationState() {
    return localStorage.getItem("notificationsRead") !== "true";
  }

  function showNewLeadAlert(lead) {
    if (Notification.permission === "granted") {
      new Notification("Nuovo Lead Ricevuto!", {
        body: `${lead.name} ${lead.surname} ha inviato una richiesta`,
        icon: "/assets/img/notification-icon.png",
        tag: "new-lead"
      });
    }
    
    showInAppToast(`Nuovo lead da ${lead.name} ${lead.surname}!`);
  }

  function showInAppToast(message) {
    const toast = document.createElement("div");
    toast.className = "notification-toast";
    toast.innerHTML = `
      <div class="toast-content">
        <i class="fas fa-bell" style="color: #28a745; margin-right: 10px;"></i>
        ${message}
      </div>
    `;
    
    toast.style.cssText = `
      position: fixed;
      top: 20px;
      right: 20px;
      background: white;
      border: 1px solid #28a745;
      border-radius: 8px;
      padding: 15px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.15);
      z-index: 10000;
      transform: translateX(100%);
      transition: transform 0.3s ease;
      max-width: 300px;
    `;
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
      toast.style.transform = "translateX(0)";
    }, 100);
    
    setTimeout(() => {
      toast.style.transform = "translateX(100%)";
      setTimeout(() => {
        if (toast.parentNode) {
          toast.parentNode.removeChild(toast);
        }
      }, 300);
    }, 4000);
  }

  function startPeriodicCheck() {
    checkInterval = setInterval(() => {
      if (!document.hidden) {
        checkNewLeads();
      }
    }, 15000);
  }

  function handleVisibilityChange() {
    document.addEventListener("visibilitychange", function() {
      if (document.hidden) {
        if (checkInterval) {
          clearInterval(checkInterval);
          checkInterval = setInterval(() => checkNewLeads(), 60000);
        }
      } else {
        if (checkInterval) {
          clearInterval(checkInterval);
          startPeriodicCheck();
        }
        checkNewLeads();
      }
    });
  }

  notificaBtn.addEventListener("click", function (e) {
    e.stopPropagation();
    
    const isCurrentlyOpen = notificaBox.classList.contains("show");
    
    if (!isCurrentlyOpen) {
      notificaBox.classList.add("show");
      checkNewLeads();
      
      setTimeout(() => {
        if (notificaBox.classList.contains("show") && newLeadsExist) {
          markAsRead();
        }
      }, 2000);
    } else {
      notificaBox.classList.remove("show");
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

  chiudiNotifica.addEventListener("click", function (e) {
    e.stopPropagation();
    notificaBox.classList.remove("show");
    
    if (newLeadsExist) {
      markAsRead();
    }
  });

  if (Notification.permission === "default") {
    Notification.requestPermission().then(function (permission) {
      if (permission === "granted") {
        console.log("Notifiche browser abilitate");
      }
    });
  }

  window.addEventListener("beforeunload", function() {
    if (checkInterval) {
      clearInterval(checkInterval);
    }
  });
});