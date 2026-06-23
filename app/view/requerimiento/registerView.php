<?php
$pageTitle = "Registrar — Requerimiento";
$jsFile    = "requerimiento.js";
include_once 'app/view/layout/head.php';
?>

<!-- TOPBAR -->
<div class="topbar">
    <div class="topbar-title">&#128203; Registrar Requerimiento POA</div>
    <div class="topbar-actions">
        <a href="?url=requerimiento&type=main" class="btn btn-outline btn-sm">&#8592; Volver</a>
    </div>
</div>

<div class="page-body">

    <!-- Alerta de fecha límite -->
    <div class="alert-banner alert-warning">
        <div class="alert-banner-left">
            <div class="alert-banner-title">&#128197; Fecha límite de envío: <?php echo date('d \d\e F \d\e Y'); ?></div>
            <div class="alert-banner-sub">Complete todas las partidas de su dependencia antes del cierre</div>
        </div>
        <div class="alert-banner-badge">12 <span>días</span></div>
    </div>

    <!-- Step wizard -->
    <div class="container-fluid mt-4">
    <div class="card">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h4 class="mb-0">Carga de Requerimientos - Partida <span id="titulo-partida">401</span></h4>
            <span class="badge bg-warning text-dark">Modo Borrador</span>
        </div>
        <div class="card-body">
            <form id="form-registro">
                <input type="hidden" id="id_req" name="id_req" value="<?php echo $id_req; ?>">
                <input type="hidden" id="partida_actual" name="partida_actual" value="401">

                <div class="table-responsive">
                    <table id="tabla-registro" class="table table-striped table-hover table-bordered w-100">
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
                    <button type="button" id="btn-enviar-final" class="btn btn-success d-none">
                        Enviar Definitivo a Coordinación
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
                <div class="flex gap-10" style="margin-top:8px;justify-content:flex-end;">
                    <a href="?url=requerimiento&type=main" class="btn btn-outline">Cancelar</a>
                    <button type="button" class="btn btn-outline">&#128190; Guardar borrador</button>
                    <button type="submit" class="btn btn-blue">&#10148; Vista previa y enviar</button>
                </div>
            </div>
        </div>

    </form>
</div>

<?php include_once 'app/view/layout/foot.php'; ?>

