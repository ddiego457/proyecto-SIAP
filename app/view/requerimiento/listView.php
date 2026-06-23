<?php
$pageTitle = "Requerimientos — Consulta";
include_once 'app/view/layout/head.php';

$result = $result ?? [];
?>
<div class="topbar">
    <div class="topbar-title">Requerimientos POA — Consulta</div>
    <div class="topbar-actions">
        <a href="?url=requerimiento&type=register"class="btn btn-success btn-sm">&#43; Registrar</a>
        <a href="?url=requerimiento&type=main"class="btn btn-outline btn-sm">Gestionar</a>

    </div>
</div>

<div class="page-body">
    <div class="card">
        <div class="card-header">
            <span class="card-title">Requerimientos registrados</span>
            <span class="badge badge-blue"><?php echo count($result); ?> registros</span>
        </div>
        <div class="card-body">
            <div class="table-wrap">
                <table class="siap-table" style="font-size:12.5px;">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Fecha Envío</th>
                            <th>Año Fiscal</th>
                            <th>Producto / Servicio</th>
                            <th class="num">Cant./Mes</th>
                            <th class="num">Cant. Total</th>
                            <th class="num">P.U. USD</th>
                            <th class="num">Total USD</th>
                            <th class="num">Total BCV (Bs.)</th>
                            <th>Unidad</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($result as $row): ?>
                        <tr>
                            <td><?php echo $row['id_requer']; ?></td>
                            <td><?php echo $row['fecha_envio']; ?></td>
                            <td style="white-space:nowrap;">
                                <?php echo $row['periodo_ini'] . ' / ' . $row['periodo_fin']; ?>
                            </td>
                            <td><?php echo $row['nombre_prod']; ?></td>
                            <td class="num"><?php echo number_format($row['cantidad_por_mes'], 2); ?></td>
                            <td class="num"><?php echo number_format($row['cantidad_total'], 2); ?></td>
                            <td class="num"><?php echo number_format($row['pre_uni_usd'], 4); ?></td>
                            <td class="num"><?php echo number_format($row['total_usd'], 2); ?></td>
                            <td class="num" style="font-weight:700;color:var(--blue);">
                                <?php echo number_format($row['total_BCV'], 2); ?>
                            </td>
                            <td><?php echo $row['unidad_medida']; ?></td>
                            <td>
                                <?php if($row['estado'] == 1): ?>
                                <span class="badge badge-success">Activo</span>
                                <?php else: ?>
                                <span class="badge badge-danger">Inactivo</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($result)): ?>
                        <tr><td colspan="11" style="text-align:center;padding:32px;color:var(--text-muted);">
                            Sin requerimientos registrados.
                        </td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php include_once 'app/view/layout/foot.php'; ?>

