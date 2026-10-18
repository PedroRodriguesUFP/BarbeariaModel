// Inclinação 3D ao passar o rato. Só em dispositivos com rato e sem "movimento reduzido".
(function () {
    var canHover = window.matchMedia('(hover: hover) and (pointer: fine)').matches;
    var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    if (!canHover || reduce) {
        return;
    }

    document.querySelectorAll('[data-tilt]').forEach(function (el) {
        var max = parseFloat(el.getAttribute('data-tilt-max')) || 6;
        var frame = null;

        function reset() {
            el.classList.remove('is-tilting');
            el.style.setProperty('--rx', '0deg');
            el.style.setProperty('--ry', '0deg');
        }

        el.addEventListener('pointermove', function (event) {
            if (frame) {
                return;
            }

            frame = requestAnimationFrame(function () {
                frame = null;

                var box = el.getBoundingClientRect();
                var x = (event.clientX - box.left) / box.width - 0.5;
                var y = (event.clientY - box.top) / box.height - 0.5;

                el.classList.add('is-tilting');
                el.style.setProperty('--mx', ((x + 0.5) * 100).toFixed(0) + '%');
                el.style.setProperty('--my', ((y + 0.5) * 100).toFixed(0) + '%');
                el.style.setProperty('--rx', (-y * max).toFixed(2) + 'deg');
                el.style.setProperty('--ry', (x * max).toFixed(2) + 'deg');
            });
        });

        el.addEventListener('pointerleave', reset);
        el.addEventListener('blur', reset, true);
    });
})();
