</div><!-- /main-content -->
</div><!-- /app-layout -->
<script src="assets/js/DataTables/jquery.js"></script>
<script src="assets/js/DataTables/datatables.min.js"></script>
<!-- Foto de perfil y menú de usuario: disponible en todas las páginas (topbar → userMenu.php) -->
<script src="assets/js/perfil.js?v=<?php echo file_exists('assets/js/perfil.js') ? filemtime('assets/js/perfil.js') : time(); ?>"></script>
<script src="assets/js/constantes.js?v=<?php echo file_exists('assets/js/constantes.js') ? filemtime('assets/js/constantes.js') : time(); ?>"></script>
<script src="assets/js/validaciones.js?v=<?php echo file_exists('assets/js/validaciones.js') ? filemtime('assets/js/validaciones.js') : time(); ?>"></script>
<?php if(isset($jsFile)): ?>
<script src="assets/js/<?php echo $jsFile; ?>?v=<?php echo file_exists("assets/js/{$jsFile}") ? filemtime("assets/js/{$jsFile}") : time(); ?>"></script>
<?php endif; ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const toggleBtn = document.getElementById('sidebarToggleBtn');
        if (!toggleBtn) return;
        const appLayout = document.querySelector('.app-layout');
        if (!appLayout) return;
        const sidebar = document.querySelector('.sidebar');
        const desktopSidebar = window.matchMedia('(min-width: 901px)');
        const panel = document.getElementById('sidebarPanel');
        let sidebarCollapsedPreference = appLayout.dataset.sidebarCollapsedPreference === 'true';

        const updateSidebarPointerEvents = function() {
            if (sidebar) {
                sidebar.style.pointerEvents = !desktopSidebar.matches && appLayout.classList.contains('sidebar-collapsed')
                    ? 'none'
                    : '';
            }
        };

        const updateResponsiveSidebarState = function() {
            const isDesktop = desktopSidebar.matches;
            const collapsed = isDesktop || sidebarCollapsedPreference;
            appLayout.classList.toggle('sidebar-desktop-collapsed', isDesktop);
            appLayout.classList.toggle('sidebar-collapsed', !isDesktop && sidebarCollapsedPreference);
            toggleBtn.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
            updateSidebarPointerEvents();
        };

        updateResponsiveSidebarState();

        toggleBtn.addEventListener('click', function(e) {
            if (desktopSidebar.matches) return;
            if (e && e.target && e.target.closest('.sidebar-link[data-nav="1"]')) return;
            const hidden = appLayout.classList.toggle('sidebar-collapsed');
            sidebarCollapsedPreference = hidden;
            appLayout.dataset.sidebarCollapsedPreference = String(hidden);
            updateSidebarPointerEvents();
            toggleBtn.setAttribute('aria-expanded', hidden ? 'false' : 'true');
            try {
                localStorage.setItem('siap-sidebar-collapsed', hidden ? 'true' : 'false');
            } catch (error) {
                console.error('No se pudo guardar el estado del menú lateral.', error);
            }
        });

        if (panel) {
            let navegando = false;

            const guardarEstadoSidebar = function() {
                try {
                    const subs = [];
                    panel.querySelectorAll('.dropdown-hover').forEach(function(submenu, index) {
                        if (submenu.classList.contains('open')) subs.push(index);
                    });
                    const nav = panel.querySelector('.sidebar-nav');
                    sessionStorage.setItem('sbEstado', JSON.stringify({
                        open: panel.getAttribute('data-open') === 'true',
                        subs: subs,
                        scroll: nav ? nav.scrollTop : 0
                    }));
                } catch (error) {
                    console.error('No se pudo guardar el estado de la navegación lateral.', error);
                }
            };

            const abrirSidebar = function() {
                if (!desktopSidebar.matches) return;
                panel.setAttribute('data-open', 'true');
                guardarEstadoSidebar();
            };

            const cerrarSidebar = function() {
                if (!desktopSidebar.matches || navegando) return;
                panel.setAttribute('data-open', 'false');
                guardarEstadoSidebar();
            };

            panel.addEventListener('mouseenter', abrirSidebar);
            panel.addEventListener('focusin', abrirSidebar);
            panel.addEventListener('mouseleave', cerrarSidebar);
            panel.addEventListener('focusout', function(event) {
                if (!panel.contains(event.relatedTarget)) cerrarSidebar();
            });
            panel.addEventListener('click', function(event) {
                if (event.target.closest('.sidebar-link[data-nav="1"]')) navegando = true;
            });

            desktopSidebar.addEventListener('change', function(event) {
                if (!event.matches) {
                    panel.setAttribute('data-open', 'false');
                    guardarEstadoSidebar();
                }
                updateResponsiveSidebarState();
            });
        }

        document.addEventListener('click', function(e) {
            if (window.innerWidth <= 700) {
                if (appLayout && !appLayout.classList.contains('sidebar-collapsed')) {
                    if (sidebar && !sidebar.contains(e.target) && toggleBtn && !toggleBtn.contains(e.target)) {
                        appLayout.classList.add('sidebar-collapsed');
                        sidebarCollapsedPreference = true;
                        appLayout.dataset.sidebarCollapsedPreference = 'true';
                        updateSidebarPointerEvents();
                        if (toggleBtn) toggleBtn.setAttribute('aria-expanded', 'false');
                        try {
                            localStorage.setItem('siap-sidebar-collapsed', 'true');
                        } catch (error) {
                            console.error('No se pudo guardar la preferencia del menú lateral.', error);
                        }
                    }
                }
            }
        });
    });
</script>
</body>
</html>