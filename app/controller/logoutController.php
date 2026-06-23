<?php

// Logout estándar: destruye sesión y redirige al login

session_start();

// Destruir datos de sesión
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'] ?? '', $params['secure'] ?? false, $params['httponly'] ?? true);
}

session_destroy();

// Redirigir al login (FrontController renderiza loginDesign cuando url=inicio)
header('Location: ?url=inicio');
exit;

