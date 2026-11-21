<?php
namespace EquipoSiap\Siap\helpers;

trait SessionTrait {

    private $loginUrl = '?url=inicio';

    // Catálogo de módulos del sistema (clave de la URL => etiqueta visible).
    // Debe mantenerse en sincronía con las llamadas a requireModule(...) de los controladores.
    private $catalogoModulos = [
        'dependencia'        => 'Dependencias',
        'responsable'        => 'Responsables',
        'proveedor'          => 'Proveedores',
        'tasaBCV'            => 'Tasa BCV',
        'requerimiento'      => 'Requerimientos',
        'productosServicios' => 'Productos y Servicios',
        'periodo'            => 'Periodo',
        'reporte'            => 'Informes',
    ];

    // Módulos permitidos por rol POR DEFECTO. Se usan como respaldo cuando el rol
    // no está definido en app/config/roles_modulos.json. '*' = todos los módulos.
    // ('perfil' = cambio de foto de perfil desde la sidebar; se permite siempre, ver rolAccedeA)
    private $modulosPorRol = [
        'Administrador' => '*',
        'Coordinador'   => '*',
        'Usuario'       => ['requerimiento', 'reporte', 'perfil'],
    ];
    // Roles protegidos: acceso total fijo, no se pueden personalizar desde el registro de roles.
    private $rolesAccesoTotal = ['Administrador', 'Coordinador'];
    // Roles con acceso a todos los módulos administrativos.
    private $rolesAdmin = ['Administrador', 'Coordinador'];
    // Destino tras login / redirecciones, según rol
    private $homeUrlPorRol = [
        'Coordinador' => '?url=requerimiento&type=main',
    ];
    private $homeUrl = '?url=requerimiento&type=main';

    // Configuración personalizada de acceso a módulos por rol (JSON) y su cache por petición.
    private static $cacheModulosRol = null;

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

        if ($this->rolAccedeA($rol, $modulo)) {
            return;
        }

        if (!$this->rolConocido($rol)) {
            http_response_code(403);
            exit('Acceso denegado.');
        }
        header("Location: " . $this->homeForRole());
        exit();
    }

    // Destino tras login / redirecciones. Se valida contra los módulos permitidos
    // para evitar bucles de redirección cuando el rol no tiene 'requerimiento'.
    private function homeForRole(): string {
        $rol = isset($_SESSION['rol']) ? $_SESSION['rol'] : '';
        $home = isset($this->homeUrlPorRol[$rol]) ? $this->homeUrlPorRol[$rol] : $this->homeUrl;

        if ($this->rolAccedeA($rol, 'requerimiento')) {
            return $home;
        }

        foreach (array_keys($this->catalogoModulos) as $modulo) {
            if ($this->rolAccedeA($rol, $modulo)) {
                return '?url=' . $modulo . '&type=main';
            }
        }

        // Rol sin ningún módulo permitido: cerrar sesión en vez de entrar en bucle.
        return '?url=inicio&type=logout';
    }

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

    public function puedeGestionarUnidades(): bool {
        return $this->esSuperAdmin();
    }

    // ─── Acceso a módulos por rol ─────────────────────────────────────
    // Catálogo disponible (clave => etiqueta) para la UI (sidebar, modal de roles).
    public function getCatalogoModulos(): array {
        return $this->catalogoModulos;
    }

    // Ruta del archivo JSON con la personalización de acceso por rol.
    private function archivoModulosRol(): string {
        return dirname(__DIR__) . '/config/roles_modulos.json';
    }

    // Lectura del JSON. Devuelve [] si el archivo no existe o está corrupto.
    private function leerModulosRol(): array {
        if (self::$cacheModulosRol !== null) {
            return self::$cacheModulosRol;
        }

        $config = [];
        $ruta = $this->archivoModulosRol();
        if (is_file($ruta)) {
            $contenido = @file_get_contents($ruta);
            if ($contenido !== false) {
                $decodificado = json_decode($contenido, true);
                if (is_array($decodificado)) {
                    $config = $decodificado;
                }
            }
        }

        self::$cacheModulosRol = $config;
        return $config;
    }

    // ¿El rol existe para el sistema (protegido, con respaldo por defecto o configurado en JSON)?
    private function rolConocido($rol): bool {
        if (!is_string($rol) || $rol === '') {
            return false;
        }
        return in_array($rol, $this->rolesAccesoTotal, true)
            || isset($this->modulosPorRol[$rol])
            || array_key_exists($rol, $this->leerModulosRol());
    }

    // Módulos efectivos de un rol: siempre un array de claves del catálogo ([] = sin acceso).
    public function modulosPermitidos($rol): array {
        $rol = is_string($rol) ? trim($rol) : '';
        if ($rol === '') {
            return [];
        }

        // Roles protegidos: acceso total fijo, ignoran el JSON.
        if (in_array($rol, $this->rolesAccesoTotal, true)) {
            return array_keys($this->catalogoModulos);
        }

        $config = $this->leerModulosRol();
        $modulos = array_key_exists($rol, $config)
            ? $config[$rol]
            : (isset($this->modulosPorRol[$rol]) ? $this->modulosPorRol[$rol] : null);

        if ($modulos === '*') {
            return array_keys($this->catalogoModulos);
        }
        if (!is_array($modulos)) {
            return [];
        }

        // Solo se consideran claves vigentes del catálogo (mantiene el orden del catálogo).
        return array_values(array_intersect(array_keys($this->catalogoModulos), $modulos));
    }

    // ¿El rol tiene acceso a la totalidad de los módulos?
    public function esAccesoTotalRol($rol): bool {
        $rol = is_string($rol) ? trim($rol) : '';
        if ($rol === '') {
            return false;
        }
        if (in_array($rol, $this->rolesAccesoTotal, true)) {
            return true;
        }

        $config = $this->leerModulosRol();
        $modulos = array_key_exists($rol, $config)
            ? $config[$rol]
            : (isset($this->modulosPorRol[$rol]) ? $this->modulosPorRol[$rol] : null);

        return $modulos === '*';
    }

    // Acceso público para vistas (sidebar): ¿puede el rol actual ver/entrar a este módulo?
    public function puedeAccederModulo($modulo): bool {
        $this->startSession();
        $rol = isset($_SESSION['rol']) ? $_SESSION['rol'] : '';
        return $this->rolAccedeA($rol, $modulo);
    }

    // Guarda la personalización de un rol en el JSON. '*' = acceso total.
    public function guardarModulosRol(string $rol, $modulos): bool {
        $rol = trim($rol);
        if ($rol === '' || in_array($rol, $this->rolesAccesoTotal, true)) {
            return false;
        }

        if (is_array($modulos)) {
            // Solo claves vigentes del catálogo, en el orden del catálogo.
            $modulos = array_values(array_intersect(array_keys($this->catalogoModulos), $modulos));
        } elseif ($modulos !== '*') {
            return false;
        }

        $config = $this->leerModulosRol();
        $config[$rol] = $modulos;
        ksort($config);

        $json = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            return false;
        }

        if (@file_put_contents($this->archivoModulosRol(), $json . PHP_EOL, LOCK_EX) === false) {
            return false;
        }

        self::$cacheModulosRol = $config;
        return true;
    }

    private function rolAccedeA($rol, $modulo): bool {
        // 'perfil' (foto desde la sidebar) es accesible para todos los roles.
        if ($modulo === 'perfil') {
            return true;
        }
        if (!$this->rolConocido($rol)) {
            return false;
        }
        return in_array($modulo, $this->modulosPermitidos($rol), true);
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
