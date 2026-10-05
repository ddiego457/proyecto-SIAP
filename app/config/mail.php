<?php

/**
 * Configuración SMTP para el envío de correos (recuperación de contraseña).
 *
 * Este archivo NO se versiona (ya está en .gitignore).
 * MailHelper::send() lo carga desde app/helpers/MailHelper.php.
 *
 * Valores actuales: Gmail (smtp.gmail.com) puerto 587 con STARTTLS.
 * 'password' es una "contraseña de aplicación" de 16 dígitos generada en
 * https://myaccount.google.com/apppasswords (Gmail rechaza la contraseña normal
 * de la cuenta con el error 535-5.7.8). Se guarda sin espacios.
 */
return [
    'host'       => 'smtp.gmail.com',
    'port'       => 587,
    'encryption' => 'tls',
    'username'   => 'siapweb123@gmail.com',
    'password'   => 'fzrnkllkbpxasqcb',
    'fromEmail'  => 'siapweb123@gmail.com',
    'fromName'   => 'Administrador SIAP',
];