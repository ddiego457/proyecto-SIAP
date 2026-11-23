<?php

namespace EquipoSiap\Siap\helpers;

use PHPMailer\PHPMailer\PHPMailer;

class MailHelper {

    /**
     * Renderiza una plantilla de correo de app/template/mail/<$plantilla>.php
     * pasándole las variables de $vars. Devuelve '' si la plantilla no existe.
     */
    public static function plantilla(string $plantilla, array $vars = []): string {
        // Solo nombres simples: evita salirse de app/template/mail/
        $plantilla = basename(str_replace(['\\', "\0"], '/', $plantilla));
        $ruta = dirname(__DIR__) . '/template/mail/' . $plantilla . '.php';

        if ($plantilla === '' || !is_file($ruta)) {
            error_log("MailHelper: plantilla no encontrada (" . $plantilla . ")");
            return '';
        }

        // Las variables de $vars quedan disponibles dentro de la plantilla.
        // La plantilla debe usar isset() para las que no reciba.
        extract($vars);

        ob_start();
        include $ruta;
        return (string) ob_get_clean();
    }

    // Envía un correo HTML. Devuelve true si se envió, false si falló (el error queda en error_log).
    public static function send(string $to, string $subject, string $htmlBody): bool {
        $config = require dirname(__DIR__) . '/config/mail.php';

        if (empty($config['username']) || empty($config['password']) || empty($config['fromEmail'])) {
            error_log("MailHelper error: Credenciales SMTP incompletas en el archivo .env (SMTP_USERNAME, SMTP_PASSWORD o SMTP_FROM_EMAIL vacíos)");
            return false;
        }

        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host       = $config['host'];
            $mail->SMTPAuth   = true;
            $mail->Username   = $config['username'];
            $mail->Password   = $config['password'];
            $mail->SMTPSecure = $config['encryption'];
            $mail->Port       = $config['port'];
            $mail->CharSet    = 'UTF-8';

            $mail->setFrom($config['fromEmail'], $config['fromName']);
            $mail->addAddress($to);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $htmlBody;

            $mail->send();
            return true;
        } catch (\Throwable $e) {
            error_log("MailHelper error: " . $e->getMessage());
            return false;
        }
    }
}

?>
