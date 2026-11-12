<?php
namespace EquipoSiap\Siap\helpers;

trait SessionTrait {

    private $loginUrl = '?url=inicio';
    // Módulos permitidos por rol. '*' = todos los módulos.
    // ('perfil' = cambio de foto de perfil desde la sidebar, accesible a todos los roles)
    private $modulosPorRol = [
        'Administrador' => '*',
        'Coordinador'   => '*',
        'Usuario'       => ['requerimiento', 'reporte', 'perfil'],
    ];
    // Roles con acceso a todos los módulos administrativos.
    private $rolesAdmin = ['Administrador', 'Coordinador'];
    // Destino tras login / redirecciones, según rol
    private $homeUrlPorRol = [
        'Coordinador' => '?url=productosServicios&type=main',
    ];
    private $homeUrl = '?url=requerimiento&type=main';

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
            header("Location: " . $this->homeForRole());
            exit();
        }
    }

    public function requireModule($modulo): void {
        $this->startSession();
        $rol = isset($_SESSION['rol']) ? $_SESSION['rol'] : '';

        if (!$this->rolAccedeA($rol, $modulo)) {
            if (!isset($this->modulosPorRol[$rol])) {
                http_response_code(403);
                exit('Acceso denegado.');
            }
            header("Location: " . $this->homeForRole());
            exit();
        }
    }

    // Home según rol: coordinadores arrancan en Productos y Servicios
    private function homeForRole(): string {
        $rol = isset($_SESSION['rol']) ? $_SESSION['rol'] : '';
        return isset($this->homeUrlPorRol[$rol]) ? $this->homeUrlPorRol[$rol] : $this->homeUrl;
    }

    // ¿El rol tiene permisos de administración?
    public function esAdmin(): bool {
        $this->startSession();
        $rol = isset($_SESSION['rol']) ? $_SESSION['rol'] : '';
        return in_array($rol, $this->rolesAdmin, true);
    }

    public function esSuperAdmin(): bool {
        $this->startSession();
        return isset($_SESSION['rol']) && $_SESSION['rol'] === 'Coordinador';
    }

    public function puedeGestionarRoles(): bool {
        return $this->esSuperAdmin();
    }

    public function puedeGestionarPartidas(): bool {
        return $this->esSuperAdmin();
    }

    // ¿El rol tiene acceso al módulo? '*' = acceso total
    private function rolAccedeA($rol, $modulo): bool {
        if (!isset($this->modulosPorRol[$rol])) {
            return false;
        }
        $modulos = $this->modulosPorRol[$rol];
        return $modulos === '*' || in_array($modulo, $modulos, true);
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
