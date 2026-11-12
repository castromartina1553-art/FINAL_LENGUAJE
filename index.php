<?php
$projectBase = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
if ($projectBase === '.') {
    $projectBase = '';
}
$assetBase = $projectBase;
$apiBase = $projectBase . '/backend';
$homeUrl = $projectBase . '/';
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f5f6f8">
    <title>Panel de gestión</title>
    <link rel="stylesheet" href="<?php echo htmlspecialchars($assetBase . '/css/app.css', ENT_QUOTES, 'UTF-8'); ?>">
</head>
<body>
    <main id="login-view" class="login-layout" hidden>
        <section class="login-card">
            <a class="brand brand-login" href="<?php echo htmlspecialchars($homeUrl, ENT_QUOTES, 'UTF-8'); ?>" aria-label="Inicio">
                <span class="brand-mark">G</span>
                <span>Gestión<span class="brand-muted"> Simple</span></span>
            </a>
            <p class="eyebrow">PANEL DE ADMINISTRACIÓN</p>
            <h1>Bienvenido</h1>
            <p class="muted">Ingresá con tu cuenta para administrar el sistema.</p>
            <form id="login-form">
                <label for="login-email">Email</label>
                <input id="login-email" name="email" type="email" autocomplete="username" required>
                <label for="login-password">Contraseña</label>
                <input id="login-password" name="password" type="password" autocomplete="current-password" required>
                <p id="login-error" class="form-error" role="alert" hidden></p>
                <button class="button button-primary button-wide" type="submit">Ingresar</button>
            </form>
        </section>
    </main>

    <div id="app-view" class="app-layout" hidden>
        <aside class="sidebar">
            <a class="brand" href="<?php echo htmlspecialchars($homeUrl, ENT_QUOTES, 'UTF-8'); ?>">
                <span class="brand-mark">G</span>
                <span>Gestión<span class="brand-muted"> Simple</span></span>
            </a>
            <p class="nav-caption">MENÚ PRINCIPAL</p>
            <nav id="entity-nav" aria-label="Secciones"></nav>
            <div class="sidebar-bottom">
                <div class="user-summary">
                    <span id="user-avatar" class="avatar">U</span>
                    <span class="user-copy"><strong id="user-name">Usuario</strong><small id="user-role">Sesión activa</small></span>
                </div>
                <button id="logout-button" class="logout-button" type="button">Cerrar sesión</button>
            </div>
        </aside>

        <section class="workspace">
            <header class="topbar">
                <div>
                    <p class="eyebrow">SISTEMA DE GESTIÓN</p>
                    <h1 id="page-title">Productos</h1>
                </div>
                <button id="create-button" class="button button-primary" type="button">＋ <span id="create-label">Nuevo producto</span></button>
            </header>

            <main class="content">
                <div class="content-heading">
                    <div>
                        <h2 id="section-title">Listado de productos</h2>
                        <p id="section-description" class="muted">Administrá los productos disponibles en el sistema.</p>
                    </div>
                    <button id="refresh-button" class="button button-quiet" type="button" aria-label="Actualizar listado">↻ Actualizar</button>
                </div>

                <div id="notice" class="notice" role="status" hidden></div>
                <section class="table-card" aria-label="Registros">
                    <div id="loading" class="table-message">Cargando registros…</div>
                    <div id="empty-state" class="table-message" hidden>No hay registros para mostrar.</div>
                    <div class="table-scroll">
                        <table id="records-table" hidden>
                            <thead><tr id="table-head"></tr></thead>
                            <tbody id="table-body"></tbody>
                        </table>
                    </div>
                </section>
            </main>
        </section>
    </div>

    <dialog id="record-dialog" class="record-dialog">
        <form id="record-form" method="dialog">
            <div class="dialog-heading">
                <div>
                    <p class="eyebrow">GESTIÓN DE DATOS</p>
                    <h2 id="dialog-title">Nuevo registro</h2>
                </div>
                <button id="close-dialog" class="icon-button" type="button" aria-label="Cerrar">×</button>
            </div>
            <div id="form-fields" class="form-fields"></div>
            <p id="form-error" class="form-error" role="alert" hidden></p>
            <div class="dialog-actions">
                <button id="cancel-button" class="button button-quiet" type="button">Cancelar</button>
                <button id="save-button" class="button button-primary" type="submit">Guardar</button>
            </div>
        </form>
    </dialog>

    <script>
        window.APP_CONFIG = { apiBase: <?php echo json_encode($apiBase, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?> };
    </script>
    <script src="<?php echo htmlspecialchars($assetBase . '/js/app.js', ENT_QUOTES, 'UTF-8'); ?>"></script>
</body>
</html>
