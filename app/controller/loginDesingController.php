<?php


use EquipoSiap\Siap\model\UserModel;
use EquipoSiap\Siap\helpers\MailHelper;
use EquipoSiap\Siap\helpers\IntentosLoginHelper;

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
        $dato = trim(isset($_POST['dato']) ? $_POST['dato'] : '');

        if ($dato === '') {
            $error = "Ingrese su correo o usuario.";
        } else {
            $user = $userModel->getByEmailOUsuario($dato);

            if ($user === null) {
                $error = "No hay ninguna cuenta activa registrada con ese correo o usuario.";
            } else if (empty($user['email'])) {
                // Usuario sin correo registrado: no hay adónde enviar el enlace
                $error = "Esta opción no está disponible, usuario sin correo.";
            } else {
                $token = $userModel->createResetToken($user['id_responsable']);

                if ($token === null) {
                    $error = "No se pudo generar el enlace de recuperación. Intente de nuevo.";
                } else {
                    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                    $basePath = implode('/', array_map('rawurlencode', explode('/', rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'))));
                    $resetUrl = $scheme . '://' . $_SERVER['HTTP_HOST'] . $basePath . '/?url=inicio&type=reset&token=' . $token;

                    // Plantilla y asunto desde app/config/mail.php
                    $mailConfig = MailHelper::config();
                    $asunto = $mailConfig['subjectRecuperacion'] ?? 'Recuperación de contraseña — SIAP';
                    $plantilla = $mailConfig['templateRecuperacion'] ?? 'recuperacion';

                    $body = MailHelper::plantilla($plantilla, [
                        'nombre'   => $user['nom_resp'],
                        'resetUrl' => $resetUrl,
                    ]);

                    if ($body === '') {
                        // Sin plantilla no se envía nada: se informa al usuario
                        $error = "No se pudo preparar el correo de recuperación. Contacte al administrador.";
                    } else if (MailHelper::send($user['email'], $asunto, $body)) {
                        $success = "Se envió un correo de recuperación a: " . $user['email'] . ". Siga el enlace para cambiar su contraseña.";
                    } else {
                        // Sin token que borrar: no se guardó nada en la BD
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
        } else if ($pass1 !== $pass2) {
            $error = "Las contraseñas no coinciden.";
        } else {
            // El formato de la contraseña se valida dentro del modelo
            $resultado = $userModel->resetPassword($idResponsable, $pass1);
            if ($resultado['success']) {
                header('Location: ?url=inicio&reset=1');
                exit();
            }
            $error = $resultado['message'];
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

        // Mensaje único: no revela si el usuario existe ni cuál campo falló.
        $errorLogin = "Usuario o contraseña incorrectos.";

        if (empty($nombre) || empty($contra)) {
            $error = $errorLogin;
            include 'app/view/loginDesign.php';
            die();
        }

        // Bloqueo temporal por intentos fallidos (storage/login_attempts.json)
        $claveIntento = IntentosLoginHelper::clave($nombre);
        $segundosBloqueo = IntentosLoginHelper::bloqueado($claveIntento);
        if ($segundosBloqueo > 0) {
            $error = IntentosLoginHelper::mensajeBloqueo($segundosBloqueo);
            include 'app/view/loginDesign.php';
            die();
        }

        $loginResult =  $userModel->getLoginSistema($nombre,$contra);

        if (isset($loginResult['status']) && $loginResult['status'] == 1) {
            IntentosLoginHelper::limpiar($claveIntento);
            $this->setSession([
                'rol'            => $loginResult['data'][0]['rol'],
                'id_dep'         => $loginResult['data'][0]['id_dep'],
                'usuario'        => $loginResult['data'][0]['dependencia'],
                'nombre'         => $loginResult['data'][0]['responsable'],
                'id_responsable' => $loginResult['data'][0]['id_responsable'],
                'foto'           => $loginResult['data'][0]['foto']
            ]);
            $home = $this->homeForRole();
            header('location: ' . $home);
            die();
        }

        // Login fallido (usuario inexistente, inactivo o contraseña inválida):
        // se cuenta el intento y se responde SIEMPRE el mismo mensaje.
        $restantes = IntentosLoginHelper::registrarFallo($claveIntento);
        $intentosRestantes = IntentosLoginHelper::MAX_INTENTOS - $restantes;
        $error = $errorLogin;
        if ($restantes >= IntentosLoginHelper::MAX_INTENTOS) {
            $error = IntentosLoginHelper::mensajeBloqueo(IntentosLoginHelper::BLOQUEO_SEGUNDOS);
        } else if ($intentosRestantes === 1) {
            $error .= " Le queda 1 intento antes de bloquear el acceso temporalmente.";
        } else if ($intentosRestantes > 1) {
            $error .= " Le quedan " . $intentosRestantes . " intentos antes de bloquear el acceso temporalmente.";
        }
}
include 'app/view/loginDesign.php';


?>
