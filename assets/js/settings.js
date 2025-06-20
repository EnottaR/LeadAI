function setupTabNavigation() {
  const tabs = document.querySelectorAll('.settings-tab');
  const panels = document.querySelectorAll('.settings-panel');
  
  tabs.forEach(tab => {
    tab.addEventListener('click', function() {
      tabs.forEach(t => t.classList.remove('active'));
      
      panels.forEach(p => p.classList.remove('active'));
      
      this.classList.add('active');
      
      const panelId = this.getAttribute('data-tab') + '-panel';
      document.getElementById(panelId).classList.add('active');
    });
  });
}

document.addEventListener("DOMContentLoaded", function () {
  setupTabNavigation();
  setupLogout();
  setupStatusUpdate();
  
});

function showNotification(message, type = "error") {
  const notificationContainer = document.getElementById("notification-container");
  
  if (!notificationContainer) {
    console.error("⚠️ Errore: Elemento #notification-container non trovato.");
    return;
  }

  notificationContainer.innerHTML = message;
  notificationContainer.style.backgroundColor = type === "success" ? "rgb(0, 180, 0)" : "rgb(255, 0, 0)";
  notificationContainer.classList.add("show");

  setTimeout(() => {
    notificationContainer.classList.remove("show");
  }, 5000);
}

function setupEditField(editBtnId, displayId, editContainerId, inputId, saveBtnId, cancelBtnId, apiKey) {
  const editBtn = document.getElementById(editBtnId);
  const displayContainer = document.getElementById(displayId);
  const editContainer = document.getElementById(editContainerId);
  const inputField = document.getElementById(inputId);
  const saveBtn = document.getElementById(saveBtnId);
  const cancelBtn = document.getElementById(cancelBtnId);

  if (!editBtn || !displayContainer || !editContainer || !inputField || !saveBtn || !cancelBtn) return;

  editBtn.addEventListener("click", function () {
    editContainer.style.display = "flex";
    displayContainer.style.display = "none";
    inputField.value = displayContainer.querySelector("span").textContent;
  });

  cancelBtn.addEventListener("click", function () {
    editContainer.style.display = "none";
    displayContainer.style.display = "flex";
  });

  saveBtn.addEventListener("click", function () {
    const newValue = inputField.value.trim();
    
    if (!newValue) {
      showNotification("⚠️ Inserisci un valore valido.", "error");
      return;
    }

    if (confirm("Vuoi aggiornare le informazioni?")) {
      fetch("includes/settings-update.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ [apiKey]: newValue }),
      })
      .then(response => response.json())
      .then(data => {
        showNotification(data.message, data.status === "success" ? "success" : "error");
        if (data.status === "success") {
          displayContainer.querySelector("span").textContent = newValue;
          editContainer.style.display = "none";
          displayContainer.style.display = "flex";
        }
      })
      .catch(error => {
        console.error("Errore:", error);
        showNotification("⚠️ Errore durante l'aggiornamento.", "error");
      });
    }
  });
}

function setupStatusUpdate() {
  document.querySelectorAll(".status-select").forEach(select => {
    select.addEventListener("change", function () {
      const leadId = this.getAttribute("data-lead-id");
      const newStatus = this.value;

      fetch("includes/update-status.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ lead_id: leadId, status_id: newStatus }),
      })
      .then(response => response.json())
      .then(data => {
        showNotification(data.message, data.status === "success" ? "success" : "error");
      })
      .catch(error => {
        console.error("Errore:", error);
        showNotification("⚠️ Errore durante l'aggiornamento dello status.", "error");
      });
    });
  });
}

function setupLogout() {
  const logoutBtn = document.getElementById("logout-button");
  if (logoutBtn) {
    logoutBtn.addEventListener("click", function () {
      const overlay = document.getElementById("logout-overlay");
      overlay.style.opacity = "1";
      overlay.style.visibility = "visible";

      setTimeout(() => {
        window.location.href = "logout.php";
      }, 5000);
    });
  }
}

document.addEventListener("DOMContentLoaded", function () {
  setupLogout();
  setupStatusUpdate();

  setupEditField("edit-email-btn", "email-display", "email-edit-container", "user-email", "save-email-btn", "cancel-email-btn", "email");
  setupEditField("edit-company-btn", "company-display", "company-edit-container", "user-company", "save-company-btn", "cancel-company-btn", "company");
  
  // FIX: Parametri corretti per website name e url
  setupEditField("edit-website-name-btn", "website-name-display", "website-name-edit-container", "user-website-name", "save-website-name-btn", "cancel-website-name-btn", "website_name");
  setupEditField("edit-website-url-btn", "website-url-display", "website-url-edit-container", "user-website-url", "save-website-url-btn", "cancel-website-url-btn", "website_url");

const editPasswordBtn = document.getElementById("edit-password-btn");
const passwordEditContainer = document.getElementById("password-edit-container");
const passwordInput = document.getElementById("user-password");
const savePasswordBtn = document.getElementById("save-password-btn");
const cancelPasswordBtn = document.getElementById("cancel-password-btn");

if (editPasswordBtn && passwordEditContainer && passwordInput && savePasswordBtn && cancelPasswordBtn) {
    editPasswordBtn.addEventListener("click", function () {
        passwordEditContainer.style.display = "flex";
        editPasswordBtn.style.display = "none";
        passwordInput.value = "";
        passwordInput.focus();
    });

    cancelPasswordBtn.addEventListener("click", function () {
        passwordEditContainer.style.display = "none";
        editPasswordBtn.style.display = "inline-block";
        passwordInput.value = "";
    });

    savePasswordBtn.addEventListener("click", function () {
        const newPassword = passwordInput.value.trim();

        if (!newPassword) {
            showNotification("⚠️ Inserisci una password.", "error");
            return;
        }
        
        if (newPassword.length < 6) {
            showNotification("⚠️ La password deve contenere almeno 6 caratteri.", "error");
            return;
        }

        savePasswordBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verifico...';
        savePasswordBtn.disabled = true;

        fetch("includes/settings-update.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ password: newPassword }),
        })
        .then(response => response.json())
        .then(data => {
            showNotification(data.message, data.status === "success" ? "success" : "error");
            
            savePasswordBtn.innerHTML = '<i class="fas fa-save"></i> Salva';
            savePasswordBtn.disabled = false;
            
            if (data.status === "success") {
                passwordEditContainer.style.display = "none";
                editPasswordBtn.style.display = "inline-block";
                passwordInput.value = "";
            }
        })
        .catch(error => {
            console.error("Errore:", error);
            showNotification("⚠️ Errore durante l'aggiornamento della password.", "error");
            
            savePasswordBtn.innerHTML = '<i class="fas fa-save"></i> Salva';
            savePasswordBtn.disabled = false;
        });
    });
}
	const togglePasswordVisibility = document.getElementById("toggle-password-visibility");
if (togglePasswordVisibility && passwordInput) {
    togglePasswordVisibility.addEventListener("click", function() {
        const type = passwordInput.getAttribute("type") === "password" ? "text" : "password";
        passwordInput.setAttribute("type", type);
        this.classList.toggle("fa-eye");
        this.classList.toggle("fa-eye-slash");
    });
}
});