@extends('layouts.app')

@section('content')
    {{-- HERO — editorial tipografiya --}}
    <section data-hero class="relative flex min-h-[86vh] flex-col overflow-hidden pt-20 sm:pt-24 lg:pt-28">
        <div class="relative mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8">
            <h1 data-anim-title
                class="max-w-5xl font-display text-[2.5rem] font-semibold leading-[1.06] tracking-tight text-ink-950 sm:text-5xl lg:text-7xl">
                <span class="block overflow-hidden pb-1">
                    <span class="hero-line block">Qurilmangizni sotishdan oldin</span>
                </span>
                <span class="block overflow-hidden pb-1">
                    <span class="hero-line block">
                        uning
                        <span class="relative inline-block">
                            <span class="ai-word">bozor narxini</span>
                            <svg class="absolute -bottom-1.5 left-0 h-3 w-full sm:-bottom-2 sm:h-3.5" viewBox="0 0 220 14" fill="none" preserveAspectRatio="none" aria-hidden="true">
                                <path data-anim-underline d="M3 10.5C52 3.5 140 2.5 217 7.5" stroke="currentColor" stroke-width="4.5" stroke-linecap="round" pathLength="1"/>
                            </svg>
                        </span>
                    </span>
                </span>
                <span class="block overflow-hidden pb-1">
                    <span class="hero-line block">sun'iy intellekt yordamida aniqlab oling!</span>
                </span>
            </h1>

            <div class="mt-10 grid gap-8 sm:mt-12 lg:grid-cols-12 lg:items-end">
                <p data-anim-text class="max-w-xl text-base leading-relaxed text-ink-500 lg:col-span-6 sm:text-lg">
                    Narxla — sun'iy intellektga asoslangan baholash tizimi. Telefon, planshet va noutbukning
                    modeli, xotirasi va holatini tahlil qilib, xarid qilish yoki sotishdan oldin to'g'ri
                    narxni ko'rsatadi.
                </p>

                <div data-anim-cta class="flex flex-col gap-3 sm:flex-row sm:items-center lg:col-span-6 lg:justify-end">
                    <a href="{{ route('valuation') }}" data-nav-loader
                       class="group inline-flex items-center justify-center gap-2.5 rounded-2xl bg-ink-950 px-7 py-4 text-sm font-semibold text-paper transition duration-200 hover:-translate-y-0.5 hover:bg-ink-900">
                        <span>Narxni aniqlash</span>
                        <i class="fa-solid fa-arrow-right text-xs transition-transform duration-200 group-hover:translate-x-1"></i>
                    </a>
                    <a href="#how"
                       class="inline-flex items-center justify-center gap-2 rounded-2xl px-5 py-4 text-sm font-semibold text-ink-600 transition duration-200 hover:bg-ink-900/5 hover:text-ink-950">
                        Qanday ishlaydi?
                        <i class="fa-solid fa-arrow-down text-xs"></i>
                    </a>
                </div>
            </div>

            <div data-anim-text style="animation-delay: 0.9s"
                 class="mt-12 flex flex-wrap items-center gap-x-10 gap-y-3 border-t border-ink-900/10 pt-6 text-xs tracking-wide text-ink-400 sm:mt-16">
                <span>Bepul</span>
                <span>Google yoki Telegram bilan kirish</span>
                <span>Natija ~10 soniyada</span>
            </div>
        </div>

        {{-- Kesilib turadigan ulkan wordmark --}}
        <div class="relative mt-10 overflow-hidden sm:mt-12" aria-hidden="true">
            <span data-parallax
                  class="block translate-y-[16%] whitespace-nowrap text-center font-display text-[23vw] font-bold leading-[0.78] tracking-tighter text-ink-900/[0.06]">
                narxla narxla
            </span>
        </div>
    </section>

    {{-- Brendlar lentasi (logotiplar bilan) --}}
    <div class="relative border-y border-ink-900/10 bg-white/70 py-5">
        <div class="marquee-mask overflow-hidden">
            <div class="marquee-track flex w-max items-center gap-14 pr-14">
                @foreach ([1, 2] as $copy)
                    <div class="flex items-center gap-14" @if ($copy === 2) aria-hidden="true" @endif>
                        @foreach ([
                            ['Apple', 'apple'],
                            ['Samsung', 'samsung'],
                            ['Xiaomi', 'xiaomi'],
                            ['Google', 'google'],
                            ['OnePlus', 'oneplus'],
                            ['Huawei', 'huawei'],
                            ['Oppo', 'oppo'],
                            ['Vivo', 'vivo'],
                            ['Honor', 'honor'],
                        ] as [$name, $slug])
                            <span class="flex items-center gap-2.5 whitespace-nowrap text-sm font-medium tracking-wide text-ink-500">
                                <img src="https://cdn.simpleicons.org/{{ $slug }}/1A1A19" alt="{{ $name }} logotipi" loading="lazy" class="h-4 w-4" />
                                {{ $name }}
                            </span>
                        @endforeach
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Qanday ishlaydi? --}}
    <section id="how" class="scroll-mt-24 bg-white">
        <div class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8 lg:py-28">
            <div class="mx-auto max-w-2xl text-center">
                <span data-reveal class="inline-flex items-center gap-2 rounded-full bg-ink-900/5 px-4 py-1.5 text-xs font-medium text-ink-600">
                    <i class="fa-solid fa-route text-ink-950"></i>
                    Qanday ishlaydi?
                </span>

                <h2 data-mask class="mt-5 font-display text-3xl font-semibold tracking-tight text-ink-950 sm:text-4xl">
                    <span class="block overflow-hidden pb-1">
                        <span class="mask-line block">Uch qadamda aniq natija</span>
                    </span>
                </h2>

                <p data-reveal data-reveal-delay="0.08" class="mt-4 text-sm leading-relaxed text-ink-500 sm:text-base">
                    Murakkab jarayonlar yo'q — qurilma haqida oddiy matn yozish kifoya.
                </p>
            </div>

            <div class="mt-14 grid gap-6 md:grid-cols-3">
                <div data-reveal class="group rounded-4xl border border-ink-900/10 bg-paper-50 p-7 transition duration-300 hover:-translate-y-1 hover:bg-white sm:p-8">
                    <span class="grid h-11 w-11 place-items-center rounded-xl bg-ink-950 text-paper">
                        <i class="fa-solid fa-keyboard"></i>
                    </span>
                    <h3 class="mt-5 font-display text-lg font-semibold text-ink-950">Qurilmani tasvirlab bering</h3>
                    <p class="mt-2 text-sm leading-relaxed text-ink-500">
                        Model, xotira hajmi, batareya va holatni oddiy matnda yozing.
                    </p>
                </div>

                <div data-reveal data-reveal-delay="0.12" class="group rounded-4xl border border-ink-900/10 bg-paper-50 p-7 transition duration-300 hover:-translate-y-1 hover:bg-white sm:p-8">
                    <span class="grid h-11 w-11 place-items-center rounded-xl bg-ink-950 text-paper">
                        <i class="fa-solid fa-magnifying-glass-chart"></i>
                    </span>
                    <h3 class="mt-5 font-display text-lg font-semibold text-ink-950">AI bozorni tekshiradi</h3>
                    <p class="mt-2 text-sm leading-relaxed text-ink-500">
                        OLX va BirBir'dagi o'xshash e'lonlar topilib, narxlari solishtiriladi.
                    </p>
                </div>

                <div data-reveal data-reveal-delay="0.24" class="group rounded-4xl border border-ink-900/10 bg-paper-50 p-7 transition duration-300 hover:-translate-y-1 hover:bg-white sm:p-8">
                    <span class="grid h-11 w-11 place-items-center rounded-xl bg-ink-950 text-paper">
                        <i class="fa-solid fa-wand-magic-sparkles"></i>
                    </span>
                    <h3 class="mt-5 font-display text-lg font-semibold text-ink-950">Narxni bilib oling</h3>
                    <p class="mt-2 text-sm leading-relaxed text-ink-500">
                        Bozor narxi oralig'i va AI xulosasini bir necha soniyada qo'lga kiriting.
                    </p>
                </div>
            </div>
        </div>
    </section>

    {{-- Statistika --}}
    @php
        $statsData = $stats ?? [
            'devices_count' => 0,
            'analysis_seconds' => 10,
            'accuracy_percent' => 98,
        ];
    @endphp
    <section class="border-y border-ink-900/10 bg-white">
        <div class="mx-auto grid max-w-7xl grid-cols-1 divide-y divide-ink-900/10 px-4 sm:grid-cols-3 sm:divide-x sm:divide-y-0 sm:px-6 lg:px-8">
            <div data-reveal class="px-4 py-12 text-center sm:py-14">
                <p class="font-display text-4xl font-semibold tracking-tight text-ink-950 sm:text-5xl">
                    <span data-count="{{ $statsData['devices_count'] }}">{{ number_format($statsData['devices_count'], 0, ',', ' ') }}</span>@if ($statsData['devices_count'] >= 100)<span class="text-ink-300">+</span>@endif
                </p>
                <p class="mt-3 text-sm text-ink-400">Baholangan qurilma</p>
            </div>
            <div data-reveal data-reveal-delay="0.1" class="px-4 py-12 text-center sm:py-14">
                <p class="font-display text-4xl font-semibold tracking-tight text-ink-950 sm:text-5xl">
                    <span data-count="{{ $statsData['analysis_seconds'] }}">{{ $statsData['analysis_seconds'] }}</span><span class="text-ink-300"> son.</span>
                </p>
                <p class="mt-3 text-sm text-ink-400">O'rtacha tahlil vaqti</p>
            </div>
            <div data-reveal data-reveal-delay="0.2" class="px-4 py-12 text-center sm:py-14">
                <p class="font-display text-4xl font-semibold tracking-tight text-ink-950 sm:text-5xl">
                    <span data-count="{{ $statsData['accuracy_percent'] }}">{{ $statsData['accuracy_percent'] }}</span><span class="text-ink-300">%</span>
                </p>
                <p class="mt-3 text-sm text-ink-400">AI aniqlik darajasi</p>
            </div>
        </div>
    </section>

    {{-- CTA --}}
    <section class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8 lg:py-28">
        <div data-reveal class="relative overflow-hidden rounded-4xl bg-ink-950 px-6 py-14 text-center sm:px-12 lg:rounded-5xl lg:py-20">
            <h2 class="relative font-display text-3xl font-semibold tracking-tight text-paper sm:text-4xl">
                Qurilmangiz qancha turadi?
            </h2>
            <p class="relative mx-auto mt-4 max-w-xl text-sm leading-relaxed text-paper/60 sm:text-base">
                Bepul baholang — bir daqiqada bozor narxini va AI xulosasini oling.
            </p>

            <div class="relative mt-9 flex flex-col items-center justify-center gap-3 sm:flex-row">
                <a href="{{ route('valuation') }}" data-nav-loader
                   class="group inline-flex w-full items-center justify-center gap-2.5 rounded-2xl bg-paper px-7 py-4 text-sm font-semibold text-ink-950 transition duration-200 hover:-translate-y-0.5 hover:bg-white sm:w-auto">
                    <span>Narxni aniqlash</span>
                    <i class="fa-solid fa-arrow-right text-xs transition-transform duration-200 group-hover:translate-x-1"></i>
                </a>
                <a href="{{ route('login') }}" data-nav-loader
                   class="inline-flex w-full items-center justify-center gap-2.5 rounded-2xl border border-white/15 px-7 py-4 text-sm font-semibold text-paper transition duration-200 hover:border-white/30 hover:bg-white/5 sm:w-auto">
                    <i class="fa-regular fa-user text-xs"></i>
                    <span>Tizimga kirish</span>
                </a>
            </div>
        </div>
    </section>
@endsection
