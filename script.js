(function () {
    'use strict';

    // Mobile navigation toggle
    var toggle = document.querySelector('.nav-toggle');
    var nav = document.getElementById('site-nav');
    if (toggle && nav) {
        toggle.addEventListener('click', function () {
            var open = nav.classList.toggle('is-open');
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
    }

    // Product gallery: click a thumbnail to swap the main image
    var main = document.getElementById('gallery-main-img');
    var thumbs = document.querySelectorAll('.thumbs button');
    thumbs.forEach(function (btn) {
        btn.addEventListener('click', function () {
            if (!main) { return; }
            main.src = btn.getAttribute('data-full');
            thumbs.forEach(function (b) { b.classList.remove('is-active'); });
            btn.classList.add('is-active');
        });
    });

    // Admin: confirm destructive actions
    document.querySelectorAll('[data-confirm]').forEach(function (el) {
        el.addEventListener('submit', function (ev) {
            if (!window.confirm(el.getAttribute('data-confirm'))) { ev.preventDefault(); }
        });
    });

    // Admin: preview a selected main image
    var fileInput = document.getElementById('image-input');
    var preview = document.getElementById('image-preview');
    if (fileInput && preview) {
        fileInput.addEventListener('change', function () {
            var f = fileInput.files && fileInput.files[0];
            if (f) { preview.src = URL.createObjectURL(f); preview.hidden = false; }
        });
    }
})();
