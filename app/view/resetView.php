<?php
// Vista: definir nueva contraseña (enlace de recuperación)
$error = isset($error) ? $error : false;
$token = isset($token) ? $token : '';
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Nueva contraseña</title>
<link rel="icon" type="image/png" href="assets/img/SIAPpeque%C3%B1o.png">

<link rel="stylesheet" href="assets/css/loginDesign.css">
</head>

<body>
    <main class="card" aria-label="Nueva contraseña">
        <section class="header">
        <div class="logo-placeholder" aria-hidden="true">
            <img src="assets/img/SIAPlogo.png" alt="SIAP">
        </div>
        <p class="org-text"><strong>Coordinación de Proyectos Institucionales</strong></p>
        <p class="org-text">Universidad Politécnica Territorial<br/>Andrés Eloy Blanco — Estado Lara</p>
        <p class="org-text"><strong>UPTAEB</strong></p>
        </section>

        <section class="form">
        <h1 class="welcome-title">Nueva contraseña</h1>
        <p class="welcome-subtitle">Elija una nueva contraseña para su cuenta</p>

        <?php if (!empty($error)): ?>
            <div class="error-message" style="color: #d9534f; background: #f2dede; padding: 10px; border-radius: 5px; margin-bottom: 15px; border: 1px solid #ebccd1;">
                <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="post" autocomplete="off">
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
            <div class="field">
            <span class="icon-left" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="11" width="18" height="11" rx="2"/>
                <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                </svg>
            </span>
            <input type="password" name="nueva_password" placeholder="Nueva contraseña" autocomplete="new-password" required />
            </div>

            <div class="field">
            <span class="icon-left" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="11" width="18" height="11" rx="2"/>
                <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                </svg>
            </span>
            <input type="password" name="confirmar_password" placeholder="Confirmar contraseña" autocomplete="new-password" required />
            </div>

            <button type="submit" class="btn">Guardar contraseña →</button>
        </form>

        <p style="text-align:center; margin-top:18px; font-size:14px;">
            <a href="?url=inicio" style="color:#1f6f54;">← Volver al login</a>
        </p>
        </section>

        <section class="footer">
        <div>El enlace solo puede usarse una vez.</div>
        <div style="margin-top:4px;">Dirección de Planificación Presupuestaria — UPTAEB</div>
        </section>
    </main>
</body>
</html>
