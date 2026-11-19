'use strict';

const menuButton = document.querySelector('[data-menu-toggle]');
if (menuButton) {
  const menu = document.getElementById(menuButton.getAttribute('aria-controls'));
  if (!menu) {
    throw new Error('Navigation menu element is missing.');
  }
  if (window.matchMedia('(max-width: 640px)').matches) {
    menuButton.setAttribute('aria-expanded', 'false');
    menu.hidden = true;
  }
  menuButton.addEventListener('click', () => {
    const expanded = menuButton.getAttribute('aria-expanded') === 'true';
    menuButton.setAttribute('aria-expanded', String(!expanded));
    menu.hidden = expanded;
  });
}
