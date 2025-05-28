document.addEventListener("DOMContentLoaded", function () {
    var modeSwitch = document.querySelector(".switch-mode");
    var tooltipText = modeSwitch.querySelector(".tooltip-text");
    var lunaIcon = document.querySelector(".luna");
    var soleIcon = document.querySelector(".sole");
  
    function updateThemeIcons() {
      if (document.documentElement.classList.contains("dark")) {
        lunaIcon.style.display = "none";
        soleIcon.style.display = "inline-block";
      } else {
        soleIcon.style.display = "none";
        lunaIcon.style.display = "inline-block";
      }
    }
  
    if (localStorage.getItem("theme") === "dark") {
      document.documentElement.classList.add("dark");
      tooltipText.textContent = "Passa alla modalità chiara";
      updateThemeIcons();
    }
  
    modeSwitch.addEventListener("click", function () {
      document.documentElement.classList.toggle("dark");
      modeSwitch.classList.toggle("active");
  
      if (document.documentElement.classList.contains("dark")) {
        localStorage.setItem("theme", "dark");
        tooltipText.textContent = "Passa alla modalità chiara";
      } else {
        localStorage.setItem("theme", "light");
        tooltipText.textContent = "Passa alla modalità scura";
      }
  
      updateThemeIcons();
    });
  
    var listView = document.querySelector(".list-view");
    var gridView = document.querySelector(".grid-view");
    var projectsList = document.querySelector(".project-boxes");
  
    listView.addEventListener("click", function () {
      gridView.classList.remove("active");
      listView.classList.add("active");
      projectsList.classList.remove("jsGridView");
      projectsList.classList.add("jsListView");
    });
  
    gridView.addEventListener("click", function () {
      gridView.classList.add("active");
      listView.classList.remove("active");
      projectsList.classList.remove("jsListView");
      projectsList.classList.add("jsGridView");
    });
  
    document
      .querySelector(".messages-btn")
      .addEventListener("click", function () {
        document.querySelector(".messages-section").classList.add("show");
      });
  
    document
      .querySelector(".messages-close")
      .addEventListener("click", function () {
        document.querySelector(".messages-section").classList.remove("show");
      });
  });
  
  document.addEventListener("DOMContentLoaded", function () {
    const sidebarLinks = document.querySelectorAll(".app-sidebar-link");
  
    sidebarLinks.forEach((link) => {
      link.addEventListener("click", function () {
        sidebarLinks.forEach((link) => link.classList.remove("active"));
  
        this.classList.add("active");
      });
    });
  });
  
  // Funzione upload avatar - DEPRECATO / NON UTILIZZATO
  document.addEventListener("DOMContentLoaded", function () {
    const avatarUpload = document.getElementById("avatar-upload");
    const avatarPreview = document.getElementById("avatar-preview");
  
    avatarUpload.addEventListener("change", function () {
      const file = this.files[0];
  
      if (file) {
        const reader = new FileReader();
  
        reader.onload = function (e) {
          avatarPreview.src = e.target.result;
        };
  
        const img = new Image();
        img.onload = function () {
          if (img.width > 300 || img.height > 300) {
            alert(
              "L'immagine deve avere una dimensione massima di 300x300 pixel."
            );
            avatarUpload.value = "";
          } else {
            reader.readAsDataURL(file);
          }
        };
        img.src = URL.createObjectURL(file);
      }
    });
  });

  document.addEventListener("DOMContentLoaded", function () {
    var menuToggle = document.getElementById("menu-toggle");
    var sidebar = document.querySelector(".app-sidebar");

    if (menuToggle && sidebar) {
        menuToggle.addEventListener("click", function () {
            sidebar.classList.toggle("active");
        });

        document.addEventListener("click", function (event) {
            if (!sidebar.contains(event.target) && !menuToggle.contains(event.target)) {
                sidebar.classList.remove("active");
            }
        });
    }
});

function showNotification(message, type = "success") {
    const notificationContainer = document.getElementById("notification-container");
    const notification = document.createElement("div");
    notification.classList.add("notification", type);
    notification.innerHTML = `${message} <span style="cursor:pointer;">✖</span>`;

    notification.querySelector("span").addEventListener("click", () => {
        notification.remove();
    });

    notificationContainer.appendChild(notification);

    setTimeout(() => {
        notification.remove();
    }, 5000);
}

