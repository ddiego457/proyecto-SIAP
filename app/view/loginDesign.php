<?php
// Diseño de Login responsivo (PHP para que el sistema lo renderice)si
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Login</title>

<!-- Fonts -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;600&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">

<link rel="stylesheet" href="assets/css/loginDesign.css">
</head>

<body>
    <main class="card" aria-label="Inicio de sesión">
        <section class="header">
        <div class="logo-placeholder" aria-hidden="true">
            <img src="assets/img/SIAPlogo.png" alt="SIAP">
        </div>
        <p class="org-text"><strong>Coordinación de Planificación Presupuestaria</strong></p>
        <p class="org-text">Universidad Politécnica Territorial<br/>Andrés Eloy Blanco — Estado Lara</p>
        <p class="org-text"><strong>UPTAEB</strong></p>
        </section>

        <section class="form">
        <h1 class="welcome-title">Bienvenido</h1>
        <p class="welcome-subtitle">Ingrese sus credenciales para acceder al sistema</p>

        <!-- Se mantiene navegación sin romper tu sistema -->
        <form  method="post" autocomplete="off">
            <div class="field">
            <span class="icon-left" aria-hidden="true">
                <!-- user icon -->
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M20 21a8 8 0 0 0-16 0"/>
                <circle cx="12" cy="8" r="4"/>
                </svg>
            </span>
            <input type="text" name="usuario" placeholder="ej. coord.caja o jperez" autocomplete="username" required />
            </div>

            <div class="field">
            <span class="icon-left" aria-hidden="true">
                <!-- lock icon -->
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="11" width="18" height="11" rx="2" />
                <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                </svg>
            </span>

            <input id="password" type="password" name="contrasena" placeholder="**********" autocomplete="current-password" required/>

            <div class="icon-right" id="togglePassword" role="button" tabindex="0" aria-label="Mostrar contraseña">
                <!-- eye icon -->
                <svg id="eyeIcon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/>
                <circle cx="12" cy="12" r="3"/>
                </svg>
            </div>
            </div>

            <button type="submit" class="btn">Ingresar al sistema →</button>
        </form>
        </section>

        <section class="footer">
        <div>¿Olvidó su contraseña? Contacte al administrador del sistema.</div>
        <div style="margin-top:4px;">Dirección de Planificación Presupuestaria — UPTAEB</div>
        </section>
    </main>

    <script src="assets/js/loginDesign.js"></script>
</body>
</html>
