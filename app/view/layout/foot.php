    </div><!-- /main-content -->
</div><!-- /app-layout -->
<script src="assets\js\DataTables\jquery.js"></script>
<script src="assets\js\DataTables\datatables.min.js"></script>
<?php if(isset($jsFile)): ?>
<script src="assets/js/<?php echo $jsFile; ?>?v=<?php echo file_exists("assets/js/{$jsFile}") ? filemtime("assets/js/{$jsFile}") : time(); ?>"></script>
<?php endif; ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var toggleBtn = document.getElementById('sidebarToggleBtn');
        if (!toggleBtn) return;
    toggleBtn.addEventListener('click', function() {
            var appLayout = document.querySelector('.app-layout');
            if (!appLayout) return;

            var hidden = appLayout.classList.toggle('sidebar-hidden');

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
