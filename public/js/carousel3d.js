// Carrossel 3D (coverflow). Marcação: resources/views/components/service-carousel.blade.php
// Teclado: setas, Home e End. Rato/dedo: arrastar, ou clicar numa foto ao lado.
(function () {
    var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    document.querySelectorAll('[data-c3d]').forEach(init);

    function init(root) {
        var stage = root.querySelector('[data-c3d-stage]');
        var slides = [].slice.call(root.querySelectorAll('[data-c3d-slide]'));
        var dots = [].slice.call(root.querySelectorAll('.c3d__dot'));
        var live = root.querySelector('[data-c3d-live]');
        var toggle = root.querySelector('[data-c3d-toggle]');
        var total = slides.length;
        var originals = dots.length || 1;
        var autoplayMs = parseInt(root.getAttribute('data-autoplay'), 10) || 0;
        var active = 0;
        var timer = null;
        var userPaused = reduce;
        var hovering = false;
        var visible = true;

        if (total < 2) {
            slides[0] && slides[0].style.setProperty('--o', 0) && slides[0].style.setProperty('--abs', 0);
            return;
        }

        function offset(i) {
            var o = i - active;
            if (o > total / 2) { o -= total; }
            if (o < -total / 2) { o += total; }
            return o;
        }

        function render(announce) {
            slides.forEach(function (slide, i) {
                var o = offset(i);
                var abs = Math.abs(o);

                slide.style.setProperty('--o', o);
                slide.style.setProperty('--abs', abs);
                slide.classList.toggle('is-active', o === 0);
                slide.classList.toggle('is-far', abs > 2);

                if (!slide.hasAttribute('data-clone')) {
                    slide.setAttribute('aria-hidden', o === 0 ? 'false' : 'true');
                }
            });

            dots.forEach(function (dot, i) {
                dot.classList.toggle('is-on', i === active % originals);
            });

            if (announce && live) {
                live.textContent = 'Foto ' + ((active % originals) + 1) + ' de ' + originals;
            }
        }

        function go(to, announce) {
            active = (to + total) % total;
            render(announce);
        }

        function next() { go(active + 1, true); }
        function prev() { go(active - 1, true); }

        // ---- botões e pontos
        root.querySelector('[data-c3d-next]').addEventListener('click', function () { next(); restart(); });
        root.querySelector('[data-c3d-prev]').addEventListener('click', function () { prev(); restart(); });

        dots.forEach(function (dot, i) {
            dot.addEventListener('click', function () { go(i, true); restart(); });
        });

        // ---- clicar numa foto ao lado traz-a para o centro
        slides.forEach(function (slide, i) {
            slide.addEventListener('click', function () {
                if (moved) { return; }
                if (i !== active) { go(i, true); restart(); }
            });
        });

        // ---- teclado
        stage.addEventListener('keydown', function (event) {
            if (event.key === 'ArrowRight') { next(); restart(); event.preventDefault(); }
            else if (event.key === 'ArrowLeft') { prev(); restart(); event.preventDefault(); }
            else if (event.key === 'Home') { go(0, true); restart(); event.preventDefault(); }
            else if (event.key === 'End') { go(originals - 1, true); restart(); event.preventDefault(); }
        });

        // ---- arrastar
        var startX = null;
        var moved = false;

        stage.addEventListener('pointerdown', function (event) {
            startX = event.clientX;
            moved = false;
            stage.classList.add('is-dragging');
        });

        window.addEventListener('pointermove', function (event) {
            if (startX === null) { return; }
            if (Math.abs(event.clientX - startX) > 8) { moved = true; }
        });

        window.addEventListener('pointerup', function (event) {
            if (startX === null) { return; }

            var delta = event.clientX - startX;
            startX = null;
            stage.classList.remove('is-dragging');

            if (Math.abs(delta) > 40) {
                delta < 0 ? next() : prev();
                restart();
            }

            // o "click" que se segue ao arrastar não deve contar
            setTimeout(function () { moved = false; }, 0);
        });

        // ---- rotação automática (só no carrossel grande e sem "movimento reduzido")
        function tick() {
            if (!userPaused && !hovering && visible && !document.hidden) { next(); }
        }

        function restart() {
            clearInterval(timer);
            if (autoplayMs && !userPaused) { timer = setInterval(tick, autoplayMs); }
        }

        if (autoplayMs) {
            root.addEventListener('pointerenter', function () { hovering = true; });
            root.addEventListener('pointerleave', function () { hovering = false; });
            root.addEventListener('focusin', function () { hovering = true; });
            root.addEventListener('focusout', function () { hovering = false; });

            if ('IntersectionObserver' in window) {
                new IntersectionObserver(function (entries) {
                    visible = entries[0].isIntersecting;
                }).observe(root);
            }

            if (toggle) {
                toggle.addEventListener('click', function () {
                    userPaused = !userPaused;
                    toggle.setAttribute('aria-pressed', userPaused ? 'true' : 'false');
                    toggle.setAttribute('aria-label', userPaused ? 'Retomar rotação automática' : 'Pausar rotação automática');
                    toggle.innerHTML = userPaused ? '&#9654;' : '&#10074;&#10074;';
                    restart();
                });

                if (reduce) {
                    toggle.setAttribute('aria-pressed', 'true');
                    toggle.setAttribute('aria-label', 'Retomar rotação automática');
                    toggle.innerHTML = '&#9654;';
                }
            }
        }

        render(false);
        restart();
    }
})();
