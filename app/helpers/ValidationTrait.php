<?php
namespace EquipoSiap\Siap\helpers;

trait ValidationTrait
{
    // ===== REGEX PATTERNS (públicos para acceso directo si se necesita) =====
    // Dependencia
    public $expNombreDep = '/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑüÜ\s\.\-\&\(\)]{3,100}$/';
    // Periodo
    public $expFechaPeriodo = '/^\d{4}-\d{2}-\d{2}$/';
    public $expAnioFiscal = '/^\d{4}$/';
    // Productos/Partidas
    public $expNomItem = '/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑüÜ\s\.\,\-\(\)\/\"\%]{2,150}$/';
    public $expPrecio = '/^\d{1,9}(\.\d{1,2})?$/';
    public $expCodPartida = '/^[0-9]+(\.[0-9]+)?$/';
    public $expDescripcionPartida = '/^[^<>\x00-\x1F\x7F]{1,150}$/';
    // Proveedor
    public $expNombreProv = '/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑüÜ\s\.\-\&\(\)]{3,100}$/';
    public $expDescripcionProv = '/^[^<>\x00-\x1F\x7F]{0,150}$/';
    public $expTelefono = '/^\+?[0-9][0-9\-\s]{6,}$/';
    // Requerimiento
    public $expPartidaActual = '/^[0-9]+(\.[0-9]+)?$/';
    public $expCantidadMes = '/^\d{1,9}$/';
    public $expIdRequerimiento = '/^\d{1,10}$/';
    // Reporte
    public $expFormatoReporte = '/^(excel|pdf)$/';
    public $expNombreReporte = '/^[a-zA-Z0-9_\-]{1,50}$/';
    public $expIdDepReporte = '/^\d{1,10}$/';
    // Responsable (incluye UserModel)
    public $expNomRep = '/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s\.\'\-]{3,100}$/';
    public $expEmail = '/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/';
    public $expPassword = '/^[a-zA-Z0-9_\.\-]{4,}$/';
    public $expFechaISO = '/^\d{4}-\d{2}-\d{2}$/';
    public $expRolDescripcion = '/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑüÜ\s\.\,\-\_\/\(\)]{1,70}$/';
    public $expUsuario = '/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑüÜ_\.\-\s]{2,100}$/';
    // TasaBCV
    public $expTasaBCV = '/^\d{1,10}(\.\d{1,2})?$/';
    public $expFechaTasa = '/^\d{4}-\d{2}-\d{2}$/';

    // ===== MÉTODO PÚBLICO GENÉRICO =====
    /**
     * Valida un campo según tipo predefinido
     * @param string $tipo  Tipo de validación
     * @param mixed $valor  Valor a validar
     * @param array $opciones  Opcional: campo, mensajes custom, etc.
     * @return null|string  null si OK, mensaje de error si falla
     */
    public function validar(string $tipo, mixed $valor, array $opciones = []): ?string
    {
        return match ($tipo) {
            'nombre_dependencia'     => $this->valNombreDependencia($valor),
            'fecha_periodo'          => $this->valFechaPeriodo($valor, $opciones['campo'] ?? 'La fecha'),
            'anio_fiscal'            => $this->valAnioFiscal($valor),
            'nombre_producto'        => $this->valNombreProducto($valor),
            'precio'                 => $this->valPrecio($valor),
            'codigo_partida'         => $this->valCodigoPartida($valor),
            'descripcion_partida'    => $this->valDescripcionPartida($valor),
            'nombre_proveedor'       => $this->valNombreProveedor($valor),
            'descripcion_proveedor'  => $this->valDescripcionProveedor($valor),
            'telefono'               => $this->valTelefono($valor),
            'partida_actual'         => $this->valPartidaActual($valor),
            'cantidades'             => $this->valCantidades($valor),
            'id_requerimiento'       => $this->valIdRequerimiento($valor),
            'formato_reporte'        => $this->valFormatoReporte($valor),
            'nombre_reporte'         => $this->valNombreReporte($valor),
            'id_dep_reporte'         => $this->valIdDep($valor),
            'nombre_responsable'     => $this->valNombreResponsable($valor),
            'email'                  => $this->valEmail($valor),
            'password'               => $this->valPassword($valor),
            'fecha_asignacion'       => $this->valFechaAsignacion($valor),
            'descripcion_rol'        => $this->valDescripcionRol($valor),
            'usuario'                => $this->valUsuario($valor),
            'tasa'                   => $this->valTasa($valor),
            'fecha_registro'         => $this->valFechaRegistro($valor),
            default                  => "Tipo de validación desconocido: $tipo",
        };
    }

    // ===== MÉTODOS PRIVADOS DE VALIDACIÓN (prefijo val_ para evitar colisiones) =====

    // --- Dependencia ---
    private function valNombreDependencia($nombre): ?string
    {
        $nombre = trim((string)$nombre);
        if ($nombre === '') {
            return 'El nombre no puede estar vacío.';
        }
        if (!preg_match($this->expNombreDep, $nombre)) {
            return 'El nombre de la dependencia solo puede contener letras, números, espacios y los caracteres . - & ( ), entre 3 y 100 caracteres.';
        }
        return null;
    }

