</div><!-- /main-content -->
</div><!-- /app-layout -->
<script src="assets/js/DataTables/jquery.js"></script>
<script src="assets/js/DataTables/datatables.min.js"></script>
<!-- Foto de perfil: disponible en todas las páginas (bloque de usuario en la sidebar) -->
<script src="assets/js/perfil.js?v=<?php echo file_exists('assets/js/perfil.js') ? filemtime('assets/js/perfil.js') : time(); ?>"></script>
<?php if(isset($jsFile)): ?>
<script src="assets/js/<?php echo $jsFile; ?>?v=<?php echo file_exists("assets/js/{$jsFile}") ? filemtime("assets/js/{$jsFile}") : time(); ?>"></script>
<?php endif; ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var toggleBtn = document.getElementById('sidebarToggleBtn');
        if (!toggleBtn) return;
    toggleBtn.addEventListener('click', function(e) {
            if (e && e.target && e.target.closest('.sidebar-link[data-nav="1"]')) return;
            var appLayout = document.querySelector('.app-layout');
            if (!appLayout) return;

            var hidden = appLayout.classList.toggle('sidebar-collapsed');

            // Cuando está oculto, quitamos tabIndex para que no quede "encima" manipulable
            var sidebar = document.querySelector('.sidebar');
            if (sidebar) {
                sidebar.style.pointerEvents = hidden ? 'none' : '';
            }

            toggleBtn.setAttribute('aria-expanded', hidden ? 'false' : 'true');
        });
    });
</script>
</body>
</html>