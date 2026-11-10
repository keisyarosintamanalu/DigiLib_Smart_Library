(() => {
  'use strict';

  const menuButton = document.querySelector('[data-landing-menu]');
  const menu = document.getElementById('landing-nav-links');
  if (!menuButton || !menu) return;

  const closeMenu = () => {
    menu.classList.remove('is-open');
    menuButton.setAttribute('aria-expanded', 'false');
    menuButton.setAttribute('aria-label', 'Buka navigasi');
  };

  menuButton.addEventListener('click', () => {
    const isOpen = menu.classList.toggle('is-open');
    menuButton.setAttribute('aria-expanded', String(isOpen));
    menuButton.setAttribute('aria-label', isOpen ? 'Tutup navigasi' : 'Buka navigasi');
  });

  menu.querySelectorAll('a[href^="#"]').forEach(link => link.addEventListener('click', closeMenu));
  document.addEventListener('keydown', event => {
    if (event.key === 'Escape') closeMenu();
  });
})();