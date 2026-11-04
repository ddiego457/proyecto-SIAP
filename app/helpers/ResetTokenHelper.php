<?php

    namespace EquipoSiap\Siap\helpers;

    // ============================================================
    // Token de recuperación de contraseña SIN estado
    // (no usa la tabla password_resets ni escribe nada en la BD)
    //
    // token = payload(id_responsable, expira, huella_password) + '.' + firma HMAC-SHA256
    //
    // - El payload viaja dentro del propio enlace, firmado con un
    //   secreto guardado en app/config/reset_secret.php (se
    //   autogenera en la primera uso; no se versiona).
    // - La "huella" es un trozo del hash SHA-256 del hash bcrypt
    //   actualmente almacenado en la BD. Al verificar, se compara
    //   con el hash vigente: si alguien cambió la contraseña
    //   (con este enlace o desde el módulo de responsables),
    //   todos los enlaces anteriores quedan inválidos solos.
    // ============================================================

    class ResetTokenHelper
    {
        // Vida del enlace: 30 minutos
        const VIDA_SEGUNDOS = 1800;

        // ─── Secreto HMAC (archivo autogenerado, sin tocar la BD) ──
        private static function rutaSecreto(): string
        {
            return dirname(__DIR__) . '/config/reset_secret.php';
        }

        private static function secreto(): string
        {
            $ruta = self::rutaSecreto();

            if (is_file($ruta)) {
                $sec = include $ruta;
                if (is_string($sec) && strlen($sec) >= 32) {
                    return $sec;
                }
            }

            // Primera uso: generar y guardar el secreto
            $sec = 'reset_' . bin2hex(random_bytes(32));
            $contenido = "<?php\n"
                . "// Secreto de recuperación de contraseña (HMAC). Generado automáticamente.\n"
                . "// No compartir ni exponer públicamente.\n"
                . "return '" . $sec . "';\n";

            if (@file_put_contents($ruta, $contenido, LOCK_EX) === false && !is_file($ruta)) {
                throw new \RuntimeException('No se pudo crear app/config/reset_secret.php (verifique permisos de escritura).');
            }

            // Si otra petición lo creó a la vez, usar el que quedó en disco
            if (is_file($ruta)) {
                $sec = include $ruta;
                if (is_string($sec) && strlen($sec) >= 32) {
                    return $sec;
                }
            }

            throw new \RuntimeException('No se pudo leer el secreto de recuperación de contraseña.');
        }

        // Huella corta del hash bcrypt almacenado (16 hex)
        public static function huella(string $passwordHash): string
        {
            return substr(hash('sha256', $passwordHash), 0, 16);
        }

        // ─── Firmar: genera el token para el enlace ───────────────
        public static function firmar(int $idResponsable, string $huellaPassword): string
        {
            $payload = json_encode([
                'id'  => $idResponsable,
                'exp' => time() + self::VIDA_SEGUNDOS,
                'pw'  => $huellaPassword,
            ]);

            $b64 = rtrim(strtr(base64_encode($payload), '+/', '-_'), '=');
            $firma = hash_hmac('sha256', $b64, self::secreto());

            return $b64 . '.' . $firma;
        }

        // ─── Verificar: firma + expiración. Devuelve ['id','pw'] o null ──
        public static function verificar(string $token): ?array
        {
            if ($token === '' || strlen($token) > 512) {
                return null;
            }

            $partes = explode('.', $token);
            if (count($partes) !== 2 || $partes[0] === '' || $partes[1] === '') {
                return null;
            }

            [$b64, $firma] = $partes;

            // Comparación en tiempo constante (hash_equals)
            $esperada = hash_hmac('sha256', $b64, self::secreto());
            if (!hash_equals($esperada, $firma)) {
                return null;
            }

            $payload = json_decode(base64_decode(strtr($b64, '-_', '+/'), true), true);
            if (!is_array($payload) || !isset($payload['id'], $payload['exp'], $payload['pw'])) {
                return null;
            }

            if ((int)$payload['exp'] < time()) {
                return null; // expirado
            }

            if (!preg_match('/^[0-9a-f]{16}$/', (string)$payload['pw'])) {
                return null;
            }

            return [
                'id' => (int)$payload['id'],
                'pw' => (string)$payload['pw'],
            ];
        }
    }

?>
