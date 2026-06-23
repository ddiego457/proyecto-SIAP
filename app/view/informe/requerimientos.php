<?php
$pageTitle = 'informe de dependencia';
include_once 'app/view/layout/head.php';

// Si se pasan datos desde el controlador, se reflejan aquí.
$result = isset($result) ? $result : [];
$aniosFiscales = isset($aniosFiscales) ? $aniosFiscales : [];
$dependencias  = isset($dependencias) ? $dependencias : [];
?>

<div class="topbar">
    <div class="topbar-title">informe de dependencia</div>
</div>

<div class="page-body">

    <div class="card" style="margin-bottom:16px;">
        <div class="card-header">
            <span class="card-title">Filtros</span>
        </div>
        <div class="card-body">
            <form id="formInforme">
                <div class="row" style="gap:12px;">
                    <div class="field-group" style="flex:1; min-width:200px;">
                        <label class="field-label">Busqueda</label>
                        <input type="text" name="buscar" class="field-input" placeholder="CÃ³digo o descripciÃ³n" />
                    </div>

                    <div class="field-group" style="min-width:220px;">
                        <label class="field-label">Dependencia</label>
                        <select name="dependenciaId" class="field-input field-select">
                            <option value="">Todas</option>
                            <?php foreach ($dependencias as $d): ?>
                                <option value="<?php echo (int)$d['dependencia_id']; ?>"><?php echo htmlspecialchars((string)$d['nombre_dep']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="field-group" style="min-width:220px;">
                        <label class="field-label">Año Fiscal</label>
                        <select name="anioFiscalId" class="field-input field-select">
                            <option value="">Todos</option>
                            <?php foreach ($aniosFiscales as $a): ?>
                                <option value="<?php echo (int)$a['id_anioFis']; ?>">
                                    <?php echo htmlspecialchars((string)$a['anio_fiscal']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="field-group" style="min-width:220px;">
                        <label class="field-label">Partida (402/403)</label>
                        <select name="partida" class="field-input field-select">
                            <option value="">Todas</option>
                            <option value="402">402</option>
                            <option value="403">403</option>
                        </select>
                    </div>

                    <div class="field-group" style="align-self:flex-end;">
                        <button type="submit" class="btn btn-blue">Buscar</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <span class="card-title">Resultados</span>
            <span class="badge badge-blue"><?php echo count($result); ?> registros</span>
        </div>
        <div class="card-body">
            <div class="table-wrap">
                <table class="siap-table" style="width:100%;font-size:12.5px;">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Código</th>
                            <th>Descripción</th>
                            <th>Dependencia</th>
                            <th>Partida</th>
                            <th>Año Fiscal</th>
                            <th>Periodo</th>
                            <th>Estado</th>
                            <th>Fecha Envío</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($result)): ?>
                            <tr><td colspan="9" style="text-align:center;padding:28px;color:var(--text-muted);">Sin datos</td></tr>
                        <?php else: ?>
                            <?php foreach ($result as $r): ?>
                                <tr>
                                    <td><?php echo (int)$r['id_requerimiento']; ?></td>
                                    <td><?php echo htmlspecialchars((string)($r['codigo'] ?? '')); ?></td>
                                    <td><?php echo htmlspecialchars((string)($r['descripcion'] ?? '')); ?></td>
                                    <td><?php echo htmlspecialchars((string)($r['dependencia_id'] ?? '')); ?></td>
                                    <td><?php echo htmlspecialchars((string)($r['partida_presupuestaria'] ?? '')); ?></td>
                                    <td><?php echo htmlspecialchars((string)($r['anio_fiscal_id'] ?? '')); ?></td>
                                    <td><?php echo htmlspecialchars((string)($r['periodo_id'] ?? '')); ?></td>
                                    <td><?php echo htmlspecialchars((string)($r['estado'] ?? '')); ?></td>
                                    <td><?php echo htmlspecialchars((string)($r['fecha_envio'] ?? '')); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<?php include_once 'app/view/layout/foot.php'; ?>


