<!DOCTYPE html>
<html lang="uz" class="scroll-smooth bg-paper">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Narxla — Telefon, planshet va noutbukning haqiqiy narxini AI bilan bilib oling')</title>
    <meta name="description" content="@yield('meta_description', 'Narxla — sun\'iy intellekt yordamida telefon, planshet va noutbukning bozor narxini soniyalarda aniqlaydi.')">

    <link rel="icon" type="image/jpeg" href="{{ asset('images/logo.jpg') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/logo.jpg') }}">

    {{-- Google Fonts: Inter (matn) + Poppins (sarlavhalar) --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@500;600;700;800&display=swap" rel="stylesheet">

    {{-- FontAwesome 6 --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

    {{-- Tailwind CSS (Vite orqali quriladi — @theme resources/css/app.css da) --}}
    @vite('resources/css/app.css')

    @stack('styles')

    <script>document.documentElement.classList.add('js-anim');</script>

    <style>
        [x-cloak] { display: none !important; }

        @keyframes line-up {
            from { transform: translateY(112%); }
            to { transform: translateY(0); }
        }
        @keyframes nav-squash {
            0% { transform: translateY(-16px) scale(0.97); opacity: 0; }
            60% { transform: translateY(2px) scale(1.005); opacity: 1; }
            100% { transform: translateY(0) scale(1); opacity: 1; }
        }
        @keyframes letter-in {
            from { opacity: 0; transform: translateY(-105%) rotateX(-70deg); }
            to { opacity: 1; transform: none; }
        }
        @keyframes nav-item-in {
            from { opacity: 0; transform: translateX(26px); }
            to { opacity: 1; transform: none; }
        }
        @keyframes draw-x {
            from { transform: scaleX(0); }
            to { transform: scaleX(1); }
        }

        /* Navbar animatsiyalari — faqat klass orqali boshlanadi,
           hero-ready qo'shilmasa ham elementlar ko'rinib turadi */
        .hero-ready .nav-squash {
            transform-origin: top;
            animation: nav-squash 0.85s cubic-bezier(0.34, 1.4, 0.64, 1) both;
        }

        .hero-ready .logo-letter {
            animation: letter-in 0.7s cubic-bezier(0.22, 1, 0.36, 1) both;
            animation-delay: calc(0.2s + var(--i) * 0.055s);
        }

        .hero-ready .nav-item {
            animation: nav-item-in 0.7s cubic-bezier(0.22, 1, 0.36, 1) both;
            animation-delay: calc(0.4s + var(--nav-i, 0) * 0.09s);
        }

        .hero-ready .nav-line { animation: draw-x 1.2s cubic-bezier(0.65, 0, 0.35, 1) 0.1s both; }

        /* Siljitiladigan pill indikator.
           Joylashuv to'liq transform orqali boshqariladi — Tailwind v4 ning
           translate xossasi bilan to'qnashib, ikki marta siljib ketmasligi uchun. */
        .nav-pill {
            top: 50%;
            transform: translate3d(0, -50%, 0);
            transition: transform 0.35s cubic-bezier(0.22, 1, 0.36, 1),
                        width 0.35s cubic-bezier(0.22, 1, 0.36, 1),
                        height 0.3s cubic-bezier(0.22, 1, 0.36, 1),
                        opacity 0.25s ease;
        }

        /* --- Sahifa o'tish indikatori --- */
        .page-progress {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            z-index: 50;
            height: 3px;
            pointer-events: none;
            opacity: 0;
            overflow: hidden;
            border-top-left-radius: 9999px;
            border-top-right-radius: 9999px;
            transition: opacity 0.15s ease;
            background: rgba(20, 20, 18, 0.05);
        }
        .page-progress-fixed {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 100;
            height: 3px;
            border-radius: 0;
        }
        .page-progress span {
            display: block;
            height: 100%;
            width: 100%;
            transform-origin: left center;
            transform: scaleX(0);
            background: #141412 !important;
            box-shadow: 0 0 10px rgba(20, 20, 18, 0.45);
        }

        html[data-navigating] .page-progress { opacity: 1; }
        html[data-navigating] .page-progress span {
            animation: page-progress-run 3.2s cubic-bezier(0.1, 0.8, 0.25, 1) forwards;
        }
        @keyframes page-progress-run {
            0% { transform: scaleX(0.08); }
            20% { transform: scaleX(0.5); }
            55% { transform: scaleX(0.78); }
            100% { transform: scaleX(0.96); }
        }

        /* Bosilgan tugma ichidagi loader — sahifa almashayotganini bildiradi */
        [data-nav-loader] { position: relative; }
        [data-nav-loader].is-loading { pointer-events: none; }
        [data-nav-loader].is-loading > * { visibility: hidden; }
        [data-nav-loader].is-loading::after {
            content: '';
            position: absolute;
            inset: 0;
            margin: auto;
            width: 1rem;
            height: 1rem;
            border-radius: 9999px;
            border: 2px solid currentColor;
            border-top-color: transparent;
            animation: nav-spin 0.65s linear infinite;
        }
        @keyframes nav-spin { to { transform: rotate(360deg); } }

        /* GPU ga ko'chirish — transform animatsiyalari silliq ishlashi uchun */
        .gpu {
            transform: translateZ(0);
            backface-visibility: hidden;
        }
        @keyframes rise-in {
            from { opacity: 0; transform: translateY(28px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes underline-loop {
            0% { stroke-dashoffset: 1; }
            38% { stroke-dashoffset: 0; }
            70% { stroke-dashoffset: 0; }
            100% { stroke-dashoffset: -1; }
        }
        @keyframes ai-pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.72; }
        }

        /* Hero: satrlar niqobdan chiqadi (fadeless) */
        .js-anim .hero-line { transform: translateY(112%); }
        .hero-ready .hero-line { animation: line-up 1s cubic-bezier(0.22, 1, 0.36, 1) both; }
        .hero-ready [data-anim-title] > span:nth-child(1) .hero-line { animation-delay: 0.05s; }
        .hero-ready [data-anim-title] > span:nth-child(2) .hero-line { animation-delay: 0.14s; }
        .hero-ready [data-anim-title] > span:nth-child(3) .hero-line { animation-delay: 0.23s; }

        .js-anim [data-anim-underline] { stroke-dasharray: 1; stroke-dashoffset: 1; }
        /* "AI yordamida" — doimiy takrorlanuvchi animatsiya */
        .hero-ready [data-anim-underline] {
            animation: underline-loop 3.4s cubic-bezier(0.65, 0, 0.35, 1) 0.55s infinite both;
        }
        .hero-ready .ai-word { animation: ai-pulse 2.8s ease-in-out infinite; }

        .js-anim [data-anim-text] { opacity: 0; }
        .hero-ready [data-anim-text] { animation: rise-in 0.8s cubic-bezier(0.22, 1, 0.36, 1) 0.45s both; }
        .hero-ready [data-anim-text]:nth-of-type(2) { animation-delay: 0.9s; }

        .js-anim [data-anim-cta] { opacity: 0; }
        .hero-ready [data-anim-cta] { animation: rise-in 0.8s cubic-bezier(0.22, 1, 0.36, 1) 0.6s both; }

        /* Scroll'da ko'rinadigan bloklar */
        .js-anim [data-reveal] {
            opacity: 0;
            transform: translateY(56px);
            transition: opacity 0.9s cubic-bezier(0.22, 1, 0.36, 1), transform 0.9s cubic-bezier(0.22, 1, 0.36, 1);
            transition-delay: var(--reveal-delay, 0s);
        }
        .js-anim [data-reveal].is-visible { opacity: 1; transform: none; }

        /* Scroll'da chiqadigan sarlavhalar */
        .js-anim [data-mask] .mask-line { transform: translateY(112%); }
        .js-anim [data-mask].is-visible .mask-line { animation: line-up 0.95s cubic-bezier(0.22, 1, 0.36, 1) both; }

        /* Brendlar lentasi (marquee) */
        .marquee-mask {
            -webkit-mask-image: linear-gradient(to right, transparent, #000 12%, #000 88%, transparent);
            mask-image: linear-gradient(to right, transparent, #000 12%, #000 88%, transparent);
        }
        .marquee-track { animation: narxla-marquee 34s linear infinite; will-change: transform; }
        @keyframes narxla-marquee { to { transform: translateX(-50%); } }

        /* Shisha (glassmorphism) kartochka */
        .glass {
            background: rgba(255, 255, 255, 0.55);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
        }

        /* Batareya slideri */
        input[type='range'].range {
            -webkit-appearance: none;
            appearance: none;
            height: 6px;
            border-radius: 9999px;
            outline: none;
            cursor: pointer;
        }
        input[type='range'].range::-webkit-slider-thumb {
            -webkit-appearance: none;
            appearance: none;
            width: 22px;
            height: 22px;
            border-radius: 9999px;
            background: #1A1A19;
            border: 4px solid #FFFFFF;
            box-shadow: 0 6px 16px rgba(20, 20, 18, 0.3);
        }
        input[type='range'].range::-moz-range-thumb {
            width: 22px;
            height: 22px;
            border-radius: 9999px;
            background: #1A1A19;
            border: 4px solid #FFFFFF;
            box-shadow: 0 6px 16px rgba(20, 20, 18, 0.3);
        }

        /* Scrollbar */
        ::-webkit-scrollbar { width: 10px; }
        ::-webkit-scrollbar-track { background: #F7F6F3; }
        ::-webkit-scrollbar-thumb {
            background: #D2CFC4;
            border-radius: 9999px;
            border: 2px solid #F7F6F3;
        }
        ::-webkit-scrollbar-thumb:hover { background: #B4B4AC; }

        @media (prefers-reduced-motion: reduce) {
            .marquee-track { animation: none; }
            .nav-squash, .logo-letter, .nav-item, .nav-line,
            .ai-word, [data-anim-underline] { animation: none; }
        }
    </style>
</head>
<body class="min-h-screen bg-paper font-sans text-ink-900 antialiased selection:bg-ink-950 selection:text-paper">

    @if (View::hasSection('hide-chrome'))
        {{-- Chrome yashirilgan sahifalarda (Login) tepada zaxira progress chizig'i --}}
        <div class="page-progress page-progress-fixed" aria-hidden="true"><span></span></div>
    @endif

    @if (! View::hasSection('hide-chrome'))
        @include('partials.navbar')
    @endif

    <main>
        @yield('content')
    </main>

    @if (! View::hasSection('hide-chrome'))
        @include('partials.footer')
    @endif

    {{-- Alpine.js (interaktivlik) --}}
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <script>
        (function () {
            const root = document.documentElement;

            const disableAnimations = function () {
                root.classList.remove('js-anim');
                root.classList.add('no-anim');
            };

            const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

            if (prefersReducedMotion || !('IntersectionObserver' in window)) {
                disableAnimations();
                return;
            }

            const startCount = function (element) {
                const target = parseFloat(element.dataset.count || '0');
                const duration = 1600;
                const startedAt = performance.now();

                const tick = function (now) {
                    const progress = Math.min(1, (now - startedAt) / duration);
                    const eased = 1 - Math.pow(1 - progress, 3);
                    const value = Math.round(target * eased)
                        .toString()
                        .replace(/\B(?=(\d{3})+(?!\d))/g, ' ');

                    element.textContent = value;

                    if (progress < 1) {
                        requestAnimationFrame(tick);
                    }
                };

                requestAnimationFrame(tick);
            };

            window.addEventListener('DOMContentLoaded', function () {
                const startHero = function () {
                    root.classList.add('hero-ready');
                };

                // Hero matni niqobdan chiqishidan oldin veb-shrift tayyor bo'lishini kutamiz —
                // aks holda fallback shriftda animatsiya boshlanib, shrift almashganda sakraydi.
                // Shrift kechiksa yoki yuklanmasa 700ms dan keyin baribir boshlanadi.
                if (document.fonts && document.fonts.ready) {
                    Promise.race([
                        document.fonts.ready,
                        new Promise(function (resolve) { window.setTimeout(resolve, 700); }),
                    ]).then(function () {
                        requestAnimationFrame(startHero);
                    });

                    // rAF fon tab'ida to'xtaydi — shuning uchun setTimeout zaxirasi ham qo'yiladi
                    window.setTimeout(startHero, 1200);
                } else {
                    requestAnimationFrame(startHero);
                    window.setTimeout(startHero, 400);
                }

                // Navbar skroll holati + progress chizig'i
                const navbar = document.querySelector('[data-navbar]');
                const progressBar = document.querySelector('[data-progress]');

                if (navbar || progressBar) {
                    let navTicking = false;

                    const syncNavbar = function () {
                        const y = window.scrollY;

                        if (navbar) {
                            const isScrolled = y > 12;
                            const hasFlag = navbar.hasAttribute('data-scrolled');

                            if (isScrolled !== hasFlag) {
                                if (isScrolled) {
                                    navbar.setAttribute('data-scrolled', 'true');
                                } else {
                                    navbar.removeAttribute('data-scrolled');
                                }
                            }
                        }

                        if (progressBar) {
                            const scrollable = document.documentElement.scrollHeight - window.innerHeight;
                            const ratio = scrollable > 0 ? Math.min(1, Math.max(0, y / scrollable)) : 0;

                            progressBar.style.transform = 'scaleX(' + ratio + ')';
                        }

                        navTicking = false;
                    };

                    // Skroll har bir freymda bitta marta — layout o'qish va yozish rAF ichida
                    const requestNavbarSync = function () {
                        if (!navTicking) {
                            navTicking = true;
                            requestAnimationFrame(syncNavbar);
                        }
                    };

                    window.addEventListener('scroll', requestNavbarSync, { passive: true });
                    window.addEventListener('resize', requestNavbarSync, { passive: true });
                    syncNavbar();
                }

                // Siljitiladigan pill indikator
                const navLinks = document.querySelector('[data-nav-links]');
                const pill = document.querySelector('[data-pill]');

                if (navLinks && pill) {
                    const pillTargets = navLinks.querySelectorAll('[data-nav-link]');

                    const movePill = function (target) {
                        if (!target) {
                            pill.style.opacity = '0';
                            return;
                        }

                        pill.style.opacity = '1';
                        pill.style.width = target.offsetWidth + 'px';
                        pill.style.height = target.offsetHeight + 'px';
                        pill.style.transform = 'translate3d(' + target.offsetLeft + 'px, -50%, 0)';
                    };

                    pillTargets.forEach(function (target) {
                        target.addEventListener('mouseenter', function () { movePill(target); });
                        target.addEventListener('focus', function () { movePill(target); });
                    });

                    navLinks.addEventListener('mouseleave', function () { movePill(null); });
                }

                const observer = new IntersectionObserver(function (entries) {
                    entries.forEach(function (entry) {
                        if (!entry.isIntersecting) {
                            return;
                        }

                        entry.target.classList.add('is-visible');

                        if (entry.target.hasAttribute('data-count')) {
                            startCount(entry.target);
                        }

                        observer.unobserve(entry.target);
                    });
                }, { threshold: 0.15, rootMargin: '0px 0px -6% 0px' });

                document.querySelectorAll('[data-reveal], [data-mask], [data-count]').forEach(function (element) {
                    // data-reveal-delay="0.12" → --reveal-delay: 0.12s (ketma-ket chiqish uchun)
                    if (element.hasAttribute('data-reveal-delay')) {
                        element.style.setProperty('--reveal-delay', element.dataset.revealDelay + 's');
                    }

                    observer.observe(element);
                });

                // Fon yozuvi skroll bilan sekin siljiydi
                const parallax = document.querySelector('[data-parallax]');

                if (parallax) {
                    let ticking = false;

                    const update = function () {
                        const rect = parallax.getBoundingClientRect();
                        const offset = (rect.top + rect.height / 2 - window.innerHeight / 2) * -0.06;

                        parallax.style.transform = 'translate3d(0,' + offset.toFixed(1) + 'px,0)';
                        ticking = false;
                    };

                    window.addEventListener('scroll', function () {
                        if (!ticking) {
                            ticking = true;
                            requestAnimationFrame(update);
                        }
                    }, { passive: true });

                    update();
                }
            });
        })();
    </script>

    {{-- Sahifa o'tish loaderi: bosilgan havola/tugma yuklanayotganini ko'rsatadi --}}
    <script>
        (function () {
            const root = document.documentElement;
            let navigating = false;

            const resetNavigation = function () {
                navigating = false;
                root.removeAttribute('data-navigating');

                document.querySelectorAll('[data-nav-loader].is-loading').forEach(function (element) {
                    element.classList.remove('is-loading');
                });
            };

            const startNavigation = function (target) {
                if (navigating) {
                    return;
                }

                navigating = true;
                root.setAttribute('data-navigating', '');

                const button = target && target.closest ? target.closest('[data-nav-loader]') : null;

                if (button) {
                    button.classList.add('is-loading');
                }
            };

            document.addEventListener('click', function (event) {
                if (event.defaultPrevented || event.button !== 0) {
                    return;
                }

                if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
                    return;
                }

                const link = event.target.closest ? event.target.closest('a[href]') : null;

                if (!link || link.target === '_blank' || link.hasAttribute('download') || link.hasAttribute('data-no-loader')) {
                    return;
                }

                const href = link.getAttribute('href') || '';

                if (href === '' || href.charAt(0) === '#' || /^(mailto:|tel:|javascript:)/i.test(href)) {
                    return;
                }

                if (link.origin !== window.location.origin) {
                    return;
                }

                startNavigation(link);
            });

            document.addEventListener('submit', function (event) {
                if (event.defaultPrevented || !(event.target instanceof HTMLFormElement)) {
                    return;
                }

                // Faqat sahifani almashtiradigan formalar (masalan, chiqish).
                if ((event.target.getAttribute('method') || 'get').toLowerCase() !== 'post') {
                    return;
                }

                startNavigation(event.target.querySelector('[data-nav-loader]'));
            });

            // Orqaga qaytganda (bfcache) holat tozalanadi.
            window.addEventListener('pageshow', function (event) {
                if (event.persisted) {
                    resetNavigation();
                }
            });
        })();
    </script>

    @stack('scripts')
</body>
</html>