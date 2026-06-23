<?php
$pageTitle = "Dependencias — Lista";
include_once 'app/view/layout/head.php';

$result = $result ?? [];
?>

<div class="topbar">
    <div class="topbar-title">Dependencias Consulta</div>
    <div class="topbar-actions">
        <a href="?url=dependencia&type=register" class="btn btn-success btn-sm">&#43; Registrar</a>
        <a href="?url=dependencia&type=main"     class="btn btn-outline btn-sm">Gestionar</a>

    </div>
</div>

<div class="page-body">
    <div class="card">
        <div class="card-header">
            <span class="card-title">Dependencias</span>
            <span class="badge badge-blue"><?php echo count($result); ?> registros</span>
        </div>
        <div class="card-body">
            <div class="table-wrap">
                <table class="siap-table">
                    <thead><tr><th>ID</th><th>Nombre Dependencia</th><th>Rol</th><th>Estado</th></tr></thead>
                    <tbody>
                        <?php foreach ($result as $row): ?>
                        <tr>
                            <td><?php echo $row['id_dep']; ?></td><td><?php echo $row['nombre_dep']; ?></td><td><?php echo $row['rol']; ?></td>
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
