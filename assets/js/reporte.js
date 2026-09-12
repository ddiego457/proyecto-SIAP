(function() {
    'use strict';

    const BASE_URL = window.location.pathname + window.location.search.replace(/[?&]type=[^&]*/, '').replace(/[?&]format=[^&]*/, '').replace(/[?&]report=[^&]*/, '');
    const API_BASE = BASE_URL.includes('?') ? BASE_URL.replace('?', '&') : BASE_URL + '?';

    let dependenciasCache = [];

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

    function setButtonLoading(btn, loading) {
        if (loading) {
            btn.disabled = true;
            btn.dataset.originalText = btn.innerHTML;
            btn.innerHTML = '<span class="spinner"></span> Generando...';
        } else {
            btn.disabled = false;
            if (btn.dataset.originalText) {
                btn.innerHTML = btn.dataset.originalText;
            }
        }
    }

    async function loadDependencias() {
        const select = document.getElementById('selDepIndividual');
        if (!select) return;

        try {
            const res = await fetch('?url=reporte&type=get_dependencias');
            const data = await res.json();
            if (!data.success) throw new Error(data.message);

            dependenciasCache = data.data || [];
            select.innerHTML = '<option value="">-- Seleccione dependencia --</option>';
            dependenciasCache.forEach(d => {
                const opt = document.createElement('option');
                opt.value = d.id_dep;
                opt.textContent = d.nom_dep;
                select.appendChild(opt);
            });
            select.disabled = false;
        } catch (err) {
            select.innerHTML = '<option value="">Error al cargar</option>';
            showToast('No se pudieron cargar las dependencias: ' + err.message);
        }
    }

    async function exportReport(reportCode, format) {
        const btnExcel = document.getElementById('btnExpIndExcel');
        const btnPdf = document.getElementById('btnExpIndPdf');
        const select = document.getElementById('selDepIndividual');

        let idDep = 0;
        if (reportCode === 'req_individual') {
            idDep = select ? parseInt(select.value, 10) : 0;
            if (!idDep) {
                showToast('Debe seleccionar una dependencia.');
                return;
            }
        }

        const buttonsToDisable = [];
        if (btnExcel) buttonsToDisable.push(btnExcel);
        if (btnPdf) buttonsToDisable.push(btnPdf);
        if (select) buttonsToDisable.push(select);

        buttonsToDisable.forEach(b => { if (b) setButtonLoading(b, true); });

        try {
            let url = '?url=reporte&type=export&report=' + encodeURIComponent(reportCode) + '&format=' + encodeURIComponent(format);
            const formData = new FormData();
            if (idDep) formData.append('id_dep', idDep);

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
            buttonsToDisable.forEach(b => { if (b) setButtonLoading(b, false); });
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        loadDependencias();
        window.exportReport = exportReport;
    });
})();