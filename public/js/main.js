document.addEventListener('DOMContentLoaded', function() {
    const burger = document.getElementById('burger');
    const nav = document.getElementById('nav');
    if (burger && nav) {
        burger.addEventListener('click', () => nav.classList.toggle('active'));
    }

    const track = document.querySelector('.slider-track');
    if (track) {
        const slides = track.querySelectorAll('.slide');
        const prevBtn = document.querySelector('.slider-btn.prev');
        const nextBtn = document.querySelector('.slider-btn.next');
        const dotsContainer = document.querySelector('.slider-dots');
        let current = 0;
        let autoTimer;

        if (dotsContainer) {
            slides.forEach((_, i) => {
                const dot = document.createElement('span');
                dot.classList.add('dot');
                if (i === 0) dot.classList.add('active');
                dot.addEventListener('click', () => goTo(i));
                dotsContainer.appendChild(dot);
            });
        }

        function goTo(index) {
            current = index;
            if (current < 0) current = slides.length - 1;
            if (current >= slides.length) current = 0;
            track.style.transform = `translateX(-${current * 100}%)`;
            document.querySelectorAll('.dot').forEach((d, i) => d.classList.toggle('active', i === current));
            resetAuto();
        }

        function next() { goTo(current + 1); }
        function prev() { goTo(current - 1); }

        if (nextBtn) nextBtn.addEventListener('click', next);
        if (prevBtn) prevBtn.addEventListener('click', prev);

        function resetAuto() {
            clearInterval(autoTimer);
            autoTimer = setInterval(next, 3000);
        }
        resetAuto();
    }

    document.querySelectorAll('input[name="banquet_date"]').forEach(input => {
        input.addEventListener('input', function(e) {
            let v = e.target.value.replace(/\D/g, '');
            if (v.length > 8) v = v.slice(0, 8);
            if (v.length >= 5) v = v.slice(0, 2) + '.' + v.slice(2, 4) + '.' + v.slice(4);
            else if (v.length >= 3) v = v.slice(0, 2) + '.' + v.slice(2);
            e.target.value = v;
        });
    });
});

async function checkAuth() {
    try {
        const res = await fetch('/api/me');
        return await res.json();
    } catch {
        return { loggedIn: false };
    }
}

function updateNav(auth) {
    const nav = document.getElementById('nav');
    if (!nav) return;
    let html = '<a href="/">Главная</a>';
    if (auth.isAdmin) {
        html += '<a href="/admin.html">Панель администратора</a><a href="#" id="logout-btn">Выход</a>';
    } else if (auth.loggedIn) {
        html += '<a href="/cabinet.html">Личный кабинет</a><a href="/application.html">Новая заявка</a><a href="#" id="logout-btn">Выход</a>';
    } else {
        html += '<a href="/login.html">Вход</a><a href="/register.html">Регистрация</a>';
    }
    nav.innerHTML = html;

    const logoutBtn = document.getElementById('logout-btn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', async (e) => {
            e.preventDefault();
            await fetch('/api/logout', { method: 'POST' });
            window.location.href = '/';
        });
    }
}
