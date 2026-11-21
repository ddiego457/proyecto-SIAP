<?php
namespace EquipoSiap\Siap\helpers;

/**
 * Carga mínima de variables de entorno desde el archivo /.env de la raíz.
 *
 * Formato esperado: CLAVE=valor (una por línea). Se ignoran líneas vacías y
 * las que empiezan con #. El valor puede ir entre comillas simples o dobles.
 *
 * El archivo /.env no se versiona (ver .gitignore) y está bloqueado por el
 * .htaccess de la raíz, de modo que las credenciales SMTP no son legibles
 * desde el navegador.
 */
class EnvHelper {

    /** @var array<string,string>|null */
    private static $valores = null;

    /** Lee una variable de entorno. Si no existe, devuelve $defecto. */
    public static function get(string $clave, string $defecto = ''): string {
        self::cargar();

        if (isset(self::$valores[$clave])) {
            return self::$valores[$clave];
        }

        // Permite que PHP sea configurado sin editar el .env (putenv/$_ENV)
        $externo = getenv($clave);
        if ($externo !== false && $externo !== '') {
            return $externo;
        }

        return $defecto;
    }

    /** ¿Existe la clave en el .env (o en el entorno del proceso)? */
    public static function has(string $clave): bool {
        self::cargar();
        return isset(self::$valores[$clave]) || getenv($clave) !== false;
    }

    // ─── Internos ──────────────────────────────────────────────

    private static function cargar(): void {
        if (self::$valores !== null) {
            return;
        }

        self::$valores = [];

        $ruta = dirname(__DIR__, 2) . '/.env';
        if (!is_readable($ruta)) {
            return;
        }

        $lineas = file($ruta, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lineas === false) {
            return;
        }

        foreach ($lineas as $linea) {
            $linea = trim($linea);

            // Comentarios y líneas sin clave
            if ($linea === '' || $linea[0] === '#' || strpos($linea, '=') === false) {
                continue;
            }

            list($clave, $valor) = explode('=', $linea, 2);
            $clave = trim($clave);
            $valor = trim($valor);

            if ($clave === '') {
                continue;
            }

            // Quita las comillas envolventes (simples o dobles)
            if ($valor !== '' && ($valor[0] === '"' || $valor[0] === "'") && substr($valor, -1) === $valor[0]) {
                $valor = substr($valor, 1, -1);
            }

            self::$valores[$clave] = $valor;
        }
    }
}

?>
