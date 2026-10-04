(function () {
    'use strict';

    var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var touch = window.matchMedia('(hover: none)').matches;

    // Линии подсветки «проявляются» по мере прокрутки
    var decos = [].slice.call(document.querySelectorAll('.fx-deco'));
    var ticking = false;
    function updateLines() {
        ticking = false;
        var vh = window.innerHeight;
        decos.forEach(function (d) {
            var r = d.getBoundingClientRect();
            var p = (vh * 0.95 - r.top) / (r.height * 0.8 + vh * 0.3);
            d.style.setProperty('--p', Math.max(0, Math.min(1, p)).toFixed(3));
        });
    }
    if (decos.length) {
        if (reduce) {
            decos.forEach(function (d) { d.style.setProperty('--p', '1'); });
        } else {
            updateLines();
            window.addEventListener('scroll', function () {
                if (!ticking) { ticking = true; requestAnimationFrame(updateLines); }
            }, { passive: true });
            window.addEventListener('resize', updateLines);
        }
    }

    // Галерея: лайтбокс
    var works = [].slice.call(document.querySelectorAll('#works .work'));
    var lb = document.getElementById('lb');
    if (works.length && lb) {
        var data = works.map(function (w) {
            return { src: w.querySelector('img').currentSrc || w.querySelector('img').src,
                     t: w.querySelector('b').textContent, d: w.querySelector('small').textContent };
        });
        var cur = 0, lbImg = document.getElementById('lbImg');
        var show = function (i) {
            cur = (i + data.length) % data.length;
            lbImg.src = data[cur].src;
            document.getElementById('lbT').textContent = data[cur].t;
            document.getElementById('lbD').textContent = data[cur].d;
            document.getElementById('lbC').textContent = ('0' + (cur + 1)).slice(-2) + ' / ' + ('0' + data.length).slice(-2);
        };
        var open = function (i) { show(i); lb.classList.add('open'); lb.setAttribute('aria-hidden', 'false'); document.body.style.overflow = 'hidden'; };
        var close = function () { lb.classList.remove('open'); lb.setAttribute('aria-hidden', 'true'); document.body.style.overflow = ''; };
        works.forEach(function (w, i) { w.addEventListener('click', function () { open(i); }); });
        lb.querySelector('.lb-x').addEventListener('click', close);
        lb.querySelector('.lb-p').addEventListener('click', function () { show(cur - 1); });
        lb.querySelector('.lb-n').addEventListener('click', function () { show(cur + 1); });
        lb.addEventListener('click', function (e) { if (e.target === lb) close(); });
        document.addEventListener('keydown', function (e) {
            if (!lb.classList.contains('open')) return;
            if (e.key === 'Escape') close();
            if (e.key === 'ArrowLeft') show(cur - 1);
            if (e.key === 'ArrowRight') show(cur + 1);
        });
        var sx = null;
        lb.addEventListener('touchstart', function (e) { sx = e.touches[0].clientX; }, { passive: true });
        lb.addEventListener('touchend', function (e) {
            if (sx === null) return;
            var dx = e.changedTouches[0].clientX - sx; sx = null;
            if (Math.abs(dx) > 50) show(cur + (dx < 0 ? 1 : -1));
        }, { passive: true });
    }

    // Подсветка внутри карточек: позиция курсора в CSS-переменных
    document.addEventListener('pointermove', function (e) {
        var cards = document.querySelectorAll('.glass-card');
        for (var i = 0; i < cards.length; i++) {
            var r = cards[i].getBoundingClientRect();
            cards[i].style.setProperty('--mx', (e.clientX - r.left) + 'px');
            cards[i].style.setProperty('--my', (e.clientY - r.top) + 'px');
        }
    }, { passive: true });

    // Подсветка вокруг курсора с лёгким запаздыванием
    if (touch) return;
    var glow = document.createElement('div');
    glow.className = 'cursor-glow';
    document.body.appendChild(glow);
    var tx = 0, ty = 0, x = 0, y = 0, running = false;
    function loop() {
        x += (tx - x) * (reduce ? 1 : 0.15);
        y += (ty - y) * (reduce ? 1 : 0.15);
        glow.style.transform = 'translate3d(' + x + 'px,' + y + 'px,0)';
        if (Math.abs(tx - x) > 0.1 || Math.abs(ty - y) > 0.1) { requestAnimationFrame(loop); } else { running = false; }
    }
    document.addEventListener('pointermove', function (e) {
        tx = e.clientX; ty = e.clientY;
        glow.classList.add('visible');
        if (!running) { running = true; requestAnimationFrame(loop); }
    }, { passive: true });
    document.addEventListener('mouseleave', function () { glow.classList.remove('visible'); });
})();
