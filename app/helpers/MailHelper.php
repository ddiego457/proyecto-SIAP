<?php

namespace EquipoSiap\Siap\helpers;

use PHPMailer\PHPMailer\PHPMailer;

class MailHelper {
    
    // Envía un correo HTML. Devuelve true si se envió, false si falló (el error queda en error_log).
    public static function send(string $to, string $subject, string $htmlBody): bool {
        $config = require dirname(__DIR__) . '/config/mail.php';

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
