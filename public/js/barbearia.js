// Cartões e blocos de dados ganham inclinação 3D sem ser preciso marcar cada um no HTML.
document.querySelectorAll('.card, .detail, .barber-stat-card').forEach(function (el) {
    if (!el.hasAttribute('data-tilt')) {
        el.setAttribute('data-tilt', '');
        el.setAttribute('data-tilt-max', '7');
    }
});

// Palco 3D da página inicial: a cena segue o rato (ou o dedo) com profundidade.
(function () {
    var stage = document.querySelector('[data-stage]');
    var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    if (!stage || reduce) {
        return;
    }

    var frame = null;
    var timer = null;

    function move(x, y) {
        if (frame) {
            return;
        }

        frame = requestAnimationFrame(function () {
            frame = null;
            var box = stage.getBoundingClientRect();
            var px = Math.max(-1, Math.min(1, ((x - box.left) / box.width - 0.5) * 2));
            var py = Math.max(-1, Math.min(1, ((y - box.top) / box.height - 0.5) * 2));

            stage.classList.add('is-moving');
            stage.style.setProperty('--px', px.toFixed(3));
            stage.style.setProperty('--py', py.toFixed(3));

            clearTimeout(timer);
            timer = setTimeout(function () { stage.classList.remove('is-moving'); }, 120);
        });
    }

    window.addEventListener('pointermove', function (event) { move(event.clientX, event.clientY); }, { passive: true });

    window.addEventListener('pointerleave', function () {
        stage.style.setProperty('--px', '0');
        stage.style.setProperty('--py', '0');
    });
})();

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
/* ---------- Barber Walk-in & Availability Toggles ---------- */
document.addEventListener('DOMContentLoaded', () => {
    // 1. Alternância "Já tem conta" vs "Sem conta (walk-in)"
    const bookingRadios = document.querySelectorAll('[data-booking-toggle]');
    const existingSection = document.getElementById('section-existing-customer');
    const walkinSection = document.getElementById('section-walkin-customer');

    if (bookingRadios.length && existingSection && walkinSection) {
        const customerSelect = document.getElementById('customer_id');
        const walkinName = document.getElementById('walkin_name');
        const walkinPhone = document.getElementById('walkin_phone');

        const syncBookingFields = () => {
            const selected = document.querySelector('[data-booking-toggle]:checked')?.value;
            const isWalkin = selected === 'walkin';

            existingSection.classList.toggle('is-hidden', isWalkin);
            walkinSection.classList.toggle('is-hidden', !isWalkin);

            if (customerSelect) customerSelect.disabled = isWalkin;
            if (walkinName) walkinName.disabled = !isWalkin;
            if (walkinPhone) walkinPhone.disabled = !isWalkin;
        };

        bookingRadios.forEach((radio) => radio.addEventListener('change', syncBookingFields));
        syncBookingFields();
    }

    // 2. Alternância "Dia inteiro" nos bloqueios de horário
    const allDayCheckbox = document.querySelector('[data-all-day-toggle]');
    const timeFieldsWrap = document.getElementById('block-time-fields');

    if (allDayCheckbox && timeFieldsWrap) {
        const timeInputs = timeFieldsWrap.querySelectorAll('input[type="time"]');
        const syncAllDay = () => {
            const isAllDay = allDayCheckbox.checked;
            timeFieldsWrap.classList.toggle('is-hidden', isAllDay);
            timeInputs.forEach((input) => {
                input.disabled = isAllDay;
                if (isAllDay) input.value = '';
            });
        };

        allDayCheckbox.addEventListener('change', syncAllDay);
        syncAllDay();
    }
});