<?php
namespace EquipoSiap\Siap\helpers;

/**
 * Control de intentos fallidos de login (archivo JSON, sin tabla en la BD).
 *
 * Se almacena en storage/login_attempts.json, indexado por IP + usuario.
 * Reglas:
 *  - MAX_INTENTOS intentos fallidos seguidos -> bloqueo por BLOQUEO_SEGUNDOS.
 *  - Un login exitoso limpia el contador.
 *  - Los registros vencidos se descartan al leer (limpieza automática).
 *
 * El archivo no se versiona (ver .gitignore) y si no se puede escribir,
 * el control se omite en silencio: nunca debe impedir el login por un
 * fallo de permisos de disco.
 */
class IntentosLoginHelper {

    // Intentos fallidos permitidos antes de bloquear
    const MAX_INTENTOS = 5;
    // Duración del bloqueo (15 minutos)
    const BLOQUEO_SEGUNDOS = 900;
    // Un registro sin fallos nuevos se descarta después de 24 h
    const VIDA_SEGUNDOS = 86400;

    /**
     * Clave del registro: IP + usuario normalizado.
     * Se usa hash para no guardar datos sensibles en claro en el archivo.
     */
    public static function clave(string $usuario, string $ip = ''): string {
        $ip = $ip !== '' ? $ip : (isset($_SERVER['REMOTE_ADDR']) ? (string)$_SERVER['REMOTE_ADDR'] : '');
        return hash('sha256', mb_strtolower(trim($usuario)) . '|' . $ip);
    }

    /**
     * Segundos restantes de bloqueo para esa clave.
     * 0 = sin bloqueo.
     */
    public static function bloqueado(string $clave): int {
        $datos = self::leer();
        if (!isset($datos[$clave])) {
            return 0;
        }

        $registro = $datos[$clave];
        $bloqueadoHasta = isset($registro['bloqueado_hasta']) ? (int)$registro['bloqueado_hasta'] : 0;

        if ($bloqueadoHasta > time()) {
            return $bloqueadoHasta - time();
        }

        // Bloqueo vencido: se limpia el contador para empezar de cero
        if ($bloqueadoHasta > 0) {
            unset($datos[$clave]);
            self::escribir($datos);
        }

        return 0;
    }

    /**
     * Registra un intento fallido y devuelve los intentos acumulados.
     * Al llegar a MAX_INTENTOS marca el bloqueo.
     */
    public static function registrarFallo(string $clave): int {
        $datos = self::leer();
        $ahora = time();

        $intentos = isset($datos[$clave]) ? (int)$datos[$clave]['intentos'] : 0;
        $bloqueadoHasta = isset($datos[$clave]) ? (int)$datos[$clave]['bloqueado_hasta'] : 0;

        // Si el bloqueo ya venció, el contador arranca de nuevo
        if ($bloqueadoHasta > 0 && $bloqueadoHasta <= $ahora) {
            $intentos = 0;
        }

        $intentos++;
        $bloquear = $intentos >= self::MAX_INTENTOS;

        $datos[$clave] = [
            'intentos'        => $intentos,
            'ultimo_intento'  => $ahora,
            'bloqueado_hasta' => $bloquear ? $ahora + self::BLOQUEO_SEGUNDOS : 0,
        ];

        self::escribir($datos);
        return $intentos;
    }

    /** Limpia el contador (login exitoso). */
    public static function limpiar(string $clave): void {
        $datos = self::leer();
        if (!isset($datos[$clave])) {
            return;
        }
        unset($datos[$clave]);
        self::escribir($datos);
    }

    /** Mensaje estándar de bloqueo (sin revelar si el usuario existe). */
    public static function mensajeBloqueo(int $segundos): string {
        $minutos = (int)ceil($segundos / 60);
        if ($minutos < 1) {
            $minutos = 1;
        }
        $texto = $minutos === 1 ? '1 minuto' : $minutos . ' minutos';
        return 'Demasiados intentos de acceso. La entrada está bloqueada temporalmente por ' . $texto . '.';
    }

    // ─── Internos ──────────────────────────────────────────────

    private static function rutaArchivo(): string {
        return dirname(__DIR__, 2) . '/storage/login_attempts.json';
    }

    /** @return array<string, array> */
    private static function leer(): array {
        $ruta = self::rutaArchivo();
        if (!is_file($ruta)) {
            return [];
        }

        $crudo = @file_get_contents($ruta);
        if ($crudo === false || trim($crudo) === '') {
            return [];
        }

        $datos = json_decode($crudo, true);
        if (!is_array($datos)) {
            return [];
        }

        // Descarta registros vencidos (sin bloqueo vigente y con más de 24 h)
        $ahora = time();
        $limpio = [];
        foreach ($datos as $clave => $registro) {
            if (!is_array($registro)) {
                continue;
            }
            $ultimo = isset($registro['ultimo_intento']) ? (int)$registro['ultimo_intento'] : 0;
            $bloqueadoHasta = isset($registro['bloqueado_hasta']) ? (int)$registro['bloqueado_hasta'] : 0;

            if ($bloqueadoHasta > $ahora) {
                $limpio[$clave] = $registro;
            } elseif (($ultimo + self::VIDA_SEGUNDOS) > $ahora) {
                $limpio[$clave] = $registro;
            }
        }

        return $limpio;
    }

    private static function escribir(array $datos): void {
        $ruta = self::rutaArchivo();
        $dir = dirname($ruta);

        if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
            error_log('[IntentosLogin] No se pudo crear el directorio storage/');
            return;
        }

        // storage/ no se versiona: se protege aquí mismo contra acceso web
        $htaccess = $dir . '/.htaccess';
        if (!is_file($htaccess)) {
            @file_put_contents($htaccess, "Require all denied\n", LOCK_EX);
        }

        $json = json_encode($datos, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            return;
        }

        // LOCK_EX evita que dos peticiones simultáneas se sobrescriban
        if (@file_put_contents($ruta, $json, LOCK_EX) === false) {
            error_log('[IntentosLogin] No se pudo escribir storage/login_attempts.json');
        }
    }
}

?>