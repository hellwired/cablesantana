<?php
session_start();
require_once 'db_connection.php';
require_once 'client_model.php';

// Redirigir si no está logueado o no tiene el rol visor (u otro rol autorizado)
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['rol'], ['visor', 'administrador', 'editor'])) {
    header('Location: login.php');
    exit();
}

include 'header.php';
?>

<style>
    /* Estilos específicos para Mobile-First Dashboard */
    .search-container {
        position: sticky;
        top: 0;
        z-index: 1000;
        background: #f4f6f9;
        padding: 15px 0;
    }

    .client-card {
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        margin-bottom: 15px;
        padding: 15px;
        border-left: 5px solid #0d6efd;
        transition: transform 0.2s;
    }

    .client-card:active {
        transform: scale(0.98);
    }

    .client-card.debtor {
        border-left-color: #dc3545;
    }

    .client-name {
        font-size: 1.1rem;
        font-weight: 700;
        color: #333;
        margin-bottom: 5px;
    }

    .client-detail {
        font-size: 0.85rem;
        color: #666;
        margin-bottom: 3px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .client-balance {
        font-size: 1.2rem;
        font-weight: bold;
        margin-top: 10px;
        text-align: right;
    }

    .btn-cobrar {
        width: 100%;
        margin-top: 10px;
        font-weight: 600;
        border-radius: 8px;
        padding: 10px;
    }

    /* Ocultar elementos sobrantes del layout web si es necesario */
    h2.page-title {
        font-size: 1.5rem;
        font-weight: 700;
        margin-bottom: 0;
    }
</style>

<div class="row align-items-center mb-3">
    <div class="col-8">
        <h2 class="page-title text-primary"><i class="fas fa-wallet me-2"></i>Cobranza</h2>
    </div>
    <div class="col-4 text-end">
        <span class="badge bg-secondary"><i class="fas fa-user me-1"></i>
            <?php echo htmlspecialchars($_SESSION['username']); ?>
        </span>
    </div>
</div>

<!-- Buscador Fijo -->
<div class="search-container">
    <div class="input-group input-group-lg">
        <span class="input-group-text bg-white border-end-0" id="basic-addon1"><i
                class="fas fa-search text-muted"></i></span>
        <input type="text" id="searchInput" class="form-control border-start-0 ps-0"
            placeholder="Buscar por DNI o Nombre..." autocomplete="off">
        <button class="btn btn-outline-secondary" type="button" id="clearSearch" style="display:none;">
            <i class="fas fa-times"></i>
        </button>
    </div>
</div>

<!-- Contenedor Resultados -->
<div id="resultsContainer" class="mt-2 pb-5">
    <div class="text-center text-muted mt-5">
        <i class="fas fa-search fa-3x mb-3 text-light"></i>
        <p>Escribe el DNI o nombre del cliente<br>para iniciar el cobro.</p>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const searchInput = document.getElementById('searchInput');
        const clearButton = document.getElementById('clearSearch');
        const resultsContainer = document.getElementById('resultsContainer');
        let timeoutId;

        searchInput.addEventListener('input', function (e) {
            clearTimeout(timeoutId);
            const query = e.target.value.trim();

            if (query.length > 0) {
                clearButton.style.display = 'block';
            } else {
                clearButton.style.display = 'none';
            }

            if (query.length < 2) {
                resultsContainer.innerHTML = '<div class="text-center text-muted mt-5"><i class="fas fa-search fa-3x mb-3 text-light"></i><p>Escribe el DNI o nombre del cliente<br>para iniciar el cobro.</p></div>';
                return;
            }

            // Mostrar loading text simple
            resultsContainer.innerHTML = '<div class="text-center mt-4"><div class="spinner-border text-primary" role="status"></div><p class="mt-2 text-muted">Buscando...</p></div>';

            // Debounce de 300ms
            timeoutId = setTimeout(() => {
                fetch(`visor_search_ajax.php?q=${encodeURIComponent(query)}`)
                    .then(response => {
                        if (!response.ok) throw new Error('Error en la red');
                        return response.text();
                    })
                    .then(html => {
                        resultsContainer.innerHTML = html;
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        resultsContainer.innerHTML = '<div class="alert alert-danger">Error al buscar clientes. Verifique su conexión.</div>';
                    });
            }, 300);
        });

        clearButton.addEventListener('click', function () {
            searchInput.value = '';
            searchInput.dispatchEvent(new Event('input'));
            searchInput.focus();
        });
    });
</script>

<?php include 'footer.php'; ?>