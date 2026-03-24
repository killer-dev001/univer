document.addEventListener('DOMContentLoaded', function () {

    const menuBtn     = document.getElementById('menuBtn');
    const sidebar     = document.getElementById('sidebar');
    const overlay     = document.getElementById('overlay');
    const sidebarClose = document.getElementById('sidebarClose');

    function openSidebar() {
        sidebar.classList.add('open');
        overlay.classList.add('show');
        document.body.style.overflow = 'hidden';
    }

    function closeSidebar() {
        sidebar.classList.remove('open');
        overlay.classList.remove('show');
        document.body.style.overflow = '';
    }

    if (menuBtn)      menuBtn.addEventListener('click', openSidebar);
    if (sidebarClose) sidebarClose.addEventListener('click', closeSidebar);
    if (overlay)      overlay.addEventListener('click', closeSidebar);

    // Close sidebar on nav item click (mobile)
    document.querySelectorAll('.nav-item').forEach(item => {
        item.addEventListener('click', () => {
            if (window.innerWidth <= 1024) closeSidebar();
        });
    });

    // Close on resize if desktop
    window.addEventListener('resize', () => {
        if (window.innerWidth > 1024) closeSidebar();
    });
});
