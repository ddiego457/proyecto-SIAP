<?php

/**
 * Configuración SMTP para el envío de correos (recuperación de contraseña).
 *
 * Este archivo concentra TODA la configuración de correo del sistema: datos
 * SMTP (host, puerto, cifrado, usuario, contraseña, remitente) y los ajustes
 * del correo de recuperación (asunto y plantilla).
 *
 * MailHelper::send() y loginDesingController lo cargan desde aquí.
 * NO se usa el archivo /.env para el correo.
 *
 * Valores actuales: Gmail (smtp.gmail.com) puerto 587 con STARTTLS.
 * 'password' es una "contraseña de aplicación" de 16 dígitos generada en
 * https://myaccount.google.com/apppasswords (Gmail rechaza la contraseña normal
 * de la cuenta con el error 535-5.7.8). Se guarda sin espacios.
 */
//explica quien capta estos datos y como se usan en el siguiente comentario
// Estos datos son captados por la clase MailHelper en el método send(). Cuando se llama
// pero como los llama si no tiene namespaces ni se guarda en ningun metodo
// al enviar un correo, la clase MailHelper carga este archivo de configuración para obtener los detalles del servidor SMTP, como el host, puerto, tipo de cifrado, nombre de usuario y contraseña. Luego utiliza estos datos para configurar PHPMailer y enviar correos electrónicos a través del servidor SMTP especificado.

return [
    // ── Servidor SMTP ────────────────────────────────────────────
    'host'       => 'smtp.gmail.com',
    'port'       => 587,
    'encryption' => 'tls',
    'username'   => 'siapweb123@gmail.com',
    'password'   => 'fzrnkllkbpxasqcb',
    'fromEmail'  => 'siapweb123@gmail.com',
    'fromName'   => 'Administrador SIAP',

    // ── Recuperación de contraseña ───────────────────────────────
    // Asunto del correo
    'subjectRecuperacion'  => 'Recuperación de contraseña — SIAP',
    // Plantilla usada (archivo app/template/mail/<valor>.php)
    'templateRecuperacion' => 'recuperacion',
];
