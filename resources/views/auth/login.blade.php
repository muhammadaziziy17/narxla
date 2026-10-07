@extends('layouts.app')

@section('hide-chrome', '1')
@section('title', 'Tizimga kirish — Narxla')
@section('meta_description', 'Narxla tizimiga Google yoki Telegram orqali bir necha soniyada kiring.')

@section('content')
    {{-- Balandlik bo'yicha ritm clamp() da: past ekranda ixcham, baland ekranda keng --
         lg'da sahifa dvh ga qulflanadi — oddiy holatda scroll umuman chiqmaydi,
         juda past oynada esa kontent kesilib qolmasligi uchun ichki scroll zaxirasi bor --}}
    <div class="login-scroll relative flex min-h-dvh w-full flex-col bg-paper px-4 py-[clamp(1.25rem,3vh,2.75rem)]">
        {{-- Yumshoq fon dog'lari — kontentga tegilmaydi, chetlari shu yerda qirqiladi --}}
        <div aria-hidden="true" class="pointer-events-none absolute inset-0 -z-10 overflow-hidden bg-paper">
            <div class="absolute -left-24 -top-28 h-72 w-72 rounded-full bg-ink-900/[0.05] blur-3xl"></div>
            <div class="absolute -bottom-32 -right-20 h-80 w-80 rounded-full bg-ink-900/[0.04] blur-3xl"></div>
        </div>

        <div class="mx-auto flex w-full max-w-6xl flex-1 flex-col">
            {{-- Vertikal markazlashgan guruh (my-auto), pastki qator esa eng pastda turadi --}}
            <div class="my-auto">
                {{-- Yuqori qator --}}
                <div data-reveal class="flex items-center justify-between">
                    <a href="{{ route('home') }}" class="font-display text-xl font-semibold tracking-tight text-ink-950"
                       aria-label="Narxla — bosh sahifa">Narxla</a>
                    <a href="{{ route('home') }}" data-nav-loader
                       class="group inline-flex items-center gap-2 text-xs font-medium text-ink-400 transition-colors duration-500 hover:text-ink-950">
                        <span>Bosh sahifa</span>
                        <i class="fa-solid fa-arrow-right text-[10px] transition-transform duration-500 group-hover:translate-x-1"></i>
                    </a>
                </div>

                {{-- Sarlavha --}}
                <div data-reveal data-reveal-delay="0.06" class="mx-auto mt-[clamp(1.5rem,4vh,3.5rem)] max-w-2xl text-center">

                    <h1 class="mt-[clamp(0.75rem,1.8vh,1.5rem)] font-display text-3xl font-semibold tracking-tight text-ink-950 sm:text-4xl">
                        Qaysi biri sizga qulay ?
                    </h1>
                </div>

                @if (session('auth_error'))
                    <div data-reveal class="mx-auto mt-[clamp(1rem,2.4vh,1.5rem)] flex w-full max-w-md items-start gap-3 rounded-2xl border border-ink-900/10 bg-white px-4 py-3 text-xs leading-relaxed text-ink-600">
                        <i class="fa-solid fa-triangle-exclamation mt-0.5 text-ink-950"></i>
                        <p>{{ session('auth_error') }}</p>
                    </div>
                @endif

                {{-- Kirish usullari — bosilganda loader ko'rsatiladi --}}
                <div x-data="{ pending: null }"
                     class="login-cards-grid mx-auto mt-[clamp(1.5rem,4vh,3.5rem)] mb-[clamp(1.5rem,4vh,3.5rem)] grid w-full max-w-3xl items-stretch gap-5 md:grid-cols-2">
                    {{-- 01 — Google --}}
                    <article :class="pending && pending !== 'google' ? 'opacity-40' : ''"
                             class="login-card group relative flex flex-col rounded-4xl border border-ink-900/10 bg-white p-[clamp(1.25rem,2.2vh,1.75rem)] hover:border-ink-900/25">
                        <div class="login-card-inner flex items-center justify-between gap-3">
                            <span class="flex min-w-0 items-center gap-3">
                                <span class="login-card-icon grid h-10 w-10 shrink-0 place-items-center rounded-xl border border-ink-900/10 bg-paper-100 transition duration-500 group-hover:-translate-y-0.5 group-hover:scale-[1.06] group-hover:border-ink-900/20 group-hover:bg-white">
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" aria-hidden="true">
                                        <path fill="#4285F4" d="M23.49 12.27c0-.79-.07-1.54-.19-2.27H12v4.51h6.47c-.29 1.48-1.14 2.73-2.4 3.58v3h3.86c2.26-2.09 3.56-5.17 3.56-8.82z"/>
                                        <path fill="#34A853" d="M12 24c3.24 0 5.95-1.08 7.93-2.91l-3.86-3c-1.08.72-2.45 1.16-4.07 1.16-3.13 0-5.78-2.11-6.73-4.96H1.29v3.09C3.26 21.3 7.31 24 12 24z"/>
                                        <path fill="#FBBC05" d="M5.27 14.29A7.2 7.2 0 0 1 4.89 12c0-.8.14-1.57.38-2.29V6.62H1.29A12 12 0 0 0 0 12c0 1.94.46 3.77 1.29 5.38l3.98-3.09z"/>
                                        <path fill="#EA4335" d="M12 4.75c1.77 0 3.35.61 4.6 1.8l3.42-3.42C17.95 1.19 15.24 0 12 0 7.31 0 3.26 2.7 1.29 6.62l3.98 3.09C6.22 6.86 8.87 4.75 12 4.75z"/>
                                    </svg>
                                </span>
                                <h2 class="truncate font-display text-base font-semibold tracking-tight text-ink-950">Google bilan</h2>
                            </span>
                            <span class="login-card-num shrink-0 text-[11px] font-medium tracking-[0.18em] text-ink-300">01</span>
                        </div>

                        {{-- Google OAuth — haqiqiy oqim (app/Http/Controllers/Auth/GoogleAuthController) --}}
                        <div class="login-card-cta mt-auto pt-[clamp(1rem,2vh,1.5rem)]">
                            <a href="{{ route('auth.google.redirect') }}"
                               @click="pending = 'google'"
                               :class="pending ? 'pointer-events-none' : ''"
                               class="group/btn relative flex w-full items-center justify-center gap-2.5 overflow-hidden rounded-2xl bg-ink-950 px-5 py-3 text-sm font-semibold text-paper transition duration-500 hover:-translate-y-0.5 hover:bg-ink-900 active:scale-[0.98]">
                                <span class="pointer-events-none absolute inset-0 -translate-x-full bg-gradient-to-r from-transparent via-white/25 to-transparent transition-transform duration-1000 ease-out group-hover/btn:translate-x-full"></span>
                                <i x-show="pending === 'google'" x-cloak class="fa-solid fa-circle-notch fa-spin relative text-sm"></i>
                                <svg x-show="pending !== 'google'" class="relative h-4 w-4" viewBox="0 0 24 24" aria-hidden="true">
                                    <path fill="#fff" d="M23.49 12.27c0-.79-.07-1.54-.19-2.27H12v4.51h6.47c-.29 1.48-1.14 2.73-2.4 3.58v3h3.86c2.26-2.09 3.56-5.17 3.56-8.82z"/>
                                    <path fill="#fff" d="M12 24c3.24 0 5.95-1.08 7.93-2.91l-3.86-3c-1.08.72-2.45 1.16-4.07 1.16-3.13 0-5.78-2.11-6.73-4.96H1.29v3.09C3.26 21.3 7.31 24 12 24z"/>
                                    <path fill="#fff" fill-opacity=".7" d="M5.27 14.29A7.2 7.2 0 0 1 4.89 12c0-.8.14-1.57.38-2.29V6.62H1.29A12 12 0 0 0 0 12c0 1.94.46 3.77 1.29 5.38l3.98-3.09z"/>
                                    <path fill="#fff" fill-opacity=".7" d="M12 4.75c1.77 0 3.35.61 4.6 1.8l3.42-3.42C17.95 1.19 15.24 0 12 0 7.31 0 3.26 2.7 1.29 6.62l3.98 3.09C6.22 6.86 8.87 4.75 12 4.75z"/>
                                </svg>
                                <span x-show="pending !== 'google'" class="relative">Google bilan davom etish</span>
                                <span x-show="pending === 'google'" x-cloak class="relative">Google'ga o'tilmoqda…</span>
                            </a>
                        </div>
                    </article>

                    {{-- 02 — Telegram --}}
                    <article :class="pending && pending !== 'telegram' ? 'opacity-40' : ''"
                             class="login-card group relative flex flex-col rounded-4xl border border-ink-900/10 bg-white p-[clamp(1.25rem,2.2vh,1.75rem)] hover:border-ink-900/25"
                             style="--card-delay: 0.1s">
                        <div class="login-card-inner flex items-center justify-between gap-3">
                            <span class="flex min-w-0 items-center gap-3">
                                <span class="login-card-icon telegram-mark grid h-10 w-10 shrink-0 place-items-center rounded-xl transition duration-500 group-hover:-translate-y-0.5 group-hover:scale-[1.06] group-hover:brightness-110">
                                    <i class="fa-solid fa-paper-plane text-[15px] text-white" aria-hidden="true"></i>
                                </span>
                                <h2 class="truncate font-display text-base font-semibold tracking-tight text-ink-950">Telegram orqali</h2>
                            </span>
                            <span class="login-card-num shrink-0 text-[11px] font-medium tracking-[0.18em] text-ink-300">02</span>
                        </div>

                        {{-- Telegram OpenID Connect — haqiqiy oqim (app/Http/Controllers/Auth/TelegramAuthController) --}}
                        <div class="login-card-cta mt-auto pt-[clamp(1rem,2vh,1.5rem)]">
                            <a href="{{ route('auth.telegram.redirect') }}"
                               @click="pending = 'telegram'"
                               :class="pending ? 'pointer-events-none' : ''"
                               class="flex w-full items-center justify-center gap-2.5 rounded-2xl border border-ink-900/10 bg-paper-100 px-5 py-3 text-sm font-semibold text-ink-950 transition duration-500 hover:-translate-y-0.5 hover:border-ink-900/25 hover:bg-white hover:shadow-card active:scale-[0.98]">
                                <i x-show="pending === 'telegram'" x-cloak class="fa-solid fa-circle-notch fa-spin text-sm"></i>
                                <span x-show="pending !== 'telegram'" class="relative grid h-4 w-4 shrink-0 place-items-center" aria-hidden="true">
                                    <span class="telegram-mark absolute inset-0 rounded-full"></span>
                                    <i class="fa-solid fa-paper-plane relative text-[7px] text-white"></i>
                                </span>
                                <span x-show="pending !== 'telegram'">Telegram bilan kirish</span>
                                <span x-show="pending === 'telegram'" x-cloak>Telegram'ga o'tilmoqda…</span>
                            </a>
                        </div>
                    </article>

                </div>
            </div>

            {{-- Pastki qator — har doim eng pastda --}}
            <div data-reveal data-reveal-delay="0.24"
                 class="flex flex-col gap-4 border-t border-ink-900/10 pt-[clamp(1rem,2.2vh,1.75rem)] sm:flex-row sm:items-center sm:justify-between">
                <div class="login-optional flex flex-wrap items-center gap-x-6 gap-y-2 text-xs text-ink-400">
                    <span class="inline-flex items-center gap-2">
                        <i class="fa-solid fa-shield-halved text-ink-950"></i>
                        Parol saqlanmaydi
                    </span>
                    <span class="inline-flex items-center gap-2">
                        <i class="fa-solid fa-bolt text-ink-950"></i>
                        Birdan kirish
                    </span>
                    <span class="inline-flex items-center gap-2">
                        <i class="fa-solid fa-clock-rotate-left text-ink-950"></i>
                        Natijalarni saqlash
                    </span>
                </div>

                <p class="max-w-sm text-[11px] leading-relaxed text-ink-300 sm:ml-auto sm:text-right">
                    Davom etish bilan siz
                    <a href="#" class="font-medium text-ink-500 underline decoration-ink-900/20 underline-offset-2 transition-colors duration-500 hover:text-ink-950">foydalanish shartlari</a>
                    va
                    <a href="#" class="font-medium text-ink-500 underline decoration-ink-900/20 underline-offset-2 transition-colors duration-500 hover:text-ink-950">maxfiylik siyosati</a>ga
                    rozilik bildirasiz.
                </p>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        /* Past ekranlarda (noutbuk + brauzer paneli) dekorativ bloklar yashiriladi —
           shuning uchun sahifa o'lchamidan qat'i nazar scroll qilmaydi. */
        @media (max-height: 760px) {
            .login-optional { display: none; }
        }

        /* Zaxira holatida (juda past oyna) ichki scroll bo'lishi mumkin,
           lekin scrollbar ko'rinmaydi — sahifa "scroll qiladigan" ko'rinmaydi. */
        .login-scroll { scrollbar-width: none; }
        .login-scroll::-webkit-scrollbar { display: none; }

        /* Telegram brend belgisi — gradient fon (rasmiy ranglar) */
        .telegram-mark {
            background-image: linear-gradient(135deg, #2AABEE, #229ED9);
        }

        /* --- Kartalar: pog'onali chiqish (faqat nozik siljish, o'lcham o'zgarmaydi) --- */
        @keyframes login-card-in {
            from { opacity: 0; transform: translateY(16px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes login-fade-in {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        /* JS o'chirilgan bo'lsa kartalar darhol ko'rinadi */
        .js-anim .login-card:not(.is-visible) { opacity: 0; }

        .js-anim .login-card.is-visible {
            animation: login-card-in 0.5s cubic-bezier(0.22, 1, 0.36, 1) backwards;
            animation-delay: var(--card-delay, 0s);
        }

        .js-anim .login-card:not(.is-visible) .login-card-icon,
        .js-anim .login-card:not(.is-visible) .login-card-num { opacity: 0; }

        .js-anim .login-card.is-visible .login-card-icon {
            animation: login-fade-in 0.4s ease both;
            animation-delay: calc(var(--card-delay, 0s) + 0.12s);
        }

        .js-anim .login-card.is-visible .login-card-num {
            animation: login-fade-in 0.4s ease both;
            animation-delay: calc(var(--card-delay, 0s) + 0.18s);
        }

        /* Sichqoncha ostidan yumshoq yorug'lik dog'i */
        .login-card::before {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: inherit;
            background: radial-gradient(220px circle at var(--spot-x, 50%) var(--spot-y, 0%), rgba(20, 20, 18, 0.05), transparent 70%);
            opacity: 0;
            transition: opacity 0.4s ease;
            pointer-events: none;
        }
        .login-card:hover::before { opacity: 1; }

        /* 3D hover effekti — kompyuter va planshetlar (>= 768px) uchun */
        @media (min-width: 768px) {
            .login-cards-grid {
                perspective: 1200px;
            }

            .login-card {
                transform-style: preserve-3d;
                transition: border-color 0.3s ease;
                will-change: transform, box-shadow;
            }

            .login-card-inner {
                transform: translateZ(22px);
                transform-style: preserve-3d;
            }

            .login-card-icon {
                transform: translateZ(16px);
            }

            .login-card-cta {
                transform: translateZ(28px);
                transform-style: preserve-3d;
            }

            .login-card-num {
                transform: translateZ(12px);
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .login-card::before { display: none; }
            .login-card { transform: none !important; }
        }
    </style>
@endpush

@push('scripts')
    <script>
        (function () {
            const cards = document.querySelectorAll('.login-card');

            // Kartalar chiqishi uchun IntersectionObserver
            if ('IntersectionObserver' in window) {
                const observer = new IntersectionObserver(function (entries) {
                    entries.forEach(function (entry) {
                        if (!entry.isIntersecting) {
                            return;
                        }

                        entry.target.classList.add('is-visible');
                        observer.unobserve(entry.target);
                    });
                }, { threshold: 0.15, rootMargin: '0px 0px -6% 0px' });

                cards.forEach(function (card) {
                    observer.observe(card);
                });
            } else {
                cards.forEach(function (card) {
                    card.classList.add('is-visible');
                });
            }

            // Silliq 3D LERP hover effekti — kompyuter ekranlari uchun
            const isDesktop = window.innerWidth >= 768;

            if (isDesktop) {
                cards.forEach(function (card) {
                    let rafId = null;
                    let isHovered = false;

                    // Hozirgi qiymatlar (LERP hisoblash uchun)
                    let currentRotX = 0;
                    let currentRotY = 0;
                    let currentLift = 0;
                    let currentSpotX = 50;
                    let currentSpotY = 50;

                    // Maqsadli qiymatlar
                    let targetRotX = 0;
                    let targetRotY = 0;
                    let targetLift = 0;
                    let targetSpotX = 50;
                    let targetSpotY = 50;

                    const maxTilt = 9; // Aniq va chiroyli 3D burchak
                    const maxLift = -7; // 7px yumshoq ko'tarilish

                    // Animatsiya tugagach uni tozalab, transformni to'liq bo'shatamiz
                    card.addEventListener('animationend', function () {
                        card.style.animation = 'none';
                    }, { once: true });

                    const tick = function () {
                        // Silliq LERP koeffitsienti: 0.12 (tabiiy mayin inersiya)
                        const ease = 0.12;
                        currentRotX += (targetRotX - currentRotX) * ease;
                        currentRotY += (targetRotY - currentRotY) * ease;
                        currentLift += (targetLift - currentLift) * ease;
                        currentSpotX += (targetSpotX - currentSpotX) * ease;
                        currentSpotY += (targetSpotY - currentSpotY) * ease;

                        card.style.transform = `perspective(1000px) rotateX(${currentRotX.toFixed(2)}deg) rotateY(${currentRotY.toFixed(2)}deg) translateY(${currentLift.toFixed(2)}px)`;
                        card.style.setProperty('--spot-x', currentSpotX.toFixed(1) + 'px');
                        card.style.setProperty('--spot-y', currentSpotY.toFixed(1) + 'px');

                        const shadowX = (-currentRotY * 1.6).toFixed(1);
                        const shadowY = (-currentRotX * 1.6 + Math.abs(currentLift) * 2).toFixed(1);
                        const shadowBlur = (16 + Math.abs(currentLift) * 2.5).toFixed(1);
                        card.style.boxShadow = `${shadowX}px ${shadowY}px ${shadowBlur}px rgba(20, 20, 18, 0.12)`;

                        const delta = Math.abs(targetRotX - currentRotX) +
                                      Math.abs(targetRotY - currentRotY) +
                                      Math.abs(targetLift - currentLift);

                        // Kursor kartadan chiqqan bo'lsa va to'xtagan bo'lsa, animatsiyani to'xtatamiz
                        if (!isHovered && delta < 0.04) {
                            card.style.transform = '';
                            card.style.boxShadow = '';
                            card.style.borderColor = '';
                            rafId = null;
                            return;
                        }

                        rafId = requestAnimationFrame(tick);
                    };

                    const startLoop = function () {
                        if (!rafId) {
                            rafId = requestAnimationFrame(tick);
                        }
                    };

                    card.addEventListener('mouseenter', function () {
                        card.style.animation = 'none';
                        isHovered = true;
                        card.style.borderColor = 'rgba(20, 20, 18, 0.25)';
                        startLoop();
                    });

                    card.addEventListener('mousemove', function (event) {
                        const rect = card.getBoundingClientRect();
                        const x = event.clientX - rect.left;
                        const y = event.clientY - rect.top;

                        targetSpotX = x;
                        targetSpotY = y;

                        const centerX = rect.width / 2;
                        const centerY = rect.height / 2;

                        const percentX = (x - centerX) / centerX;
                        const percentY = (y - centerY) / centerY;

                        targetRotX = -percentY * maxTilt;
                        targetRotY = percentX * maxTilt;
                        targetLift = maxLift;

                        startLoop();
                    });

                    card.addEventListener('mouseleave', function () {
                        isHovered = false;
                        targetRotX = 0;
                        targetRotY = 0;
                        targetLift = 0;
                        startLoop();
                    });
                });
            } else {
                cards.forEach(function (card) {
                    card.addEventListener('mousemove', function (event) {
                        const rect = card.getBoundingClientRect();
                        card.style.setProperty('--spot-x', (event.clientX - rect.left) + 'px');
                        card.style.setProperty('--spot-y', (event.clientY - rect.top) + 'px');
                    });
                });
            }
        })();
    </script>
@endpush
