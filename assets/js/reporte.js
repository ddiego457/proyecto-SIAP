(function() {
    'use strict';

    let exportInProgress = false;
    let dependencySearchTimer = null;
    let dependencySearchController = null;
    let dependencySuggestionsOpen = false;

    const reportDefinitions = [
        {
            code: 'productos_partida',
            title: 'Productos por Partida',
            description: 'Seleccione una o varias partidas presupuestarias, o todas.',
            adminOnly: true
        },
        {
            code: 'dependencias_responsable',
            title: 'Dependencias',
            description: 'Activas con/sin responsable o inactivas.',
            adminOnly: true
        },
        {
            code: 'req_individual',
            title: 'Requerimientos Individuales por Dependencia',
            description: 'Seleccione dependencia y estado del requerimiento.',
            adminOnly: false
        },
        {
            code: 'req_global',
            title: 'Requerimientos Global (Consolidado)',
            description: 'Consolidado, cantidades por producto o totales por partida.',
            adminOnly: true
        }
    ];

    function renderReportOptions(code, isAdmin) {
        if (code === 'productos_partida') {
            return `
                <div class="field-group partidas-filter" style="margin-bottom:12px;">
                    <span class="field-label" style="font-size:.85rem;">Partidas presupuestarias</span>
                    <label class="partidas-all-option">
                        <input type="checkbox" id="chkTodasPartidasReport" checked>
                        <span><strong>Todas las partidas</strong><small>Incluye el catálogo completo</small></span>
                    </label>
                    <div id="selPartidasReport" class="partidas-options" aria-label="Partidas presupuestarias">
                        <div class="partidas-loading">Cargando partidas...</div>
                    </div>
                    <small id="lblPartidasSeleccionadas" class="partidas-selection-count" aria-live="polite">
                        Todas las partidas seleccionadas
                    </small>
                </div>`;
        }

        if (code === 'dependencias_responsable') {
            return `
                <div class="field-group report-choice-group" style="margin-bottom:12px;">
                    <span class="field-label">Tipo de dependencias</span>
                    <div id="selFiltroDependencias" class="report-choice-list" role="radiogroup" aria-label="Tipo de dependencias">
                        <label class="report-choice is-selected">
                            <input type="radio" name="filtro_dependencias" value="activas_con_responsable" checked>
                            <span>Activas con responsable</span>
                        </label>
                        <label class="report-choice">
                            <input type="radio" name="filtro_dependencias" value="activas_sin_responsable">
                            <span>Activas sin responsable</span>
                        </label>
                        <label class="report-choice">
                            <input type="radio" name="filtro_dependencias" value="inactivas">
                            <span>Inactivas (con o sin responsables)</span>
                        </label>
                    </div>
                </div>`;
        }

        if (code === 'req_individual') {
            return `
                ${isAdmin ? `
                    <div class="field-group dependency-search" style="margin-bottom:12px;">
                        <label class="field-label" for="selDepIndividual" style="font-size:.85rem;">Dependencia</label>
                        <input
                            type="search"
                            id="selDepIndividual"
                            class="field-input"
                            placeholder="Escriba al menos 2 caracteres"
                            autocomplete="off"
                            aria-autocomplete="list"
                            aria-controls="opcionesDepIndividual"
                            aria-expanded="false">
                        <input type="hidden" id="idDepIndividual">
                        <button type="button" id="alternarSugerenciasDep" class="dependency-search-toggle" aria-controls="opcionesDepIndividual" aria-expanded="false">
                            Mostrar sugerencias
                        </button>
                        <div id="opcionesDepIndividual" class="dependency-search-results" role="listbox" aria-label="Coincidencias de dependencias" hidden></div>
                        <small id="estadoBusquedaDep" class="partidas-selection-count" aria-live="polite"></small>
                    </div>` : ''}
                <div class="field-group report-choice-group" style="margin-bottom:12px;">
                    <span class="field-label">Estado del requerimiento</span>
                    <div id="selEstadoReq" class="report-choice-list" role="radiogroup" aria-label="Estado del requerimiento">
                        <label class="report-choice is-selected">
                            <input type="radio" name="estado_envio" value="enviado" checked>
                            <span>Enviado</span>
                        </label>
                        <label class="report-choice">
                            <input type="radio" name="estado_envio" value="sin_enviar">
                            <span>Sin enviar</span>
                        </label>
                    </div>
                </div>`;
        }

        if (code === 'req_global') {
            return `
                <div class="field-group report-choice-group" style="margin-bottom:12px;">
                    <span class="field-label">Tipo de consolidado</span>
                    <div id="selModoGlobal" class="report-choice-list" role="radiogroup" aria-label="Tipo de consolidado">
                        <label class="report-choice is-selected">
                            <input type="radio" name="modo_global" value="consolidado" checked>
                            <span>Consolidado detallado</span>
                        </label>
                        <label class="report-choice">
                            <input type="radio" name="modo_global" value="cantidades">
                            <span>Cantidades anuales por producto y partida</span>
                        </label>
                        <label class="report-choice">
                            <input type="radio" name="modo_global" value="totales">
                            <span>Totales por partida (USD y Bs.)</span>
                        </label>
                    </div>
                </div>`;
        }

        return '';
    }

    function renderReportCards() {
        const grid = document.getElementById('reportGrid');
        const intro = document.getElementById('reportIntro');
        if (!grid || !intro) return;

        const isAdmin = grid.dataset.isAdmin === 'true';
        const reports = reportDefinitions.filter(report => isAdmin || !report.adminOnly);
        intro.textContent = isAdmin
            ? 'Selecciona cualquiera de los cuatro informes y el formato. Los archivos se generan y descargan directamente.'
            : 'Solo puedes generar un informe de tu propio requerimiento. Si está vacío, el sistema te indicará qué debes completar.';
        grid.classList.toggle('report-grid-single', reports.length === 1);
        grid.innerHTML = reports.map(report => {
            const description = !isAdmin && report.code === 'req_individual'
                ? 'Requerimiento creado para su dependencia'
                : report.description;
            const exportButtons = report.code === 'req_individual'
                ? `
                    <button type="button" class="btn btn-success btn-full" data-report-export data-report-code="${report.code}" data-report-format="excel" id="btnExpIndExcel">
                        <span>&#128190;</span> Exportar Excel
                    </button>
                    <button type="button" class="btn btn-primary btn-full" data-report-export data-report-code="${report.code}" data-report-format="pdf" style="margin-top:8px;" id="btnExpIndPdf">
                        <span>&#128221;</span> Exportar PDF
                    </button>`
                : `
                    <div class="flex gap-10">
                        <button type="button" class="btn btn-success" data-report-export data-report-code="${report.code}" data-report-format="excel" style="flex:1;">
                            <span>&#128190;</span> Excel
                        </button>
                        <button type="button" class="btn btn-primary" data-report-export data-report-code="${report.code}" data-report-format="pdf" style="flex:1;">
                            <span>&#128221;</span> PDF
                        </button>
                    </div>`;

            return `
                <div class="report-card">
                    <div class="report-card-title">${report.title}</div>
                    <div class="report-card-text">${description}</div>
                    ${renderReportOptions(report.code, isAdmin)}
                    ${exportButtons}
                </div>`;
        }).join('');
    }

    function setDependencySuggestionsOpen(open) {
        dependencySuggestionsOpen = open;
        const input = document.getElementById('selDepIndividual');
        const results = document.getElementById('opcionesDepIndividual');
        const toggle = document.getElementById('alternarSugerenciasDep');
        if (results) results.hidden = !open;
        input?.setAttribute('aria-expanded', String(open));
        if (toggle) {
            toggle.setAttribute('aria-expanded', String(open));
            toggle.textContent = open ? 'Ocultar sugerencias' : 'Mostrar sugerencias';
        }
    }

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

    function getChoiceValue(containerId) {
        return document.querySelector('#' + containerId + ' input[type="radio"]:checked')?.value || '';
    }

    function renderDependencySearchMessage(message) {
        const results = document.getElementById('opcionesDepIndividual');
        if (!results) return;
        results.innerHTML = '';
        setDependencySuggestionsOpen(false);
        const status = document.getElementById('estadoBusquedaDep');
        if (status) status.textContent = message;
    }

    async function searchDependencias() {
        const input = document.getElementById('selDepIndividual');
        const idInput = document.getElementById('idDepIndividual');
        const results = document.getElementById('opcionesDepIndividual');
        if (!input || !idInput || !results) return;

        const nombre = input.value.trim();
        if (nombre.length === 1) {
            renderDependencySearchMessage('Escriba al menos 2 caracteres.');
            return;
        }

        const status = getChoiceValue('selEstadoReq') || 'enviado';
        dependencySearchController?.abort();
        dependencySearchController = new AbortController();
        const controller = dependencySearchController;
        const statusLabel = document.getElementById('estadoBusquedaDep');
        if (statusLabel) statusLabel.textContent = 'Buscando dependencias...';

        try {
            const params = new URLSearchParams({
                url: 'reporte',
                type: 'get_dependencias',
                estado_envio: status,
                q: nombre,
            });
            const res = await fetch('?' + params.toString(), { signal: controller.signal });
            const data = await res.json();
            if (!data.success) throw new Error(data.message);

            if (input.value.trim() !== nombre) return;
            results.innerHTML = '';
            if (!data.data?.length) {
                renderDependencySearchMessage(nombre
                    ? 'No se encontraron coincidencias.'
                    : 'No hay dependencias con requerimientos para este estado.');
                return;
            }
            (data.data || []).forEach(d => {
                const option = document.createElement('button');
                option.type = 'button';
                option.className = 'dependency-search-option';
                option.setAttribute('role', 'option');
                option.textContent = d.nom_dep;
                option.addEventListener('click', () => {
                    input.value = d.nom_dep;
                    idInput.value = d.id_dep;
                    renderDependencySearchMessage('Dependencia seleccionada: ' + d.nom_dep);
                });
                results.appendChild(option);
            });
            setDependencySuggestionsOpen(dependencySuggestionsOpen);
            if (statusLabel) {
                statusLabel.textContent = nombre
                    ? 'Seleccione una dependencia de la lista.'
                    : 'Sugerencias para requerimientos ' + (status === 'enviado' ? 'enviados.' : 'sin enviar.');
            }
        } catch (err) {
            if (err.name !== 'AbortError') {
                renderDependencySearchMessage('No se pudieron buscar dependencias.');
                showToast('No se pudieron buscar las dependencias: ' + err.message);
            }
        }
    }

    function scheduleDependencySearch() {
        clearTimeout(dependencySearchTimer);
        dependencySearchController?.abort();
        dependencySearchTimer = setTimeout(searchDependencias, 250);
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

        const selectedDependency = document.getElementById('idDepIndividual')?.value || '';
        const selectedStatus = getChoiceValue('selEstadoReq');
        const allPartidasCheckbox = document.getElementById('chkTodasPartidasReport');
        const selectedDependencyFilter = getChoiceValue('selFiltroDependencias');
        const selectedGlobalMode = getChoiceValue('selModoGlobal');

        let idDep = 0;
        let estadoEnvio = '';
        let partidas = [];
        if (reportCode === 'req_individual') {
            estadoEnvio = selectedStatus;
            if (!estadoEnvio) {
                showToast('Seleccione el estado del requerimiento.');
                return;
            }
            idDep = selectedDependency ? parseInt(selectedDependency, 10) : 0;
            if (document.getElementById('selDepIndividual') && !idDep) {
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
        } else if (reportCode === 'dependencias_responsable' && !selectedDependencyFilter) {
            showToast('Seleccione el tipo de dependencias.');
            return;
        } else if (reportCode === 'req_global' && !selectedGlobalMode) {
            showToast('Seleccione el tipo de consolidado.');
            return;
        }

        const exportButtons = Array.from(document.querySelectorAll('[data-report-export]'));
        const parameterControls = Array.from(document.querySelectorAll('.report-grid select, .report-grid input[type="checkbox"], .report-grid input[type="radio"], .report-grid input[type="search"]'));
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
            if (reportCode === 'dependencias_responsable') {
                formData.append('filtro_dependencias', selectedDependencyFilter);
            }
            if (reportCode === 'req_global') {
                formData.append('modo_global', selectedGlobalMode);
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
        renderReportCards();
        loadPartidas();
        document.getElementById('reportGrid')?.addEventListener('click', event => {
            const button = event.target.closest('[data-report-export]');
            if (button) {
                exportReport(button.dataset.reportCode, button.dataset.reportFormat, button);
            }
        });
        const dependencyInput = document.getElementById('selDepIndividual');
        const dependencyToggle = document.getElementById('alternarSugerenciasDep');
        dependencyInput?.addEventListener('input', () => {
            document.getElementById('idDepIndividual').value = '';
            scheduleDependencySearch();
        });
        dependencyToggle?.addEventListener('click', () => {
            const results = document.getElementById('opcionesDepIndividual');
            if (dependencySuggestionsOpen) {
                setDependencySuggestionsOpen(false);
                return;
            }
            if (results?.children.length) {
                setDependencySuggestionsOpen(true);
            } else {
                setDependencySuggestionsOpen(true);
                searchDependencias();
            }
        });
        dependencyInput?.addEventListener('keydown', event => {
            if (event.key === 'Escape') {
                setDependencySuggestionsOpen(false);
            }
        });
        if (dependencyInput) {
            searchDependencias();
        }
        document.querySelectorAll('.report-choice-list:not(#selDepIndividual) input[type="radio"]').forEach(radio => {
            radio.addEventListener('change', () => {
                radio.closest('.report-choice-list').querySelectorAll('.report-choice').forEach(choice => {
                    choice.classList.toggle('is-selected', choice.querySelector('input').checked);
                });
                if (radio.closest('.report-choice-list').id === 'selEstadoReq') {
                    const selectedDependency = document.getElementById('idDepIndividual');
                    if (selectedDependency) selectedDependency.value = '';
                    scheduleDependencySearch();
                }
            });
        });
        document.addEventListener('click', event => {
            if (!event.target.closest('.dependency-search')) {
                const results = document.getElementById('opcionesDepIndividual');
                if (results && !results.hidden) setDependencySuggestionsOpen(false);
            }
        });
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