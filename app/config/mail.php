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
//explica quien capta estos datos y como se usan en el siguiente comentario
// Estos datos son captados por la clase MailHelper en el método send(). Cuando se llama
// pero como los llama si no tiene namespaces ni se guarda en ningun metodo
// al enviar un correo, la clase MailHelper carga este archivo de configuración para obtener los detalles del servidor SMTP, como el host, puerto, tipo de cifrado, nombre de usuario y contraseña. Luego utiliza estos datos para configurar PHPMailer y enviar correos electrónicos a través del servidor SMTP especificado.

return [
    'host'       => EnvHelper::get('SMTP_HOST', 'smtp.gmail.com'),
    'port'       => (int) EnvHelper::get('SMTP_PORT', '587'),
    'encryption' => EnvHelper::get('SMTP_ENCRYPTION', 'tls'),
    'username'   => EnvHelper::get('SMTP_USERNAME', ''),
    'password'   => str_replace(' ', '', EnvHelper::get('SMTP_PASSWORD', '')),
    'fromEmail'  => EnvHelper::get('SMTP_FROM_EMAIL', '') ?: EnvHelper::get('SMTP_USERNAME', ''),
    'fromName'   => EnvHelper::get('SMTP_FROM_NAME', 'Administrador SIAP')
];
