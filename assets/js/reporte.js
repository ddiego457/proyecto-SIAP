(function() {
    'use strict';

    let exportInProgress = false;

    function showToast(message, type = 'error') {
        const existing = document.querySelector('.toast');
        if (existing) existing.remove();

        const toast = document.createElement('div');
        toast.className = 'toast toast-' + type;
        toast.textContent = message;
        document.body.appendChild(toast);

        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transition = 'opacity 0.3s';
            setTimeout(() => toast.remove(), 300);
        }, 4000);
    }

    async function loadDependencias() {
        const select = document.getElementById('selDepIndividual');
        if (!select) return;
        const status = document.getElementById('selEstadoReq')?.value || 'enviado';

        try {
            select.disabled = true;
            const res = await fetch('?url=reporte&type=get_dependencias&estado_envio=' + encodeURIComponent(status));
            const data = await res.json();
            if (!data.success) throw new Error(data.message);

            select.innerHTML = '<option value="">-- Seleccione dependencia --</option>';
            (data.data || []).forEach(d => {
                const opt = document.createElement('option');
                opt.value = d.id_dep;
                opt.textContent = d.nom_dep;
                select.appendChild(opt);
            });
            select.disabled = false;
        } catch (err) {
            select.innerHTML = '<option value="">No se pudieron cargar dependencias</option>';
            showToast('No se pudieron cargar las dependencias: ' + err.message);
        }
    }

    async function loadPartidas() {
        const optionsContainer = document.getElementById('selPartidasReport');
        if (!optionsContainer) return;

        try {
            const res = await fetch('?url=reporte&type=get_partidas');
            const data = await res.json();
            if (!data.success) throw new Error(data.message);

            optionsContainer.innerHTML = '';
            (data.data || []).forEach(partida => {
                const label = document.createElement('label');
                label.className = 'partida-option';

                const checkbox = document.createElement('input');
                checkbox.type = 'checkbox';
                checkbox.className = 'partida-option-input';
                checkbox.value = partida.id_partida;
                checkbox.addEventListener('change', () => {
                    if (checkbox.checked) {
                        document.getElementById('chkTodasPartidasReport').checked = false;
                    }
                    label.classList.toggle('is-selected', checkbox.checked);
                    updatePartidaSelectionSummary();
                });

                const text = document.createElement('span');
                text.textContent = partida.cod_partida + ' - ' + partida.descripcion;

                label.append(checkbox, text);
                optionsContainer.appendChild(label);
            });
            updatePartidaSelectionSummary();
        } catch (err) {
            optionsContainer.innerHTML = '<div class="partidas-loading">Error al cargar partidas</div>';
            showToast('No se pudieron cargar las partidas: ' + err.message);
        }
    }

    function updatePartidaSelectionSummary() {
        const allCheckbox = document.getElementById('chkTodasPartidasReport');
        const countLabel = document.getElementById('lblPartidasSeleccionadas');
        if (!countLabel) return;

        if (allCheckbox?.checked) {
            countLabel.textContent = 'Todas las partidas seleccionadas';
            return;
        }

        const selectedCount = document.querySelectorAll('#selPartidasReport .partida-option-input:checked').length;
        countLabel.textContent = selectedCount === 1
            ? '1 partida seleccionada'
            : selectedCount + ' partidas seleccionadas';
    }

    async function exportReport(reportCode, format, button) {
        if (exportInProgress) return;

        const select = document.getElementById('selDepIndividual');
        const statusSelect = document.getElementById('selEstadoReq');
        const allPartidasCheckbox = document.getElementById('chkTodasPartidasReport');
        const filtroDependenciasSelect = document.getElementById('selFiltroDependencias');
        const modoGlobalSelect = document.getElementById('selModoGlobal');

        let idDep = 0;
        let estadoEnvio = '';
        let partidas = [];
        if (reportCode === 'req_individual') {
            estadoEnvio = statusSelect ? statusSelect.value : '';
            if (!estadoEnvio) {
                showToast('Seleccione el estado del requerimiento.');
                return;
            }
            idDep = select ? parseInt(select.value, 10) : 0;
            if (select && !idDep) {
                showToast('Debe seleccionar una dependencia.');
                return;
            }
        } else if (reportCode === 'productos_partida') {
            partidas = allPartidasCheckbox?.checked
                ? ['todas']
                : Array.from(document.querySelectorAll('#selPartidasReport .partida-option-input:checked'))
                    .map(checkbox => checkbox.value);
            if (partidas.length === 0) {
                showToast('Seleccione una o varias partidas, o todas.');
                return;
            }
        } else if (reportCode === 'dependencias_responsable' && !filtroDependenciasSelect?.value) {
            showToast('Seleccione el tipo de dependencias.');
            return;
        } else if (reportCode === 'req_global' && !modoGlobalSelect?.value) {
            showToast('Seleccione el tipo de consolidado.');
            return;
        }

        const exportButtons = Array.from(document.querySelectorAll('[data-report-export]'));
        const parameterControls = Array.from(document.querySelectorAll('.report-grid select, .report-grid input[type="checkbox"]'));
        const clickedButton = button instanceof HTMLButtonElement
            ? button
            : document.activeElement instanceof HTMLButtonElement
                ? document.activeElement
                : null;
        const buttonStates = exportButtons.map(btn => ({ button: btn, disabled: btn.disabled }));
        const controlStates = parameterControls.map(control => ({ control, disabled: control.disabled }));
        const originalButtonText = clickedButton ? clickedButton.innerHTML : '';
        exportInProgress = true;

        exportButtons.forEach(btn => { btn.disabled = true; });
        parameterControls.forEach(control => { control.disabled = true; });
        if (clickedButton) {
            clickedButton.innerHTML = '<span class="spinner"></span> Generando...';
        }

        try {
            let url = '?url=reporte&type=export&report=' + encodeURIComponent(reportCode) + '&format=' + encodeURIComponent(format);
            const formData = new FormData();
            if (idDep) formData.append('id_dep', idDep);
            if (estadoEnvio) formData.append('estado_envio', estadoEnvio);
            if (reportCode === 'productos_partida') {
                if (partidas[0] === 'todas') {
                    formData.append('id_partidas', 'todas');
                } else {
                    partidas.forEach(idPartida => formData.append('id_partidas[]', idPartida));
                }
            }
            if (filtroDependenciasSelect && reportCode === 'dependencias_responsable') {
                formData.append('filtro_dependencias', filtroDependenciasSelect.value);
            }
            if (modoGlobalSelect && reportCode === 'req_global') {
                formData.append('modo_global', modoGlobalSelect.value);
            }

            const res = await fetch(url, {
                method: 'POST',
                body: formData
            });

            const contentType = res.headers.get('content-type') || '';
            if (contentType.includes('application/json')) {
                const data = await res.json();
                if (!data.success) throw new Error(data.message);
                showToast('Respuesta inesperada del servidor', 'error');
                return;
            }

            const blob = await res.blob();
            if (blob.size === 0) throw new Error('Archivo vacío recibido');

            const downloadUrl = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = downloadUrl;
            const filename = res.headers.get('content-disposition')?.match(/filename="([^"]+)"/)?.[1] || (reportCode + '_' + format + '_' + Date.now() + '.' + format);
            a.download = filename;
            document.body.appendChild(a);
            a.click();
            a.remove();
            window.URL.revokeObjectURL(downloadUrl);

            showToast('Descarga completada', 'success');
        } catch (err) {
            showToast('Error: ' + err.message, 'error');
        } finally {
            if (clickedButton) clickedButton.innerHTML = originalButtonText;
            buttonStates.forEach(state => { state.button.disabled = state.disabled; });
            controlStates.forEach(state => { state.control.disabled = state.disabled; });
            exportInProgress = false;
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        loadPartidas();
        loadDependencias();
        document.getElementById('selEstadoReq')?.addEventListener('change', loadDependencias);
        document.getElementById('chkTodasPartidasReport')?.addEventListener('change', event => {
            const checkbox = event.currentTarget;
            const optionsContainer = document.getElementById('selPartidasReport');
            if (!optionsContainer) return;
            optionsContainer.querySelectorAll('.partida-option-input').forEach(option => {
                option.checked = false;
                option.closest('.partida-option').classList.remove('is-selected');
            });
            updatePartidaSelectionSummary();
        });
        window.exportReport = exportReport;
    });
})();