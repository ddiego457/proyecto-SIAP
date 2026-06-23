<?php
$pageTitle = "Registrar — Responsable";
$jsFile    = "responsable.js";
include_once 'app/view/layout/head.php';
?>

<div class="topbar">
    <div class="topbar-title">Registrar Responsable</div>
    <div class="topbar-actions">
        <a href="?url=responsable&type=main" class="btn btn-outline btn-sm">&#8592; Volver</a>
    </div>
</div>

<div class="page-body">
    <div class="card" style="max-width:560px;margin:0 auto;">
        <div class="card-header">
            <span class="card-title">Nuevo Responsable</span>
        </div>
        <div class="card-body">
            <form id="formRegistro">
                <div class="field-group">
                    <label class="field-label">Nombre</label>
                    <input type="text" name="nom_rep" class="field-input" required>
                </div>
                <div class="field-group">
                    <label class="field-label">Contraseña</label>
                    <input type="password" name="contrasena" class="field-input" required>
                </div>
                <div class="field-group">
                    <label class="field-label">Rol</label>
                    <div class="field-select-wrap">
                        <select name="id_rol" class="field-input field-select" required>
                            <option value="">— Seleccione rol —</option>
                            <?php foreach ($roles as $rol): ?>
                                <option value="<?php echo $rol['id_rol']; ?>"><?php echo htmlspecialchars($rol['descripcion']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="field-group">
                    <label class="field-label">Dependencia</label>
                    <input list="dependenciasList" id="register_dependencia_search" name="dependencia_search" class="field-input" placeholder="Escriba para buscar dependencia" autocomplete="off" required>
                    <datalist id="dependenciasList">
                        <?php foreach ($dependenciasDisponibles as $dep): ?>
                            <option value="<?php echo htmlspecialchars($dep['nom_dep']); ?>" data-id="<?php echo $dep['id_dep']; ?>"></option>
                        <?php endforeach; ?>
                    </datalist>
                    <input type="hidden" id="register_id_dep" name="id_dep">
                    <p class="field-hint">Seleccione una dependencia existente de la lista.</p>
                </div>
                <div class="flex gap-10" style="margin-top:24px;">
                    <button type="submit" class="btn btn-success" style="flex:1;">&#10003; Registrar</button>
                    <a href="?url=responsable&type=main" class="btn btn-outline" style="flex:1;justify-content:center;">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include_once 'app/view/layout/foot.php'; ?>
