<?php


use EquipoSiap\Siap\model\UserModel;
use EquipoSiap\Siap\helpers\MailHelper;

// CIERRE DE SESIÓN: ?url=inicio&type=logout
if (isset($_GET['type']) && $_GET['type'] == 'logout') {
    $this->logout();
}

$userModel = new UserModel();
$error = false;
$success = false;

// RECUPERACIÓN paso 1: solicitar enlace por correo (?url=inicio&type=recuperar)
if (isset($_GET['type']) && $_GET['type'] == 'recuperar') {

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $email = trim(isset($_POST['email']) ? $_POST['email'] : '');

        $error = $this->validarEmail($email);
        if ($error === null) {
            $user = $userModel->getByEmail($email);

            if ($user === null) {
                $error = "No hay ninguna cuenta activa registrada con ese correo.";
            } else {
                $token = $userModel->createResetToken($user['id_responsable']);

                if ($token === null) {
                    $error = "No se pudo generar el enlace de recuperación. Intente de nuevo.";
                } else {
                    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                    $basePath = implode('/', array_map('rawurlencode', explode('/', rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'))));
                    $resetUrl = $scheme . '://' . $_SERVER['HTTP_HOST'] . $basePath . '/?url=inicio&type=reset&token=' . $token;

                    $body = '<div style="font-family:Arial,Helvetica,sans-serif;max-width:520px;margin:auto;border:1px solid #ddd;border-radius:8px;overflow:hidden;">'
                        . '<div style="background:#1f6f54;color:#fff;padding:16px;text-align:center;"><h2 style="margin:0;font-size:20px;">SIAP — Recuperación de contraseña</h2></div>'
                        . '<div style="padding:20px;color:#333;line-height:1.5;">'
                        . '<p>Hola <strong>' . htmlspecialchars($user['nom_rep']) . ':</strong></p>'
                        . '<p>Recibimos una solicitud para restablecer la contraseña de tu cuenta. Haz clic en el siguiente botón (válido por <strong>30 minutos</strong>):</p>'
                        . '<p style="text-align:center;margin:24px 0;"><a href="' . htmlspecialchars($resetUrl) . '" style="background:#1f6f54;color:#fff;padding:12px 22px;border-radius:6px;text-decoration:none;font-weight:bold;">Restablecer contraseña</a></p>'
                        . '<p style="color:#777;font-size:13px;">Si no solicitaste este cambio, ignora este correo. Si el botón no funciona, copia y pega este enlace en tu navegador:<br>'
                        . '<span style="word-break:break-all;">' . htmlspecialchars($resetUrl) . '</span></p>'
                        . '</div></div>';

                    if (MailHelper::send($user['email'], 'Recuperación de contraseña — SIAP', $body)) {
                        $success = "Se envió un correo de recuperación a: " . $user['email'] . ". Siga el enlace para cambiar su contraseña.";
                    } else {
                        $userModel->deleteResetToken($token);
                        $error = "No se pudo enviar el correo. Verifique la configuración de correo del sistema o contacte al administrador.";
                    }
                }
            }
        }
    }

    include 'app/view/recuperarView.php';
    return;
}

// RECUPERACIÓN paso 2: nueva contraseña (?url=inicio&type=reset&token=...)
if (isset($_GET['type']) && $_GET['type'] == 'reset') {

    $token = isset($_GET['token']) ? (string)$_GET['token'] : '';
    $idResponsable = $userModel->validateResetToken($token);

    if ($idResponsable === null) {
        $error = "El enlace de recuperación no es válido o ha expirado. Solicite uno nuevo.";
        include 'app/view/recuperarView.php';
        return;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $pass1 = isset($_POST['nueva_password']) ? (string)$_POST['nueva_password'] : '';
        $pass2 = isset($_POST['confirmar_password']) ? (string)$_POST['confirmar_password'] : '';

        if ($pass1 === '' || $pass2 === '') {
            $error = "Complete ambos campos de contraseña.";
        } else if (($error = $this->validarPassword($pass1)) !== null) {
            // El mensaje del trait ya quedó asignado en $error
        } else if ($pass1 !== $pass2) {
            $error = "Las contraseñas no coinciden.";
        } else if ($userModel->resetPassword($idResponsable, $pass1, $token)) {
            header('Location: ?url=inicio&reset=1');
            exit();
        } else {
            $error = "No se pudo actualizar la contraseña. Intente de nuevo.";
        }
    }

    include 'app/view/resetView.php';
    return;
}

// LOGIN
$this->redirectIfLoggedIn();

if (isset($_GET['reset']) && $_GET['reset'] == '1') {
    $success = "Contraseña restablecida exitosamente. Inicie sesión con su nueva contraseña.";
}

if($_SERVER['REQUEST_METHOD'] === 'POST'){

        $nombre =  isset($_POST['usuario']) ? $_POST['usuario'] : '';
        $contra = isset($_POST['contrasena']) ?$_POST['contrasena'] : '';

        if (empty($nombre) || empty($contra)) {
            $error = "usuario o contraseña incorrectos";
            include 'app/view/loginDesign.php';
            die();
        }

        $loginResult =  $userModel->getLoginSistema($nombre,$contra);

        if (isset($loginResult['status']) && $loginResult['status'] == 1) {
            $this->setSession([
                'rol'            => $loginResult['data'][0]['rol'],
                'id_dep'         => $loginResult['data'][0]['id_dep'],
                'usuario'        => $loginResult['data'][0]['dependencia'],
                'id_responsable' => $loginResult['data'][0]['id_responsable']
            ]);
            header('location: ?url=requerimiento&type=main');
            die();
        }

        if (isset($loginResult['status']) && $loginResult['status'] == 2) {
            $error = "Contraseña incorrecta para el usuario: " . $loginResult['user'];
        } else if ($loginResult == 0) {
            $error = "Usuario no encontrado o error en los datos.";
        } else if ($loginResult == 3) {
            $error = "Usuario inactivo o no encontrado.";
        } else {
            $error = "usuario o contraseña incorrectos";
        }
}
include 'app/view/loginDesign.php';


?>
