$(function(){
    const url = 'app/controller/reportController.php';
    const $dependenciaFilter = $('#dependenciaFilter');
    const $btnClear = $('#btnClearFilter');

    function getSelectedDependency() {
        const val = $dependenciaFilter.length ? $dependenciaFilter.val() : '';
        return val ? Number(val) : null;
    }

    function loadSummary() {
        const dependenciaId = getSelectedDependency();
        const payload = { action: 'getSummary' };
        if (dependenciaId !== null) {
            payload.dependenciaId = dependenciaId;
        }

        $.post(url, payload, function(res){
            if (res.error) { alert('Error cargando reporte'); return; }
            // Partidas
            let htmlP = '<table class="siap-table"><thead><tr><th>Partida</th><th>Descripción</th><th>USD</th><th>BCV</th></tr></thead><tbody>';
            res.partidas.forEach(p => {
                htmlP += `<tr><td>${p.partida}</td><td>${p.descripcion}</td><td class="num">${Number(p.total_usd).toFixed(2)}</td><td class="num">${Number(p.total_bcv).toFixed(2)}</td></tr>`;
            });
            htmlP += '</tbody></table>';
            $('#partidasWrap').html(htmlP);

            // Dependencias
            let htmlD = '<table class="siap-table"><thead><tr><th>Dependencia</th><th>USD</th><th>BCV</th></tr></thead><tbody>';
            res.dependencias.forEach(d => {
                htmlD += `<tr><td>${d.nom_dep}</td><td class="num">${Number(d.total_usd).toFixed(2)}</td><td class="num">${Number(d.total_bcv).toFixed(2)}</td></tr>`;
            });
            htmlD += '</tbody></table>';
            $('#dependenciasWrap').html(htmlD);

            // Global
            $('#globalWrap').html(`<strong>USD:</strong> ${Number(res.global.total_usd).toFixed(2)} &nbsp; <strong>BCV:</strong> ${Number(res.global.total_bcv).toFixed(2)}`);
        }, 'json');
    }

    function submitExport(actionType) {
        const dependenciaId = getSelectedDependency();
        const f = document.createElement('form');
        f.method = 'POST';
        f.action = url;
        f.style.display = 'none';

        const actionInput = document.createElement('input');
        actionInput.name = 'action';
        actionInput.value = actionType;
        f.appendChild(actionInput);

        if (dependenciaId !== null) {
            const depInput = document.createElement('input');
            depInput.name = 'dependenciaId';
            depInput.value = dependenciaId.toString();
            f.appendChild(depInput);
        }

        document.body.appendChild(f);
        f.submit();
        document.body.removeChild(f);
    }

    loadSummary();

    $dependenciaFilter.on('change', function(){
        loadSummary();
    });

    $btnClear.on('click', function(){
        if ($dependenciaFilter.length) {
            $dependenciaFilter.val('');
            loadSummary();
        }
    });

    $('#btnExportExcel').on('click', function(){
        submitExport('exportExcel');
    });

    $('#btnExportPdf').on('click', function(){
        submitExport('exportPdf');
    });
});
