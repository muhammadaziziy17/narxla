@extends('layouts.app')

@section('title', 'Baholashlar tarixi — Narxla')
@section('meta_description', 'Hisobingizga saqlangan qurilma baholashlari.')

@section('content')
    <section class="mx-auto max-w-4xl px-4 py-12 sm:px-6 lg:px-8 lg:py-16">
        <div data-reveal class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="font-display text-3xl font-semibold tracking-tight text-ink-950 sm:text-4xl">
                    Baholashlar tarixi
                </h1>
                <p class="mt-2 text-sm text-ink-500">
                    Saqlangan baholashlar — eng yangisi birinchi.
                </p>
            </div>

            <a href="{{ route('valuation') }}" data-nav-loader
               class="inline-flex items-center justify-center gap-2.5 rounded-2xl bg-ink-950 px-6 py-3.5 text-sm font-semibold text-paper transition duration-500 hover:-translate-y-0.5 hover:bg-ink-900">
                <i class="fa-solid fa-wand-magic-sparkles text-xs"></i>
                <span>Yangi baholash</span>
            </a>
        </div>

        @if ($valuations->isEmpty())
            <div class="mt-10 rounded-4xl border-2 border-dashed border-ink-900/15 p-10 text-center">
                <i class="fa-solid fa-clock-rotate-left text-lg text-ink-300"></i>
                <p class="mt-4 text-sm font-semibold text-ink-950">Hozircha bo'sh</p>
                <p class="mx-auto mt-2 max-w-xs text-xs leading-relaxed text-ink-400">
                    Qurilmangizni baholang — natija avtomatik shu yerda saqlanadi.
                </p>
                <a href="{{ route('valuation') }}" data-nav-loader
                   class="mt-6 inline-flex items-center justify-center gap-2 rounded-2xl border border-ink-900/15 px-5 py-3 text-sm font-semibold text-ink-950 transition duration-500 hover:border-ink-900/40">
                    <span>Narxni aniqlash</span>
                </a>
            </div>
        @else
            <div class="mt-8 space-y-3">
                @foreach ($valuations as $valuation)
                    <article data-reveal
                             class="rounded-4xl border border-ink-900/10 bg-white/80 p-5 transition duration-500 hover:border-ink-900/25 hover:shadow-card sm:p-6">
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div class="min-w-0">
                                <h2 class="font-display text-base font-semibold tracking-tight text-ink-950">
                                    {{ $valuation->brand }} {{ $valuation->model }}
                                </h2>
                                <p class="mt-1.5 text-xs text-ink-400">
                                    {{ $storages[$valuation->storage] ?? $valuation->storage }} ·
                                    {{ $valuation->battery }}% ·
                                    {{ $conditions[$valuation->condition]['label'] ?? $valuation->condition }} ·
                                    {{ $valuation->created_at->format('d.m.Y H:i') }}
                                </p>
                            </div>

                            <div class="text-left sm:text-right">
                                <p class="font-display text-lg font-semibold tracking-tight text-ink-950">
                                    {{ $valuation->priceRangeSom() }}
                                </p>
                                <p class="mt-0.5 text-xs text-ink-400">
                                    {{ $valuation->priceRangeUsd() }} · {{ $valuation->confidence }}% aniqlik
                                </p>
                                <span class="mt-2 inline-flex items-center gap-1.5 rounded-full bg-ink-900/[0.06] px-2.5 py-1 text-[10px] font-medium text-ink-600">
                                    <span class="h-1.5 w-1.5 rounded-full {{ $valuation->price_source === 'market' ? 'bg-ink-950' : 'bg-ink-300' }}"></span>
                                    {{ $valuation->sourceLabel() }}
                                </span>
                            </div>
                        </div>

                        @if ($valuation->recommendation)
                            <div class="mt-4 rounded-2xl border border-ink-900/10 bg-paper-100 p-4 text-xs">
                                <div class="flex items-center gap-2.5">
                                    <span class="grid h-5 w-5 place-items-center rounded-md bg-ink-950 text-[9px] text-paper">
                                        <i class="fa-solid fa-lightbulb"></i>
                                    </span>
                                    <div class="flex flex-wrap items-baseline gap-2">
                                        <span class="text-[10px] uppercase tracking-[0.16em] text-ink-400">Bozor maslahati:</span>
                                        <span class="font-semibold text-ink-950">{{ $valuation->recommendation['badge'] ?? 'Maslahat' }}</span>
                                    </div>
                                </div>
                                @if (! empty($valuation->recommendation['reason']))
                                    <p class="mt-2 text-xs leading-relaxed text-ink-600">{{ $valuation->recommendation['reason'] }}</p>
                                @endif
                            </div>
                        @endif

                        @if ($valuation->insight)
                            <p class="mt-4 border-t border-ink-900/10 pt-4 text-xs leading-relaxed text-ink-500">
                                {{ $valuation->insight }}
                            </p>
                        @endif

                        @if (! empty($valuation->sources))
                            <ul class="mt-4 flex flex-wrap gap-x-4 gap-y-2 border-t border-ink-900/10 pt-4 text-[11px]">
                                @foreach ($valuation->sources as $source)
                                    @php($meta = \App\Models\Valuation::sourceMeta($source['url'] ?? ''))
                                    <li>
                                        <a href="{{ $source['url'] ?? '#' }}" target="_blank" rel="noopener nofollow"
                                           class="inline-flex items-center gap-1.5 text-ink-500 underline decoration-ink-900/20 underline-offset-2 transition-colors duration-500 hover:text-ink-950">
                                            <span class="relative grid h-4 w-4 shrink-0 place-items-center overflow-hidden rounded bg-white text-[6px] font-bold uppercase text-ink-400 ring-1 ring-ink-900/10"
                                                  title="{{ $meta['label'] }}">
                                                <span>{{ $meta['short'] }}</span>
                                                <img src="{{ $meta['favicon'] }}" alt="{{ $meta['label'] }}" loading="lazy"
                                                     class="absolute inset-0 m-auto h-3 w-3"
                                                     onload="this.previousElementSibling.style.display = 'none'"
                                                     onerror="this.remove()">
                                            </span>
                                            {{ \Illuminate\Support\Str::limit($source['title'] ?? ($source['url'] ?? ''), 60) }}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </article>
                @endforeach
            </div>

            <div class="mt-8">
                {{ $valuations->links('partials.pagination') }}
            </div>
        @endif
    </section>
@endsection
