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

    <!-- Step wizard -->
    <div class="card">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h4 class="mb-0">Carga de Requerimientos - Partida <span id="titulo-partida">401</span></h4>
        </div>
        <div class="card-body">
            <form id="form-registro">
                <input type="hidden" id="id_req" name="id_req" value="<?php echo $id_req; ?>">
                <input type="hidden" id="partida_actual" name="partida_actual" value="401">

                <div class="table-wrap">
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
            <div class="flex gap-10" style="margin-top:8px;justify-content:flex-end;">
                <a href="?url=requerimiento&type=main" class="btn btn-outline">Cancelar</a>
            </div>
        </div>
    </div>
</div>

</form>

<?php include_once 'app/view/layout/foot.php'; ?>

