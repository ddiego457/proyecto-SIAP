<?php
$pageTitle = "Requerimientos - Gestionar";
$jsFile    = "requerimiento.js";
include_once 'app/view/layout/head.php';
?>

<!-- TOPBAR -->
<div class="topbar">
    <div class="topbar-title">&#128203; Requerimientos POA</div>
    <div class="topbar-actions">
        <?php if($_SESSION['rol'] !== "Administrador" && $rek){?>
        <a href="?url=requerimiento&type=register"  class="btn btn-success btn-sm">&#43; Registrar</a>
        <?php }?>
    </div>
</div>

<div class="alert-banner alert-warning">
        <div class="alert-banner-left">
            <div class="alert-banner-title">&#128197; Fecha límite de envío: <?php echo (string)$tl; ?></div>
            <div class="alert-banner-sub">Complete todas las partidas de su dependencia antes del cierre</div>
        </div>
        <div class="alert-banner-badge">  $dias <span>días</span></div>
    </div>

<div class="page-body">
    <div class="card">
        <div class="card-header">
            <span class="card-title">Requerimientos Consolidados</span>
        </div>
        <div class="card-body">
            <div class="table-wrap">
                <table id="tablaMain" class="siap-table" style="width:100%">
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
                    </table>
            </div>
            <button type="button" id="btn-enviar-final" class="btn btn-success d-none">
                Enviar Definitivo a Coordinación
            </button>
        </div>
    </div>
</div>


<?php include_once 'app/view/layout/foot.php'; ?>
