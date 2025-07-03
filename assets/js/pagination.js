document.addEventListener('DOMContentLoaded', function() {
    const paginationLinks = document.querySelectorAll('.pagination-link');
    const leadsTable = document.querySelector('.leads-table');
    
    paginationLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            if (!this.classList.contains('active')) {
                leadsTable.classList.add('loading');
                
                setTimeout(() => {
                }, 100);
            }
        });
    });
    
    function loadPage(pageNumber) {
        const currentUrl = new URL(window.location);
        currentUrl.searchParams.set('page', pageNumber);
        
        history.pushState({page: pageNumber}, '', currentUrl);
        
    }
    
    window.addEventListener('popstate', function(e) {
        if (e.state && e.state.page) {
            location.reload();
        }
    });
    
    document.addEventListener('keydown', function(e) {
        if (document.activeElement.tagName !== 'INPUT' && 
            document.activeElement.tagName !== 'TEXTAREA' &&
            document.activeElement.tagName !== 'SELECT') {
            
            const currentPage = parseInt(document.querySelector('.pagination-link.active')?.textContent) || 1;
            const totalPages = parseInt(document.querySelector('.pagination-link:not(.prev):not(.next):last-of-type')?.textContent) || 1;
            
            switch(e.key) {
                case 'ArrowLeft':
                    if (currentPage > 1) {
                        window.location.href = `?page=${currentPage - 1}`;
                    }
                    break;
                    
                case 'ArrowRight':
                    if (currentPage < totalPages) {
                        window.location.href = `?page=${currentPage + 1}`;
                    }
                    break;
                    
                case 'Home':
                    if (currentPage !== 1) {
                        window.location.href = `?page=1`;
                    }
                    break;
                    
                case 'End':
                    if (currentPage !== totalPages) {
                        window.location.href = `?page=${totalPages}`;
                    }
                    break;
            }
        }
    });
    
    const prevLink = document.querySelector('.pagination-link.prev');
    const nextLink = document.querySelector('.pagination-link.next');
    
    if (prevLink) {
        prevLink.setAttribute('title', 'Pagina precedente (←)');
    }
    
    if (nextLink) {
        nextLink.setAttribute('title', 'Pagina successiva (→)');
    }
    
    paginationLinks.forEach(link => {
        if (!link.classList.contains('prev') && !link.classList.contains('next')) {
            const pageNum = link.textContent;
            if (link.classList.contains('active')) {
                link.setAttribute('title', `Pagina corrente: ${pageNum}`);
            } else {
                link.setAttribute('title', `Vai alla pagina ${pageNum}`);
            }
        }
    });
    
    if (window.location.search.includes('page=')) {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    }
});

function loadPageAjax(pageNumber) {
    const leadsTable = document.querySelector('.leads-table tbody');
    const paginationContainer = document.querySelector('.pagination-container');
    
    leadsTable.style.opacity = '0.5';
    
    fetch(`leads.php?page=${pageNumber}&ajax=1`)
        .then(response => response.json())
        .then(data => {
            leadsTable.innerHTML = data.table_html;
            
            paginationContainer.innerHTML = data.pagination_html;
            
            const newUrl = new URL(window.location);
            newUrl.searchParams.set('page', pageNumber);
            history.pushState({page: pageNumber}, '', newUrl);
            
            leadsTable.style.opacity = '1';
        })
        .catch(error => {
            console.error('Errore nel caricamento della pagina:', error);
            window.location.href = `?page=${pageNumber}`;
        });
}