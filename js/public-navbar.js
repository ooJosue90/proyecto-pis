(() => {
    'use strict';

    const navbar = document.querySelector('[data-public-navbar]');
    if (!navbar) return;

    const toggle = navbar.querySelector('[data-public-nav-toggle]');
    const menu = navbar.querySelector('[data-public-nav-menu]');
    const pageContent = document.querySelector('main');
    let scrollFrame = 0;

    const currentScrollY = () => Math.max(
        window.scrollY,
        document.documentElement.scrollTop,
        document.body.scrollTop,
        pageContent?.scrollTop || 0
    );

    const updateScrollState = () => {
        navbar.classList.toggle('is-scrolled', currentScrollY() > 50);
    };

    const handleScroll = () => {
        if (scrollFrame) return;
        scrollFrame = window.requestAnimationFrame(() => {
            updateScrollState();
            scrollFrame = 0;
        });
    };

    const closeMobileMenu = () => {
        if (!menu?.classList.contains('show')) return;

        if (window.bootstrap?.Collapse) {
            window.bootstrap.Collapse.getOrCreateInstance(menu, { toggle: false }).hide();
        } else {
            menu.classList.remove('show');
            toggle?.setAttribute('aria-expanded', 'false');
        }
    };

    window.addEventListener('scroll', handleScroll, { passive: true });
    document.addEventListener('scroll', handleScroll, { passive: true, capture: true });
    pageContent?.addEventListener('scroll', handleScroll, { passive: true });
    window.addEventListener('pageshow', updateScrollState);
    window.addEventListener('load', updateScrollState);
    window.addEventListener('hashchange', updateScrollState);
    updateScrollState();

    if (!toggle || !menu) return;

    menu.addEventListener('shown.bs.collapse', () => toggle.setAttribute('aria-expanded', 'true'));
    menu.addEventListener('hidden.bs.collapse', () => toggle.setAttribute('aria-expanded', 'false'));
    menu.querySelectorAll('a').forEach(link => link.addEventListener('click', closeMobileMenu));
})();
