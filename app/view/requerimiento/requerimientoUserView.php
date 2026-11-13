<?php
$pageTitle = "Requerimientos - Gestionar";
$jsFile    = "requerimiento.js";

$prevReq = $prevReq ?? false;
$perAct = $perAct ?? false;
$timeLeft = $timeLeft ?? '';
$dias = $dias ?? 0;
$dependencias = $dependencias ?? [];

include_once 'app/view/layout/head.php';
?>

<!-- TOPBAR -->
<!-- si no es administrador y no se ha hecho un requerimiento antes en ese año activo, entonces se habilita -->
<!-- el boton de registro -->
<div class="topbar">
    <div class="topbar-title">Requerimientos</div>
    <?php include_once 'app/view/layout/userMenu.php'; ?>
</div>

<!-- acomodar para que se vea mejor -->
<!-- la variable atrapa la fecha fin y dias trapa los dias faltantes para la fecha fin -->
<?php
// Determinar el estado del banner según el periodo
$periodStatus = 'active'; // default
if (isset($timeLeft) && is_array($timeLeft) && $timeLeft[2] === false) {
    $periodStatus = 'expired';
} elseif (isset($timeLeft) && is_numeric($timeLeft) && $timeLeft > 0) {
    $periodStatus = 'active';
} elseif (isset($dias) && $dias <= 0) {
    $periodStatus = 'expired';
}
?>
<div class="page-body">
    <div class="alert-banner alert-info alert-<?php echo $periodStatus; ?>" id="alertBanner">
        <div class="alert-banner-left">
            <?php if ($periodStatus === 'expired'): ?>
                <div class="alert-banner-title">⚠️ <strong>Periodo de registro finalizado</strong></div>
                <div class="alert-banner-sub">El tiempo límite para registrar requerimientos ha concluido</div>
            <?php else: ?>
                <div class="alert-banner-title">&#128197; Fecha límite de registro: <?php echo $timeLeft; ?></div>
                <div class="alert-banner-sub">Complete todas las partidas de su dependencia antes del cierre</div>
            <?php endif; ?>
        <div class="alert-banner-title"> Año activo: <strong> <?php  echo $anioFiscal  ?> </strong> </div>
        </div>
        <div class="alert-banner-badge">  <?php echo $dias > 0 ? $dias : '0' ?> <span>días</span></div>
    </div>

    <div class="card">
        <!-- La clase card-header-with-search solo va en la cabecera con
             buscador (administrador): agrupa botones + buscador contra el
             borde derecho y controla el envoltorio en pantallas estrechas.
             Sin buscador no hace falta, se conserva el layout original. -->
        <div class="card-header<?php echo $this->esAdmin() ? ' card-header-with-search' : ''; ?>">
            <span class="card-title">Requerimientos Consolidados</span>
            <!-- Acciones principales: todos los botones en una sola fila.
                 Van ANTES del buscador para que ninguno quede a su derecha. -->
            <div class="card-header-actions">
                <?php if(!$this->esAdmin() && !empty($prevReq) && !empty($perAct)){?>
                <button type="button" id="btnNuevoRegistro" class="btn btn-success btn-sm">Registrar</button>
                <?php }?>
                <button type="button" id="btn-modificar" class="btn btn-success btn-sm" style="display: none;">
                    Modificar
                </button>
                <button type="button" id="btn-eliminar" class="btn btn-danger btn-sm" style="display: none;">
                    Eliminar
                </button>
                <button type="button" id="btn-cambiar-estado" class="btn btn-primary btn-sm" style="display: none;">
                    Registrar Definitivo
                </button>
                <?php if($this->esAdmin()){?>
                <button type="button" id="btn-vista-partidas" class="btn btn-blue btn-sm" style="display: none;">
                    <i class="fa-solid fa-table-list" aria-hidden="true"></i> Totales POA
                </button>
                <?php } ?>
                <button type="button" id="btn-ver-cantidades" class="btn btn-blue btn-sm" style="display: none;">
                    <i class="fa-solid fa-chart-column" aria-hidden="true"></i> Ver Cantidades
                </button>
                <!-- "Ver resumen": visible para todos los roles (Admin y Usuario).
                     align-items:baseline reproduce la alineación original: el
                     offset top:10px de .switch-slider está calibrado para ella. -->
                <div id="ver-resumen-wrap" style="display: inline-flex; gap: 8px; align-items: baseline;">
                    <label class="field-switch">
                        <input type="checkbox" id="modalViewToggle">
                        <span class="switch-slider"></span>
                    </label>
                    <span id="verCant" style="font-size: 13px; color: var(--text-muted);">Ver resumen</span>
                </div>
            </div>
            <!-- Selector de dependencia: solo administrador.
                 Va DESPUÉS de las acciones para que ningún botón quede a su derecha. -->
            <?php if($this->esAdmin()){?>
            <div class="row mb-3">
                <div class="col-md-4">
                    <label>Seleccionar Dependencia:</label>
                    <input list="select-dep-list" id="select-dependencia" class="field-input" placeholder="Buscar dependencia...">
                    <input type="hidden" id="id_dep_seleccionado" value="">
                    <datalist id="select-dep-list">
                        <option value="todos" data-id_dep="todos"></option>
                        <?php foreach($dependencias as $dep): ?>
                            <option value="<?php echo htmlspecialchars($dep['nom_dep']);?>" data-id_dep="<?php echo $dep['id_dep']; ?>"></option>
                        <?php endforeach; ?>
                    </datalist>
                </div>
            </div>
            <?php } ?>
        </div>
        <div class="card-body">
            <div class="table-wrap">
            <!-- id_req se almacena en $_SESSION y se expone solo via JS variable, no en HTML -->
            <table id="tablaMain" >
                    <thead>
                        <tr>
                            <th>Dependencias</th>
                            <th>Partida</th>
                            <th>Producto</th>
                            <th>Ene</th>
                            <th>Feb</th>
                            <th>Mar</th>
                            <th>Abr</th>
                            <th>May</th>
                            <th>Jun</th>
                            <th>Jul</th>
                            <th>Ago</th>
                            <th>Sep</th>
                            <th>Oct</th>
                            <th>Nov</th>
                            <th>Dic</th>
                            <th class="bg-primary">Precio del Producto</th>
                            <th class="bg-success">Total Físico</th>
                            <th class="bg-success">Total Usd</th>
                            <th class="bg-info">Total BS</th>
                        </tr>
                    </thead>
                        <tbody>
                        </tbody>
                        <!-- si es admin, entomces cargara el footer, que contiene los totales de los precios -->
                        <!-- colspan posiciona el th en la posicion 16 -->
                            <tfoot>
                                <tr>
                                    <th colspan="15" style="text-align:right">Gran Total:</th>
                                    <th></th> <!-- Total USD -->
                                    <th></th> <!-- Total BS -->
                                    <th></th> <!-- Total BS -->
                                    <th></th> <!-- Acciones -->
                                </tr>
                            </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include_once 'app/view/layout/foot.php'; ?>

