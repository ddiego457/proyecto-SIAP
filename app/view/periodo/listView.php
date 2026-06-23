<?php
$pageTitle = "Periodos — Lista";
include_once 'app/view/layout/head.php';

$result = $result ?? [];
?>

<div class="topbar">
    <div class="topbar-title">Periodos Consulta</div>
    <div class="topbar-actions">
        <a href="?url=periodo&type=register" class="btn btn-success btn-sm">&#43; Registrar</a>
        <a href="?url=periodo&type=main"     class="btn btn-outline btn-sm">Gestionar</a>
    </div>
</div>

<div class="page-body">
    <div class="card">
        <div class="card-header">
            <span class="card-title">Periodos</span>
            <span class="badge badge-blue"><?php echo count($result); ?> registros</span>
        </div>
        <div class="card-body">
            <div class="table-wrap">
                <table class="siap-table">
                    <thead><tr><th>ID</th><th>Año Fiscal</th><th>Fecha Inicio</th><th>Fecha Fin</th><th>Estado</th></tr></thead>
                    <tbody>
                        <?php foreach ($result as $row): ?>
                        <tr>
                            <td><?php echo $row['id_periodo'] ?? '—'; ?></td>
                            <td><?php echo htmlspecialchars($row['anio_fiscal'] ?? ($row['anio'] ?? '—'), ENT_QUOTES, 'UTF-8'); ?></td>

                            <?php
                                $fechaInicio = $row['fecha_inicio'] ?? ($row['per_ini'] ?? ($row['periodo_activo'] ?? null));
                                $fechaFin    = $row['fecha_fin'] ?? ($row['per_fin'] ?? ($row['periodo_inactivo'] ?? null));
                            ?>
                            <td><?php echo htmlspecialchars((string)($fechaInicio ?? '—'), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars((string)($fechaFin ?? '—'), ENT_QUOTES, 'UTF-8'); ?></td>

                            <td>
                                <?php
                                    $estadoVal = $row['estado'] ?? $row['activo'] ?? null;
                                    $activo = ((int)$estadoVal === 1);
                                ?>
                                <?php if($activo): ?>
                                    <span class="badge badge-success">Activo</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Inactivo</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($result)): ?>
                        <tr><td colspan="99" style="text-align:center;padding:32px;color:var(--text-muted);">Sin registros disponibles.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include_once 'app/view/layout/foot.php'; ?>

