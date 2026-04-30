(function () {
    const sidebar     = document.getElementById('sidebar');
    const mainContent = document.getElementById('mainContent');
    if (!sidebar) return;

    const KEY = 'chimsiq_sidebar_collapsed';
    const isMobile = () => window.innerWidth <= 768;

    if (!isMobile() && localStorage.getItem(KEY) === '1') {
        sidebar.classList.add('collapsed');
        if (mainContent) mainContent.classList.add('expanded');
    }

    window.toggleSidebar = function () {
        if (isMobile()) {
            sidebar.classList.toggle('mobile-open');
        } else {
            const collapsed = sidebar.classList.toggle('collapsed');
            if (mainContent) mainContent.classList.toggle('expanded', collapsed);
            localStorage.setItem(KEY, collapsed ? '1' : '0');
        }
    };

    document.addEventListener('click', function (e) {
        if (isMobile() && sidebar.classList.contains('mobile-open')) {
            if (!sidebar.contains(e.target)) {
                sidebar.classList.remove('mobile-open');
            }
        }
    });
})();