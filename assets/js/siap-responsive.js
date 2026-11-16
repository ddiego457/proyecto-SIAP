/**
 * SIAP Responsive - Mobile card generation and month detail modal
 * Works with any DataTable, no duplication of business logic.
 */
(function () {
  'use strict';

  const MONTH_NAMES = [
    'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
    'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
  ];

  function isMobile() {
    return window.innerWidth <= 768;
  }

  /**
   * Generate mobile cards from a DataTable instance.
   * @param {string} tableId - The table DOM id
   * @param {Array}  columns - Column configs: [{ key, label, isStatus?, isActions?, actions? }]
   *   actions: [{ label, class, dataAttr?, url? }]
   */
  window.siapGenerateCards = function (tableId, columns) {
    const table = document.getElementById(tableId);
    const container = document.getElementById(tableId + '-cards');
    if (!table || !container) return;

    // Use DataTables API if available
    let dtData = null;
    if (typeof $ !== 'undefined' && $.fn.DataTable) {
      const dt = $('#' + tableId).DataTable();
      if (dt) dtData = dt.rows().data().toArray();
    }

    if (!dtData || dtData.length === 0) {
      container.innerHTML = '<div class="empty-state"><p>No hay registros</p></div>';
      return;
    }

    let html = '';
    dtData.forEach(function (row, idx) {
      html += '<div class="mobile-card" data-row-index="' + idx + '">';
      html += '<div class="mobile-card-header">';

      // First column as title
      const titleCol = columns[0];
      const titleVal = row[titleCol.key] !== undefined ? row[titleCol.key] : '';
      const idVal = row['id'] || row['ID'] || row['idItem'] || idx;
      html += '<span class="mobile-card-id">#' + escapeHtml(String(idVal)) + '</span>';
      html += '</div>';

      html += '<div class="mobile-card-body">';
      columns.forEach(function (col, ci) {
        if (ci === 0) return; // skip first (used as title)
        if (col.isActions) return;
        let val = row[col.key] !== undefined ? row[col.key] : '';
        if (val === null || val === undefined) val = '';
        html += '<div class="mobile-card-field">';
        html += '<span class="mobile-card-label">' + escapeHtml(col.label) + '</span>';
        html += '<span class="mobile-card-value">' + escapeHtml(String(val)) + '</span>';
        html += '</div>';
      });
      html += '</div>';

      // Action buttons
      const actionCol = columns.find(function (c) { return c.isActions; });
      if (actionCol && actionCol.actions) {
        html += '<div class="mobile-card-actions">';
        actionCol.actions.forEach(function (act) {
          const cls = act.class || 'btn btn-outline';
          let attrs = '';
          if (act.dataAttr) {
            Object.keys(act.dataAttr).forEach(function (k) {
              attrs += ' data-' + k + '="' + escapeHtml(String(row[act.dataAttr[k]] || '')) + '"';
            });
          }
          if (act.url) {
            html += '<a href="' + act.url + '" class="' + cls + '">' + act.label + '</a>';
          } else {
            html += '<button type="button" class="' + cls + '"' + attrs + '>' + act.label + '</button>';
          }
        });
        html += '</div>';
      }

      html += '</div>';
    });

    container.innerHTML = html;
  };

  /**
   * Month detail modal for requerimientos
   */
  window.siapShowMonthDetail = function (rowData) {
    const existing = document.getElementById('monthDetailModal');
    if (existing) existing.remove();

    const months = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
    let total = 0;

    let html = '<div id="monthDetailModal" class="modal-backdrop" style="display:flex;">';
    html += '<div class="modal-panel" style="max-width:420px;">';
    html += '<div class="modal-header">';
    html += '<h3>Distribuci\u00f3n Mensual</h3>';
    html += '<button class="modal-close" onclick="document.getElementById(\'monthDetailModal\').remove()">&times;</button>';
    html += '</div>';
    html += '<div class="modal-body">';

    html += '<div style="margin-bottom:12px;">';
    html += '<div style="font-size:13px;color:var(--text-muted);margin-bottom:4px;">Producto</div>';
    html += '<div style="font-size:14px;font-weight:600;color:var(--text-dark);">' + escapeHtml(rowData.producto || '') + '</div>';
    html += '</div>';

    html += '<div class="month-detail-grid">';
    months.forEach(function (m, i) {
      const val = parseFloat(rowData[m]) || 0;
      total += val;
      html += '<div class="month-detail-item">';
      html += '<span class="month-detail-name">' + MONTH_NAMES[i] + '</span>';
      html += '<span class="month-detail-value">' + val.toLocaleString('es-VE') + '</span>';
      html += '</div>';
    });
    html += '</div>';

    html += '<div class="month-detail-total">';
    html += '<span>Total F\u00edsico</span>';
    html += '<span>' + total.toLocaleString('es-VE') + '</span>';
    html += '</div>';

    html += '</div>';
    html += '<div class="modal-footer">';
    html += '<button class="btn btn-outline" onclick="document.getElementById(\'monthDetailModal\').remove()">Cerrar</button>';
    html += '</div>';
    html += '</div></div>';

    document.body.insertAdjacentHTML('beforeend', html);

    // Close on backdrop click
    document.getElementById('monthDetailModal').addEventListener('click', function (e) {
      if (e.target === this) this.remove();
    });
  };

  function escapeHtml(str) {
    if (!str) return '';
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  /**
   * Auto-initialize mobile cards for all tables with mobile-card-list containers.
   * Each table must call siapGenerateCards() after DataTable loads.
   * This function provides a hook for that.
   */
  document.addEventListener('DOMContentLoaded', function () {
    // Poll for DataTables ready and generate cards
    function tryGenerate(tableId, columns, maxAttempts) {
      let attempts = 0;
      const interval = setInterval(function () {
        attempts++;
        const table = document.getElementById(tableId);
        const container = document.getElementById(tableId + '-cards');
        if (!table || !container) { clearInterval(interval); return; }

        // Check if DataTable has data
        const rows = table.querySelectorAll('tbody tr');
        if (rows.length > 0 && rows[0].querySelector('td') !== null) {
          clearInterval(interval);
          window.siapGenerateCards(tableId, columns);
        } else if (attempts >= (maxAttempts || 30)) {
          clearInterval(interval);
        }
      }, 200);
    }

    // Expose for each module
    window.siapTryGenerateCards = tryGenerate;
  });
})();