    // --- Periodo ---
    private function valFechaPeriodo($fecha, $campo = 'La fecha'): ?string
    {
        $fecha = trim((string)$fecha);
        if ($fecha === '') {
            return $campo . ' no puede estar vacía.';
        }
        if (!preg_match($this->expFechaPeriodo, $fecha)) {
            return $campo . ' debe tener el formato AAAA-MM-DD.';
        }
        $d = \DateTime::createFromFormat('Y-m-d', $fecha);
        if (!$d || $d->format('Y-m-d') !== $fecha) {
            return $campo . ' no es una fecha válida.';
        }
        return null;
    }

    private function valAnioFiscal($anio): ?string
    {
        $anio = trim((string)$anio);
        if ($anio === '') {
            return 'El año fiscal no puede estar vacío.';
        }
        if (!preg_match($this->expAnioFiscal, $anio)) {
            return 'El año fiscal debe ser un número de 4 dígitos (ej. 2026).';
        }
        if ((int)$anio < 2000 || (int)$anio > 2100) {
            return 'El año fiscal debe estar entre 1900 y 2100.';
        }
        return null;
    }

    // --- Productos/Partidas ---
    private function valNombreProducto($nombre): ?string
    {
        $nombre = trim((string)$nombre);
        if ($nombre === '') {
            return 'El nombre del producto no puede estar vacío.';
        }
        if (!preg_match($this->expNomItem, $nombre)) {
            return 'El nombre del producto solo puede contener letras, números, espacios y los caracteres . , - ( ) / %, entre 2 y 150 caracteres.';
        }
        return null;
    }

    private function valPrecio($precio): ?string
    {
        $precio = trim((string)$precio);
        if ($precio === '') {
            return 'El precio no puede estar vacío.';
        }
        if (!preg_match($this->expPrecio, $precio)) {
            return 'El precio debe ser un número positivo con hasta 2 decimales (ej. 5.50).';
        }
        if ((float)$precio <= 0) {
            return 'El precio debe ser mayor que cero.';
        }
        return null;
    }

    private function valCodigoPartida($codigo): ?string
    {
        $codigo = trim((string)$codigo);
        if ($codigo === '') {
            return 'El código de la partida no puede estar vacío.';
        }
        if (mb_strlen($codigo) > 10 || !preg_match($this->expCodPartida, $codigo)) {
            return 'El código de la partida debe contener solo dígitos y puntos, máximo 10 caracteres (ej. 401).';
        }
        return null;
    }

    private function valDescripcionPartida($descripcion): ?string
    {
        $descripcion = trim((string)$descripcion);
        if ($descripcion === '') {
            return 'La descripción de la partida no puede estar vacía.';
        }
        if (!preg_match($this->expDescripcionPartida, $descripcion)) {
            return 'La descripción de la partida solo puede contener texto (sin etiquetas HTML) de hasta 150 caracteres.';
        }
        return null;
    }

    // --- Proveedor ---
    private function valNombreProveedor($nombre): ?string
    {
        $nombre = trim((string)$nombre);
        if ($nombre === '') {
            return 'El nombre no puede estar vacío.';
        }
        if (!preg_match($this->expNombreProv, $nombre)) {
            return 'El nombre del proveedor solo puede contener letras, números, espacios y los caracteres . - & ( ), entre 3 y 100 caracteres.';
        }
        return null;
    }

    private function valDescripcionProveedor($descripcion): ?string
    {
        $descripcion = trim((string)$descripcion);
        if ($descripcion === '') {
            return null;
        }
        if (!preg_match($this->expDescripcionProv, $descripcion)) {
            return 'La descripción solo puede contener texto (sin etiquetas HTML) de hasta 150 caracteres.';
        }
        return null;
    }

    private function valTelefono($telefono): ?string
    {
        $telefono = trim((string)$telefono);
        if ($telefono === '') {
            return 'El teléfono no puede estar vacío.';
        }
        if (!preg_match($this->expTelefono, $telefono)) {
            return 'Formato de teléfono inválido. Solo se permiten dígitos, "+" al inicio y "-". Ej: +58-212-5551234';
        }
        return null;
    }

    // --- Requerimiento ---
    private function valPartidaActual($partida): ?string
    {
        $partida = trim((string)$partida);
        if ($partida === '') {
            return 'La partida actual no puede estar vacía.';
        }
        if (mb_strlen($partida) > 10 || !preg_match($this->expPartidaActual, $partida)) {
            return 'La partida actual debe contener solo dígitos y puntos, máximo 10 caracteres (ej. 401).';
        }
        return null;
    }

