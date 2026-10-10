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
    <div class="topbar-actions">
        <?php if((($_SESSION['rol'] ?? '') !== "Administrador") && !empty($prevReq) && !empty($perAct)){?>
        <a href="?url=requerimiento&type=register"  class="btn btn-success btn-sm">&#43; Registrar</a>
        <?php }?>
    </div>
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
<div class="alert-banner alert-<?php echo $periodStatus; ?>">
        <div class="alert-banner-left">
            <?php if ($periodStatus === 'expired'): ?>
                <div class="alert-banner-title">⚠️ <strong>Periodo de envío finalizado</strong></div>
                <div class="alert-banner-sub">El tiempo límite para enviar requerimientos ha concluido</div>
            <?php else: ?>
                <div class="alert-banner-title">&#128197; Fecha límite de envío: <?php echo $timeLeft; ?></div>
                <div class="alert-banner-sub">Complete todas las partidas de su dependencia antes del cierre</div>
            <?php endif; ?>
        </div>
        <div class="alert-banner-badge">  <?php echo $dias > 0 ? $dias : '0' ?> <span>días</span></div>
</div>

<div class="page-body">
    <div class="card">
        <div class="card-header">
            <span class="card-title">Requerimientos Consolidados</span>
        <div class="row mb-3"> 
            <!-- si es administrador carga las opciones del select para seleccionar la dependencia a revisar -->
            <!-- si seleciona todos, entoces va a buscar a todas las dependencias -->
        <?php if(($_SESSION['rol'] ?? '') == "Administrador"){?>
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
            <div class="col-md-4" style="display:flex; align-items:flex-end; padding-bottom: 8px;">
                <label class="field-switch">
                    <input type="checkbox" id="modalViewToggle">
                    <span class="switch-slider"></span>
                </label>
                <span style="font-size: 13px; color: var(--text-muted); margin: 0 8px;">Ver cantidades en modal</span>
            </div>
            <button id="btn-ver-cantidades" class="btn btn-blue" style="display: none;">
                <i class="fa-solid fa-chart-column" aria-hidden="true"></i> Ver Cantidades
            </button>
        <?php } ?>
        </div>
        </div>
        <div class="card-body">
            <div class="table-wrap">
            <!-- id_req se almacena en $_SESSION y se expone solo via JS variable, no en HTML -->
            <table id="tablaMain" >
                    <thead>
                        <tr>
                            <th>dependencias</th>
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
                    <div id='contenedor-acciones'>
                        <button  id="btn-modificar" class="btn btn-success" style="display: none;">
                            Modificar
                        </button>
                        <button  id="btn-eliminar" class="btn btn-danger" style="display: none;">
                            Eliminar
                        </button>
                        <button  id="btn-cambiar-estado" class="btn btn-primary" style="display: none;">
                            Enviar Definitivo
                        </button>
                    </div>
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

<script>
    const esAdmin = '<?php echo $_SESSION["rol"] ?? ""; ?>' == 'Administrador';
    let idReq = <?php echo json_encode($_SESSION['id_req'] ?? 0); ?>;
</script>