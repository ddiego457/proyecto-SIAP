</div><!-- /main-content -->
</div><!-- /app-layout -->
<script src="assets/js/DataTables/jquery.js"></script>
<script src="assets/js/DataTables/datatables.min.js"></script>
<?php if(isset($jsFile)): ?>
<script src="assets/js/<?php echo $jsFile; ?>?v=<?php echo file_exists("assets/js/{$jsFile}") ? filemtime("assets/js/{$jsFile}") : time(); ?>"></script>
<?php endif; ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var toggleBtn = document.getElementById('sidebarToggleBtn');
        if (!toggleBtn) return;
        var appLayout = document.querySelector('.app-layout');
        if (!appLayout) return;
        var sidebar = document.querySelector('.sidebar');
        var isCollapsed = appLayout.classList.contains('sidebar-collapsed');

        toggleBtn.setAttribute('aria-expanded', isCollapsed ? 'false' : 'true');
        if (sidebar) {
            sidebar.style.pointerEvents = isCollapsed ? 'none' : '';
        }

        toggleBtn.addEventListener('click', function(e) {
            if (e && e.target && e.target.closest('.sidebar-link[data-nav="1"]')) return;
            var hidden = appLayout.classList.toggle('sidebar-collapsed');

            if (sidebar) {
                sidebar.style.pointerEvents = hidden ? 'none' : '';
            }

            toggleBtn.setAttribute('aria-expanded', hidden ? 'false' : 'true');
            try {
                localStorage.setItem('siap-sidebar-collapsed', hidden ? 'true' : 'false');
            } catch (error) {
                console.error('No se pudo guardar el estado del menú lateral.', error);
            }
        });
    });
</script>
</body>
</html>