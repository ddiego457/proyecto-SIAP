<?php
$pageTitle = "Requerimientos - Gestionar";
$jsFile    = "requerimiento.js";
include_once 'app/view/layout/head.php';
?>

<!-- TOPBAR -->
<!-- si no es administrador y no se ha hecho un requerimiento antes en ese año activo, entonces se habilita -->
<!-- el boton de registro -->
<div class="topbar">
    <div class="topbar-title">&#128203; Requerimientos</div>
    <div class="topbar-actions">
        <?php if($_SESSION['rol'] !== "Administrador" && $prevReq){?>
        <a href="?url=requerimiento&type=register"  class="btn btn-success btn-sm">&#43; Registrar</a>
        <?php }?>
    </div>
</div>

<!-- acomodar para que se vea mejor -->
<!-- la variable atrapa la fecha fin y dias trapa los dias faltantes para la fecha fin -->
<div class="alert-banner alert-warning">
        <div class="alert-banner-left">
            <div class="alert-banner-title">&#128197; Fecha límite de envío: <?php echo $timeLeft; ?></div>
            <div class="alert-banner-sub">Complete todas las partidas de su dependencia antes del cierre</div>
        </div>
        <div class="alert-banner-badge">  <?php echo $dias ?> <span>días</span></div>
</div>

<div class="page-body">
    <div class="card">
        <div class="card-header">
            <span class="card-title">Requerimientos Consolidados</span>
        <div class="row mb-3"> 
            <!-- si es administrador carga las opciones del select para seleccionar la dependencia a revisar -->
            <!-- si seleciona todos, entoces va a buscar a todas las dependencias -->
        <?php if($_SESSION['rol'] == "Administrador"){?>
            <div class="col-md-4">
                <label>Seleccionar Dependencia:</label>
                <select id="select-dependencia" class="form-control">
                    <option value="">-- Seleccione una dependencia --</option>
                    <option value="todos">TODOS LOS REQUERIMIENTOS (Consolidado)</option>
                    <?php foreach($dependencias as $dep): ?>
                        <option value="<?php echo $dep['id_dep']; ?>"><?php echo $dep['nom_dep']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php } ?>
        </div>
        </div>
        <div class="card-body">
            <div class="table-wrap">
            <input type="hidden" id="id_req" name="id_req" value="<?php echo $idReq; ?>">
            <!-- class="siap-table" esta clase que va dentro de la tabla oculta los datos totales del footer. hay que acomodarlo-->
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
                            <th class="bg-primary">Total Físico</th>
                            <th class="bg-success">SubTotal Usd</th>
                            <th class="bg-success">Total Usd</th>
                            <th class="bg-info">Total BS</th>
                        </tr>
                    </thead>
                        <tbody>
                        </tbody>
                        <!-- si es admin, entomces cargara el footer, que contiene los totales de los precios -->
                        <!-- colspan posiciona el th en la posicion 16 -->
                        <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'Administrador'): ?>
                            <tfoot>
                                <tr>
                                    <th colspan="16" style="text-align:right">Gran Total:</th>
                                    <th></th> <!-- Total USD -->
                                    <th></th> <!-- Total BS -->
                                    <th></th> <!-- Acciones -->
                                </tr>
                            </tfoot>
                        <?php endif; ?>
                    </table>
                    <div id='contenedor-acciones'>
                        <button  id="btn-enviar-final" class="btn btn-success" style="display: none;">
                            Modificar
                        </button>
                        <button  id="btn-cambiar-estado" class="btn" style="display: none;">
                            Enviar Definitivo
                        </button>
                    </div>
            </div>
        </div>
    </div>
</div>
<!-- este ecript atrapa en una variable el rol del usuario que ha entrado para usarlo en el archivo requerimiento.js -->
<script>
    const esAdmin = '<?php echo $_SESSION["rol"]; ?>' == 'Administrador';
</script>


<?php include_once 'app/view/layout/foot.php'; ?>