<!-- MODAL CANTIDADES -->
<div id="modalCantidades" class="modal-backdrop" style="display:none;">
    <div class="modal-panel" style="max-width: min(92vw, 1300px); max-height: 88vh;">
        <div class="modal-header" id="modalHeaderCantidades">
            <h3>Cantidades por Mes</h3>
            <div class="flex gap-10">
                <button type="button" id="btn-modificar-modal" class="btn btn-success btn-sm" style="display: none;" disabled>
                    Modificar
                </button>
                <button id="btnCerrarModal" class="modal-close">&times;</button>
            </div>
        </div>
        <div class="modal-body" id="modalBodyCantidades">
            <table id="tablaModalCantidades" class="month-table siap-table" style="width:100%">
                <thead>
                    <tr>
                        <th>Dependencia</th>
                        <th>Producto</th>
                        <th>Ene</th>
                        <th>Feb</th>
                        <th>Mar</th>
                        <th>Abr</th>
                        <th>May</th>
                        <th>Jun</th>
                        <th>Jul</th>
                        <th>Ago</th>
                        <th>Sep</th>
                        <th>Oct</th>
                        <th>Nov</th>
                        <th>Dic</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<!-- MODAL REGISTRO DE REQUERIMIENTO -->
<div id="modalRegistro" class="modal-backdrop" style="display:none; z-index:210;">
    <div class="modal-panel" style="max-width: min(96vw, 1350px); max-height: 88vh;">
        <div class="modal-header">
            <h3>&#128203; Registrar Requerimiento &mdash; Partida <span id="titulo-partida">401</span></h3>
            <button id="btnCerrarModalRegistro" class="modal-close">&times;</button>
        </div>
        <div class="modal-body" style="overflow:auto;">
            <?php if (isset($periodExpired) && $periodExpired): ?>
                <div class="alert-banner alert-danger" style="margin-bottom:14px;">
                    <div class="alert-banner-title"><strong>&#9888;&#65039; Per&iacute;odo vencido</strong></div>
                    <div class="alert-banner-sub">
                        El per&iacute;odo de carga de requerimientos ha finalizado. Espere a que se abra el pr&oacute;ximo per&iacute;odo para realizar sus solicitudes.
                    </div>
                </div>
            <?php endif; ?>
            <?php if (isset($prevReqError) && $prevReqError): ?>
                <div class="alert-banner alert-danger" style="margin-bottom:14px;">
                    <div class="alert-banner-title"><strong>&#9888;&#65039; Ya existe un requerimiento</strong></div>
                    <div class="alert-banner-sub">
                        Ya tiene un requerimiento previo activo para este per&iacute;odo.
                    </div>
                </div>
            <?php endif; ?>

            <form id="form-registro">
                <input type="hidden" id="partida_actual" name="partida_actual" value="401">

                <div class="table-wrapper">
                    <table id="tabla-registro" class="siap-table">
                        <thead class="table-dark">
                            <tr>
                                <th>Producto</th>
                                <th>Ene</th><th>Feb</th><th>Mar</th><th>Abr</th><th>May</th><th>Jun</th>
                                <th>Jul</th><th>Ago</th><th>Sep</th><th>Oct</th><th>Nov</th><th>Dic</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>

                <div class="mt-4 text-end">
                    <button type="button" id="btn-guardar" class="btn btn-primary">
                        Guardar y Avanzar a Siguiente Partida
                    </button>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" id="btnCerrarModalRegistro2" class="btn btn-outline">Cancelar</button>
        </div>
    </div>
</div>

<script>
    const esAdmin = <?php echo $this->esAdmin() ? 'true' : 'false'; ?>;
    let idReq = <?php echo json_encode($_SESSION['id_req'] ?? 0); ?>;
</script>