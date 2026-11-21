<?php

use EquipoSiap\Siap\helpers\EnvHelper;

/**
 * Configuración SMTP para el envío de correos (recuperación de contraseña).
 *
 * Los valores NO están en este archivo: se leen de /.env en la raíz del
 * proyecto (ver .env.example). El /.env no se versiona y está bloqueado por
 * el .htaccess de la raíz, así que las credenciales no quedan en el repositorio
 * ni son servidas por Apache.
 *
 * MailHelper::send() lo carga desde app/helpers/MailHelper.php.
 *
 * Valores por defecto: Gmail (smtp.gmail.com) puerto 587 con STARTTLS.
 * SMTP_PASSWORD debe ser una "contraseña de aplicación" de 16 dígitos generada en
 * https://myaccount.google.com/apppasswords (Gmail rechaza la contraseña normal
 * de la cuenta con el error 535-5.7.8). Se guarda sin espacios.
 */
return [
    'host'       => EnvHelper::get('SMTP_HOST', 'smtp.gmail.com'),
    'port'       => (int) EnvHelper::get('SMTP_PORT', '587'),
    'encryption' => EnvHelper::get('SMTP_ENCRYPTION', 'tls'),
    'username'   => EnvHelper::get('SMTP_USERNAME', ''),
    'password'   => EnvHelper::get('SMTP_PASSWORD', ''),
    'fromEmail'  => EnvHelper::get('SMTP_FROM_EMAIL', ''),
    'fromName'   => EnvHelper::get('SMTP_FROM_NAME', 'Administrador SIAP'),
];
