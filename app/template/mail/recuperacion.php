<?php
/**
 * Plantilla del correo de recuperación de contraseña.
 *
 * Variables esperadas (inyectadas por MailHelper::plantilla()):
 *   $nombre    — nombre del responsable
 *   $resetUrl  — enlace de restablecimiento (válido 30 minutos)
 *
 * Se carga desde app/config/mail.php → MAIL_TEMPLATE_RECUPERACION.
 */

$nombre   = isset($nombre) ? (string)$nombre : '';
$resetUrl = isset($resetUrl) ? (string)$resetUrl : '';

?>
<div style="font-family:Arial,Helvetica,sans-serif;max-width:520px;margin:auto;border:1px solid #ddd;border-radius:8px;overflow:hidden;">
    <div style="background:#1f6f54;color:#fff;padding:16px;text-align:center;">
        <h2 style="margin:0;font-size:20px;">SIAP — Recuperación de contraseña</h2>
    </div>
    <div style="padding:20px;color:#333;line-height:1.5;">
        <p>Hola <strong><?php echo htmlspecialchars($nombre); ?>:</strong></p>
        <p>Recibimos una solicitud para restablecer la contraseña de tu cuenta. Haz clic en el siguiente botón (válido por <strong>30 minutos</strong>):</p>
        <p style="text-align:center;margin:24px 0;">
            <a href="<?php echo htmlspecialchars($resetUrl); ?>" style="background:#1f6f54;color:#fff;padding:12px 22px;border-radius:6px;text-decoration:none;font-weight:bold;">Restablecer contraseña</a>
        </p>
        <p style="color:#777;font-size:13px;">Si no solicitaste este cambio, ignora este correo. Si el botón no funciona, copia y pega este enlace en tu navegador:<br>
            <span style="word-break:break-all;"><?php echo htmlspecialchars($resetUrl); ?></span>
        </p>
    </div>
</div>
