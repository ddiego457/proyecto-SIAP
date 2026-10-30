<?php
namespace EquipoSiap\Siap\helpers;

trait SessionTrait {

    private $loginUrl = '?url=inicio';
    private $homeUrl = '?url=requerimiento&type=main';
    // Módulos accesibles para usuarios no administradores
    // ('perfil' = cambio de foto de perfil desde la sidebar)
    private $publicModules = ['reporte', 'requerimiento', 'perfil'];

    public function startSession(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function isLoggedIn(): bool {
        $this->startSession();
        return isset($_SESSION['sesion']) && $_SESSION['sesion'] === true;
    }

    public function setSession(array $data): void {
        $this->startSession();
        $_SESSION['sesion'] = true;
        foreach ($data as $key => $value) {
            $_SESSION[$key] = $value;
        }
    }

    public function requireLogin(): void {
        if (!$this->isLoggedIn()) {
            header("Location: " . $this->loginUrl);
            exit();
        }
    }

    public function redirectIfLoggedIn(): void {
        if ($this->isLoggedIn()) {
            header("Location: " . $this->homeUrl);
            exit();
        }
    }

    public function requireModule($modulo): void {
        $this->startSession();
        $rol = isset($_SESSION['rol']) ? $_SESSION['rol'] : '';

        if ($rol !== 'Administrador' && !in_array($modulo, $this->publicModules, true)) {
            header("Location: " . $this->homeUrl);
            exit();
        }
    }

    public function logout(): void {
        $this->startSession();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'] ?? '', $params['secure'] ?? false, $params['httponly'] ?? true);
        }
        session_destroy();
        header("Location: " . $this->loginUrl);
        exit();
    }
}

?>