    private function valCantidades($cantidades): ?string
    {
        if (!is_array($cantidades)) {
            return 'La matriz de cantidades no es válida.';
        }
        foreach ($cantidades as $idProd => $meses) {
            if (!preg_match($this->expIdRequerimiento, (string)$idProd)) {
                return 'Identificador de producto inválido en la matriz de cantidades.';
            }
            if (!is_array($meses)) {
                return 'Estructura de cantidades inválida para el producto ' . $idProd . '.';
            }
            foreach ($meses as $mes => $valor) {
                if (!preg_match('/^\d{1,2}$/', (string)$mes) || (int)$mes < 1 || (int)$mes > 12) {
                    return 'Mes inválido (' . htmlspecialchars((string)$mes, ENT_QUOTES, 'UTF-8') . ') en la matriz de cantidades.';
                }
                $valor = trim((string)$valor);
                if ($valor === '') {
                    continue;
                }
                if (!preg_match($this->expCantidadMes, $valor)) {
                    return 'Cantidad inválida para el producto ' . $idProd . ', mes ' . $mes . '. Debe ser un entero sin decimales.';
                }
            }
        }
        return null;
    }

    private function valIdRequerimiento($id): ?string
    {
        if (!preg_match($this->expIdRequerimiento, (string)$id)) {
            return 'Identificador de requerimiento inválido.';
        }
        return null;
    }

    // --- Reporte ---
    private function valFormatoReporte($formato): ?string
    {
        if (!preg_match($this->expFormatoReporte, (string)$formato)) {
            return 'Formato de reporte inválido. Use excel o pdf.';
        }
        return null;
    }

    private function valNombreReporte($reporte): ?string
    {
        $reporte = trim((string)$reporte);
        if ($reporte === '') {
            return 'Debe seleccionar un reporte.';
        }
        if (!preg_match($this->expNombreReporte, $reporte)) {
            return 'Nombre de reporte inválido.';
        }
        return null;
    }

    private function valIdDep($idDep): ?string
    {
        if (!preg_match($this->expIdDepReporte, (string)$idDep)) {
            return 'Dependencia inválida.';
        }
        return null;
    }

    // --- Responsable ---
    private function valNombreResponsable($nombre): ?string
    {
        $nombre = trim((string)$nombre);
        if ($nombre === '') {
            return 'El nombre no puede estar vacío.';
        }
        if (!preg_match($this->expNomRep, $nombre)) {
            return 'El nombre del responsable solo puede contener letras, espacios, puntos, guiones y apóstrofes, entre 3 y 100 caracteres.';
        }
        return null;
    }

    private function valEmail($email): ?string
    {
        $email = strtolower(trim((string)$email));
        if ($email === '') {
            return 'El correo electrónico no puede estar vacío.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match($this->expEmail, $email)) {
            return 'El correo electrónico no es válido.';
        }
        return null;
    }

    private function valPassword($password): ?string
    {
        if ((string)$password === '') {
            return 'La contraseña no puede estar vacía.';
        }
        if (!preg_match($this->expPassword, $password)) {
            return 'La contraseña solo puede contener letras, números y los caracteres _ . - (mínimo 4 caracteres).';
        }
        return null;
    }

    private function valFechaAsignacion($fecha): ?string
    {
        $fecha = trim((string)$fecha);
        if ($fecha === '') {
            return 'La fecha de asignación no puede estar vacía.';
        }
        if (!preg_match($this->expFechaISO, $fecha)) {
            return 'La fecha de asignación debe tener el formato AAAA-MM-DD.';
        }
        $d = \DateTime::createFromFormat('Y-m-d', $fecha);
        if (!$d || $d->format('Y-m-d') !== $fecha) {
            return 'La fecha de asignación no es una fecha válida.';
        }
        return null;
    }

    private function valDescripcionRol($descripcion): ?string
    {
        $descripcion = trim((string)$descripcion);
        if ($descripcion === '') {
            return 'La descripción del rol no puede estar vacía.';
        }
        if (!preg_match($this->expRolDescripcion, $descripcion)) {
            return 'La descripción del rol solo puede contener letras, números, espacios y los caracteres . , - _ / ( ), máximo 70 caracteres.';
        }
        return null;
    }

    // --- UserModel ---
    private function valUsuario($usuario): ?string
    {
        $usuario = trim((string)$usuario);
        if ($usuario === '') {
            return 'El usuario no puede estar vacío.';
        }
        if (!preg_match($this->expUsuario, $usuario)) {
            return 'El usuario solo puede contener letras, números, espacios y _ . - (2-100 caracteres).';
        }
        return null;
    }

    // --- TasaBCV ---
    private function valTasa($tasa): ?string
    {
        $tasa = trim((string)$tasa);
        if ($tasa === '') {
            return 'La tasa no puede estar vacía.';
        }
        if (!preg_match($this->expTasaBCV, $tasa)) {
            return 'La tasa debe ser un número positivo con hasta 2 decimales (ej. 69.00).';
        }
        if ((float)$tasa <= 0) {
            return 'La tasa debe ser mayor que cero.';
        }
        return null;
    }

    private function valFechaRegistro($fecha): ?string
    {
        $fecha = trim((string)$fecha);
        if ($fecha === '') {
            return 'La fecha no puede estar vacía.';
        }
        if (!preg_match($this->expFechaTasa, $fecha)) {
            return 'La fecha debe tener el formato AAAA-MM-DD.';
        }
        $d = \DateTime::createFromFormat('Y-m-d', $fecha);
        if (!$d || $d->format('Y-m-d') !== $fecha) {
            return 'La fecha no es una fecha válida.';
        }
        return null;
    }
}