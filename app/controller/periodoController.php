<?php


use EquipoSiap\Siap\model\periodoModel;
$this->requireLogin();
$this->requireModule('periodo');

$object = new periodoModel();

if (!function_exists('sendJsonResponse')) {
    function sendJsonResponse($payload)
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload);
        exit;
    }
}

if (isset($_GET['type'])) {
    if ($_GET['type'] == 'list') {
        $result = $object->getAll();
        include 'app/view/periodo/listView.php';
    } elseif ($_GET['type'] == 'register') {
        if (isset($_POST['registerPeriodo'])) {
            if (isset($_POST['fecha_inicio']) && isset($_POST['fecha_fin']) && isset($_POST['anio_fiscal_id'])) {
                $fechaInicio = $_POST['fecha_inicio'];
                $fechaFin = $_POST['fecha_fin'];
                $anioFiscalId = (int)$_POST['anio_fiscal_id'];

                $errorValidacion = $this->validarFechaPeriodo($fechaInicio, 'La fecha de inicio');
                if ($errorValidacion === null) {
                    $errorValidacion = $this->validarFechaPeriodo($fechaFin, 'La fecha de fin');
                }
                if ($errorValidacion !== null) {
                    sendJsonResponse(['success' => false, 'message' => $errorValidacion]);
                }

                if (!$object->isValidFiscalYearRange($anioFiscalId, $fechaInicio, $fechaFin)) {
                    // Añadir diagnóstico para entender por qué falla la validación
                    $startYear = null;
                    $endYear = null;
                    try { $sd = new \DateTime($fechaInicio); $startYear = $sd->format('Y'); } catch (\Exception $e) { }
                    try { $ed = new \DateTime($fechaFin); $endYear = $ed->format('Y'); } catch (\Exception $e) { }
                    $anioInfo = $object->getAnioFiscalById($anioFiscalId);
                    sendJsonResponse([
                        'success' => false,
                        'message' => 'Fechas inválidas: la fecha de inicio debe ser anterior o igual a la fecha fin, y ambas deben corresponder al año fiscal seleccionado.',
                        'diagnostic' => [
                            'fecha_inicio' => $fechaInicio,
                            'fecha_fin' => $fechaFin,
                            'startYear' => $startYear,
                            'endYear' => $endYear,
                            'fiscalYearRecord' => $anioInfo
                        ]
                    ]);
                }

                // Evitar solapamiento de periodos en el mismo año fiscal
                if ($object->existsOverlapping($fechaInicio, $fechaFin, $anioFiscalId)) {
                    sendJsonResponse(['success' => false, 'message' => 'Ya existe un periodo que se solapa con esas fechas en el mismo año fiscal.']);
                }

                // Si ya hay un periodo activo en ese año, se desactivará automáticamente (lo hace el modelo)
                // Avisamos al usuario para que lo sepa
                $yaHabiaActivo = $object->hasActivePeriodForYear($anioFiscalId);

                $result = $object->add($fechaInicio, $fechaFin, $anioFiscalId);
                $msg = $result ? 'Periodo registrado' : 'Error al guardar el periodo (duplicado o error BD).';
                if ($yaHabiaActivo && $result) {
                    $msg .= ' (Se desactivó el periodo activo anterior de ese año fiscal).';
                }
                $payload = ['success' => (bool)$result, 'message' => $msg];
                if ($result) $payload['redirect'] = '?url=periodo&type=main';
                sendJsonResponse($payload);
            }
        }
        $anioFiscales = $object->getAniosFiscalesActivos();
        include 'app/view/periodo/registerView.php';
    } elseif ($_GET['type'] == 'main') {
        if (isset($_POST['getAll'])) {
            sendJsonResponse($object->getAll());
        }
        if (isset($_POST['activateItem'])) {
            sendJsonResponse(['success' => $object->activate((int)$_POST['idItem'])]);
        }
        if (isset($_POST['inactivateItem'])) {
            sendJsonResponse(['success' => $object->inactivate((int)$_POST['idItem'])]);
        }

        if (isset($_POST['updateItem'])) {
            $idItem = (int)$_POST['idItem'];
            $fechaInicio = $_POST['fecha_inicio'];
            $fechaFin = $_POST['fecha_fin'];
            $anioFiscalId = (int)$_POST['anio_fiscal_id'];

            $errorValidacion = $this->validarFechaPeriodo($fechaInicio, 'La fecha de inicio');
            if ($errorValidacion === null) {
                $errorValidacion = $this->validarFechaPeriodo($fechaFin, 'La fecha de fin');
            }
            if ($errorValidacion !== null) {
                sendJsonResponse(['success' => false, 'message' => $errorValidacion]);
            }

            if (!$object->isValidFiscalYearRange($anioFiscalId, $fechaInicio, $fechaFin)) {
                sendJsonResponse(['success' => false, 'message' => 'Fechas inválidas: la fecha de inicio debe ser anterior o igual a la fecha fin, y ambas deben corresponder al año fiscal seleccionado.']);
            }

            // Evitar solapamiento (excluyendo el propio periodo que se edita)
            if ($object->existsOverlapping($fechaInicio, $fechaFin, $anioFiscalId, $idItem)) {
                sendJsonResponse(['success' => false, 'message' => 'Ya existe un periodo que se solapa con esas fechas en el mismo año fiscal.']);
            }

            // Si se cambia a un año fiscal que ya tiene periodo activo, se desactivará (lo hace el modelo en add, pero update no lo hace)
            // Para consistencia, si el periodo que se edita va a quedar activo y hay otro activo en ese año, avisamos
            // Nota: el modelo no tiene lógica de auto-desactivar en update, solo en add y activate
            $result = $object->update($idItem, $fechaInicio, $fechaFin, $anioFiscalId);
            sendJsonResponse(['success' => $result, 'message' => $result ? 'Periodo actualizado' : 'Error al actualizar el periodo.']);
        }

        // ─── CRUD de Años Fiscales (gestionado desde el modal en Periodos) ───
        if (isset($_POST['getAllAniosFiscales'])) {
            sendJsonResponse($object->getAllAniosFiscales());
        }

        if (isset($_POST['registerAnioFiscal'])) {
            $anio = isset($_POST['anio_fiscal']) ? trim((string)$_POST['anio_fiscal']) : '';
            $errorValidacion = $this->validarAnioFiscal($anio);
            if ($errorValidacion !== null) {
                sendJsonResponse(['success' => false, 'message' => $errorValidacion]);
            }
            // Evitar duplicados por año
            if ($object->existsAnioFiscalByAnio($anio)) {
                sendJsonResponse(['success' => false, 'message' => 'Ya existe un año fiscal con ese valor.']);
            }
            // El modelo crea el año fiscal activo y desactiva los demás automáticamente
            $result = $object->addAnioFiscal($anio);
            sendJsonResponse(['success' => (bool)$result, 'message' => $result ? 'Año fiscal registrado y activado' : 'Error al registrar (duplicado o error BD).']);
        }

        if (isset($_POST['updateAnioFiscal'])) {
            $idItem = (int)$_POST['idItem'];
            $anio = isset($_POST['anio_fiscal']) ? trim((string)$_POST['anio_fiscal']) : '';
            $errorValidacion = $this->validarAnioFiscal($anio);
            if ($errorValidacion !== null) {
                sendJsonResponse(['success' => false, 'message' => $errorValidacion]);
            }
            // Evitar duplicados por año (excluyendo el propio registro)
            if ($object->existsAnioFiscalByAnio($anio, $idItem)) {
                sendJsonResponse(['success' => false, 'message' => 'Ya existe un año fiscal con ese valor.']);
            }
            // El estado activo ya no se envía desde el formulario de edición; se conserva el actual
            $estado = isset($_POST['estado']) ? 1 : null;
            $result = $object->updateAnioFiscal($idItem, $anio, $estado);
            sendJsonResponse(['success' => (bool)$result, 'message' => $result ? 'Año fiscal actualizado' : 'Error al actualizar el año fiscal.']);
        }

        if (isset($_POST['activateAnioFiscal'])) {
            $result = $object->activateAnioFiscal((int)$_POST['idItem']);
            sendJsonResponse(['success' => (bool)$result, 'message' => $result ? 'Año fiscal activado' : 'Error al activar el año fiscal.']);
        }

        if (isset($_POST['inactivateAnioFiscal'])) {
            $result = $object->inactivateAnioFiscal((int)$_POST['idItem']);
            sendJsonResponse(['success' => (bool)$result, 'message' => $result ? 'Año fiscal inhabilitado' : 'Error al inhabilitar el año fiscal.']);
        }

        $anioFiscales = $object->getAniosFiscalesActivos();
        include 'app/view/periodo/userView.php';
    } else {
        echo "Error: Tipo de vista no valido.";
    }
} else {
    // Endpoint para obtener periodo activo
    if (isset($_GET['type']) && $_GET['type'] === 'current') {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($object->getLatestActive());
        die();
    }
    include 'app/view/welcomeView.php';
}

?>
