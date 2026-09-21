(function () {
  'use strict';
  var menu = document.querySelector('.site-mobile-nav');
  if (!menu) return;
  var trigger = menu.querySelector('summary');
  var panel = menu.querySelector('nav');
  var closeButton = menu.querySelector('.site-nav-dismiss');
  var main = document.getElementById('main');
  var previousInert = main ? main.hasAttribute('inert') : false;
  var mobile = window.matchMedia('(max-width: 1000px)');
  function close() {
    menu.open = false;
    sync();
    if (mobile.matches) trigger.focus();
  }
  function sync() {
    var open = menu.open && mobile.matches;
    trigger.setAttribute('aria-expanded', String(open));
    document.body.classList.toggle('site-nav-is-open', open);
    if (main && !previousInert) {
      if (open) main.setAttribute('inert', '');
      else main.removeAttribute('inert');
    }
    if (open) closeButton.focus();
  }
  menu.addEventListener('toggle', sync);
  closeButton.addEventListener('click', close);
  menu.querySelector('.site-nav-backdrop').addEventListener('click', close);
  document.addEventListener('keydown', function (event) {
    if (!menu.open || !mobile.matches) return;
    if (event.key === 'Escape') {
      event.preventDefault();
      close();
    }
    if (event.key === 'Tab') {
      var items = panel.querySelectorAll('button, a[href]');
      var first = items[0];
      var last = items[items.length - 1];
      if (event.shiftKey && document.activeElement === first) {
        event.preventDefault(); last.focus();
      } else if (!event.shiftKey && document.activeElement === last) {
        event.preventDefault(); first.focus();
      }
    }
  });
  window.addEventListener('resize', function () {
    if (!mobile.matches) close();
  });
  window.addEventListener('pageshow', function () { menu.open = false; sync(); });
  sync();
}());
