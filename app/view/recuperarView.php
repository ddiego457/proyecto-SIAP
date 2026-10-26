<?php
// Vista: solicitar enlace de recuperación de contraseña (pre-login)
$error = isset($error) ? $error : false;
$success = isset($success) ? $success : false;
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Recuperar contraseña</title>
<link rel="icon" type="image/png" href="assets/img/SIAPpeque%C3%B1o.png">

<link rel="stylesheet" href="assets/css/loginDesign.css">
</head>

<body>
    <main class="card" aria-label="Recuperación de contraseña">
        <section class="header">
        <div class="logo-placeholder" aria-hidden="true">
            <img src="assets/img/SIAPlogo.png" alt="SIAP">
        </div>
        <p class="org-text"><strong>Coordinación de Proyectos Institucionales</strong></p>
        <p class="org-text">Universidad Politécnica Territorial<br/>Andrés Eloy Blanco — Estado Lara</p>
        <p class="org-text"><strong>UPTAEB</strong></p>
        </section>

        <section class="form">
        <h1 class="welcome-title">Recuperar contraseña</h1>
        <p class="welcome-subtitle">Ingrese el correo electrónico registrado para recibir un enlace de recuperación</p>

        <?php if (!empty($error)): ?>
            <div class="error-message" style="color: #d9534f; background: #f2dede; padding: 10px; border-radius: 5px; margin-bottom: 15px; border: 1px solid #ebccd1;">
                <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div style="color: #3c763d; background: #dff0d8; padding: 10px; border-radius: 5px; margin-bottom: 15px; border: 1px solid #d6e9c6;">
                <strong>Listo:</strong> <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <form method="post" autocomplete="off">
            <div class="field">
            <span class="icon-left" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="2" y="4" width="20" height="16" rx="2"/>
                <path d="m22 7-10 6L2 7"/>
                </svg>
            </span>
            <input type="email" name="email" placeholder="correo@ejemplo.com" autocomplete="email" required />
            </div>

            <button type="submit" class="btn">Enviar enlace de recuperación →</button>
        </form>

        <p style="text-align:center; margin-top:18px; font-size:14px;">
            <a href="?url=inicio" style="color:#1f6f54;">← Volver al login</a>
        </p>
        </section>

        <section class="footer">
        <div>El enlace de recuperación es válido por 30 minutos.</div>
        <div style="margin-top:4px;">Dirección de Planificación Presupuestaria — UPTAEB</div>
        </section>
    </main>
</body>
</html>
