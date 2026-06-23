<?php
$pageTitle = "Tasas BCV â€” Lista";
include_once 'app/view/layout/head.php';

$result = $result ?? [];
?>

<div class="topbar">
    <div class="topbar-title">Tasas BCV â€” Consulta</div>
    <div class="topbar-actions">
        <a href="?url=tasaBCV&type=register" class="btn btn-success btn-sm">&#43; Registrar</a>
        <a href="?url=tasaBCV&type=main"     class="btn btn-outline btn-sm">Gestionar</a>

    </div>
</div>

<div class="page-body">
    <div class="card">
        <div class="card-header">
            <span class="card-title">Tasas BCV</span>
            <span class="badge badge-blue"><?php echo count($result); ?> registros</span>
        </div>
        <div class="card-body">
            <div class="table-wrap">
                <table class="siap-table">
                    <thead><tr><th>ID</th><th>Tasa BCV (USD)</th><th>Fecha</th><th>Estado</th></tr></thead>
                    <tbody>
                        <?php foreach ($result as $row): ?>
                        <tr>
                            <td><?php echo $row['id_tasa']; ?></td>
                            <td><?php echo $row['tasa_bcv_usd']; ?></td>
                            <td><?php echo !empty($row['fecha_reg']) ? date('d/m/Y', strtotime($row['fecha_reg'])) : ''; ?></td>
                            <td>
                                <?php if((int)$row['estado'] == 1): ?>
                                <span class="badge badge-success">Activo</span>
                                <?php else: ?>
                                <span class="badge badge-danger">Inactivo</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($result)): ?>
                        <tr><td colspan="99" style="text-align:center;padding:32px;color:var(--text-muted);">
                            Sin registros disponibles.
                        </td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include_once 'app/view/layout/foot.php'; ?>