/*DRAGNDROP */
document.addEventListener("DOMContentLoaded", function() {
    const projectBoxContainer = document.querySelector('.project-boxes');
    if (!projectBoxContainer) return;
    
    const gridViewBtn = document.querySelector('.grid-view');
    const listViewBtn = document.querySelector('.list-view');
    
    const savedOrder = localStorage.getItem('leadOrder');
    if (savedOrder) {
        try {
            const orderArray = JSON.parse(savedOrder);
            
            const currentItems = Array.from(projectBoxContainer.children);
            const orderedItems = [];
            
            orderArray.forEach(id => {
                const item = currentItems.find(el => el.getAttribute('data-lead-id') === id.toString());
                if (item) {
                    orderedItems.push(item);
                    item.parentNode.removeChild(item);
                }
            });
            
            Array.from(projectBoxContainer.children).forEach(item => {
                orderedItems.push(item);
            });
            
            while (projectBoxContainer.firstChild) {
                projectBoxContainer.removeChild(projectBoxContainer.firstChild);
            }
            
            orderedItems.forEach(item => {
                projectBoxContainer.appendChild(item);
            });
            
        } catch (e) {
            console.error('Errore nel ripristino dell\'ordine:', e);
            localStorage.removeItem('leadOrder');
        }
    }

    let sortableInstance = new Sortable(projectBoxContainer, {
        animation: 150,
        ghostClass: 'sortable-ghost',
        chosenClass: 'sortable-chosen',
        dragClass: 'sortable-drag',
        handle: '.project-box-header',
        onStart: function() {
            document.body.classList.add('dragging');
        },
        onEnd: function(evt) {
            document.body.classList.remove('dragging');
            
            salvaOrdine();
        }
    });

    if (gridViewBtn && listViewBtn) {
        gridViewBtn.addEventListener('click', function() {
            if (sortableInstance) {
                sortableInstance.destroy();
            }
            
            setTimeout(() => {
                sortableInstance = new Sortable(projectBoxContainer, {
                    animation: 150,
                    ghostClass: 'sortable-ghost',
                    chosenClass: 'sortable-chosen',
                    dragClass: 'sortable-drag',
                    handle: '.project-box-header',
                    onStart: function() {
                        document.body.classList.add('dragging');
                    },
                    onEnd: function() {
                        document.body.classList.remove('dragging');
                        salvaOrdine();
                    }
                });
            }, 300);
        });

        listViewBtn.addEventListener('click', function() {
            if (sortableInstance) {
                sortableInstance.destroy();
            }
            
            setTimeout(() => {
                sortableInstance = new Sortable(projectBoxContainer, {
                    animation: 150,
                    ghostClass: 'sortable-ghost',
                    chosenClass: 'sortable-chosen',
                    dragClass: 'sortable-drag',
                    handle: '.project-box-header',
                    onStart: function() {
                        document.body.classList.add('dragging');
                    },
                    onEnd: function() {
                        document.body.classList.remove('dragging');
                        salvaOrdine();
                    }
                });
            }, 300);
        });
    }
    
    function salvaOrdine() {
        const boxes = document.querySelectorAll('.project-box-wrapper');
        const idArray = Array.from(boxes).map(box => box.getAttribute('data-lead-id'));
        localStorage.setItem('leadOrder', JSON.stringify(idArray));
        
        const notification = document.createElement('div');
        notification.classList.add('order-saved-notification');
        notification.textContent = 'Ordine aggiornato';
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.classList.add('show');
            setTimeout(() => {
                notification.classList.remove('show');
                setTimeout(() => {
                    document.body.removeChild(notification);
                }, 300);
            }, 1500);
        }, 10);
    }
});

const resetOrderBtn = document.getElementById('reset-order');
if (resetOrderBtn) {
    resetOrderBtn.addEventListener('click', function() {
        if (confirm('Vuoi ripristinare l\'ordine originale dei lead?')) {
            localStorage.removeItem('leadOrder');
            location.reload();
        }
    });
}