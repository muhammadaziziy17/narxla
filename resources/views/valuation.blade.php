@extends('layouts.app')

@section('title', 'Qurilmani baholash — Narxla')
@section('meta_description', 'Telefon, planshet yoki noutbuk haqida yozing — sun\'iy intellekt model, xotira va holatni aniqlab, bozor narxini hisoblaydi.')

@section('content')
    <section class="relative overflow-hidden" x-data="valuationForm()">
        <div class="mx-auto max-w-4xl px-4 pb-36 pt-10 sm:px-6 lg:px-8 lg:pb-24 lg:pt-16">
            {{-- Sarlavha --}}
            <header class="mx-auto max-w-2xl text-center" data-reveal>
                <h1 class="font-display text-3xl font-semibold tracking-tight text-ink-950 sm:text-4xl md:text-5xl">
                    Qurilmangizni baholang
                </h1>
                <p class="mt-3 text-sm text-ink-500 sm:text-base">
                    Telefon, planshet yoki noutbuk haqida oddiy qilib yozing — narxni darhol hisoblaymiz.
                </p>

                @auth
                    <span class="mt-4 inline-flex items-center gap-2 rounded-full bg-ink-900/5 px-3.5 py-1.5 text-[11px] font-medium text-ink-600">
                        <i class="fa-solid fa-bookmark text-ink-950"></i>
                        Natijalar hisobingizga saqlanadi
                    </span>
                @endauth
            </header>

            {{-- Asosiy maydon (markazda) --}}
            <div class="mx-auto mt-8 max-w-2xl sm:mt-10">
                <form @submit.prevent="submit()">
                    <div class="rounded-4xl border border-ink-900/10 bg-white/80 p-5 sm:p-7 shadow-card">
                        <label for="description" class="mb-2 block text-xs font-medium text-ink-500">
                            Qurilma haqida
                        </label>

                        <textarea id="description" x-model="description" rows="6" maxlength="1000"
                                  @keydown.cmd.enter.prevent="submit()"
                                  @keydown.ctrl.enter.prevent="submit()"
                                  placeholder="Masalan: iPhone 15 Pro Max, 256 GB, batareya 90%, ideal holat"
                                  class="w-full resize-none rounded-2xl border border-ink-900/10 bg-white/70 px-4 py-3.5 text-sm leading-relaxed text-ink-950 outline-none transition placeholder:text-ink-300 focus:border-ink-900/40 focus:bg-white"></textarea>

                        <p class="mt-2.5 text-[11px] leading-relaxed text-ink-400">
                            Qurilma nomi, xotira hajmi, batareya quvvati va holatni yozsangiz — natija aniqroq bo'ladi.
                            Model katalogda bo'lmasa, so'rovingiz mutaxassisga yuboriladi.
                        </p>

                        {{-- Namuna yozuvlar --}}
                        <div class="examples-row mt-4 flex flex-wrap items-center gap-2">
                            <span class="mr-1 text-xs font-medium text-ink-500">Namuna:</span>
                            <template x-for="example in examples" :key="example">
                                <button type="button" @click="description = example"
                                        class="rounded-full border border-ink-900/15 px-3.5 py-1.5 text-xs font-medium text-ink-500 transition duration-500 hover:border-ink-900/40 hover:text-ink-950"
                                        x-text="example"></button>
                            </template>
                        </div>

                        {{-- Asosiy tugma --}}
                        <div class="mt-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <p x-show="!canSubmit" x-cloak class="text-[11px] leading-relaxed text-ink-400">
                                <i class="fa-solid fa-circle-info mr-1"></i>
                                Tugma faollashishi uchun kamida 10 belgi yozing.
                            </p>

                            <button type="button" @click="submit()" :disabled="!canSubmit || loading"
                                    class="inline-flex w-full items-center justify-center gap-2.5 rounded-2xl bg-ink-950 px-6 py-3.5 text-sm font-semibold text-paper transition duration-500 hover:-translate-y-0.5 hover:bg-ink-900 disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:translate-y-0 sm:ml-auto sm:w-auto">
                                <i class="fa-solid" :class="loading ? 'fa-circle-notch fa-spin' : 'fa-wand-magic-sparkles'"></i>
                                <span x-text="loading ? 'Tahlil qilinmoqda…' : 'Narxni aniqlash'"></span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- Modal orqa foni --}}
        <div class="modal-backdrop"
             x-show="modalOpen || modalClosing"
             x-cloak
             :class="{ 'is-open': modalOpen, 'is-closing': modalClosing }"
             @click="!loading && closeModal()"
             aria-hidden="true"></div>

        {{-- Asosiy Modal (Loader, Natija va Xatolik) --}}
        <div class="valuation-modal"
             x-show="modalOpen || modalClosing"
             x-cloak
             :class="{
                 'is-open': modalOpen,
                 'is-closing': modalClosing,
                 'is-compact-view': loading || (!loading && error),
                 'is-result-view': !loading && !error && (result || notice)
             }"
             @keydown.window.escape="!loading && closeModal()"
             role="dialog"
             aria-modal="true">

            <div class="modal-dialog">
                {{-- Umumiy yopish tugmasi (faqat yuklanish bo'lmaganda ko'rinadi) --}}
                <button type="button" x-show="!loading" x-cloak @click="closeModal()" aria-label="Yopish" class="modal-close-button">
                    <i class="fa-solid fa-xmark text-xs"></i>
                </button>

                {{-- Mobil oyna tutqichi (faqat natija chiqqanda ko'rinadi) --}}
                <div class="sheet-handle" x-show="!loading && !error && (result || notice)" x-cloak>
                    <span class="sheet-grabber" aria-hidden="true"></span>
                    <button type="button" @click="closeModal()" aria-label="Yopish" class="sheet-close">
                        <i class="fa-solid fa-xmark text-xs"></i>
                    </button>
                </div>

                {{-- 1. LOADER KO'RINISHI (Ixcham markaziy modal, faqat yuklanish paytida) --}}
                <div x-show="loading" x-cloak class="loader-modal-content">
                    <div class="flex flex-col items-center py-2 text-center">
                        <span class="loader-wordmark shrink-0" aria-label="Narxla">
                            @foreach (str_split('Narxla') as $index => $letter)
                                <span class="loader-letter" style="--i: {{ $index }}">{{ $letter }}</span>
                            @endforeach
                        </span>

                        <h3 class="mt-4 font-display text-base font-semibold tracking-tight text-ink-950">
                            AI tahlil qilmoqda
                        </h3>
                        <p class="mt-1.5 min-h-[2.5rem] px-2 text-xs leading-relaxed text-ink-400" x-text="statusText"></p>

                        <div class="mt-5 h-[2px] w-full overflow-hidden rounded-full bg-ink-900/10">
                            <span class="loader-line block h-full w-2/5 rounded-full bg-ink-950"></span>
                        </div>
                    </div>
                </div>

                {{-- 2. XATOLIK KO'RINISHI (Loader o'rnida ixcham modalda chiqadi va yopish imkonini beradi) --}}
                <div x-show="!loading && error" x-cloak class="error-modal-content">
                    <div class="flex flex-col items-center py-2 text-center">
                        <span class="grid h-12 w-12 place-items-center rounded-2xl bg-ink-900/[0.06] text-ink-950">
                            <i class="fa-solid fa-triangle-exclamation text-base"></i>
                        </span>

                        <h3 class="mt-4 font-display text-base font-semibold tracking-tight text-ink-950">
                            Xatolik yuz berdi
                        </h3>
                        <p class="mt-2 text-xs leading-relaxed text-ink-500" x-text="error"></p>

                        <div class="mt-6 w-full">
                            <button type="button" @click="closeModal()"
                                    class="inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-ink-950 px-5 py-3 text-xs font-semibold text-paper transition duration-300 hover:bg-ink-900">
                                <i class="fa-solid fa-xmark text-[11px]"></i>
                                <span>Yopish</span>
                            </button>
                        </div>
                    </div>
                </div>

                {{-- 3. NATIJA / XABAR KO'RINISHI --}}
                <div x-show="!loading && !error && (result || notice)" x-cloak class="modal-scroll-area p-5 sm:p-7">
                    {{-- Xabar: so'rov qabul qilindi yoki qurilma qo'llab-quvvatlanmaydi --}}
                    <div x-show="notice" x-cloak class="rounded-3xl border border-ink-900/10 bg-white/80 p-6 sm:p-7">
                        <span class="grid h-10 w-10 place-items-center rounded-xl bg-ink-950 text-paper">
                            <i class="fa-solid text-sm" :class="notice?.icon"></i>
                        </span>
                        <p class="mt-5 font-display text-lg font-semibold tracking-tight text-ink-950" x-text="notice?.title"></p>
                        <p class="mt-2 text-sm leading-relaxed text-ink-500" x-text="notice?.message"></p>
                        <button type="button" @click="reset(); closeModal();"
                                class="mt-6 inline-flex items-center justify-center gap-2 rounded-2xl border border-ink-900/15 px-5 py-3 text-sm font-semibold text-ink-950 transition duration-500 hover:border-ink-900/40">
                            <i class="fa-solid fa-rotate-left text-xs"></i>
                            Yana baholash
                        </button>
                    </div>

                    {{-- Natija --}}
                    <div x-show="result" x-cloak class="gpu rounded-3xl border border-ink-900/10 bg-white/80 p-6 sm:p-7">
                        <div class="flex flex-wrap items-center justify-between gap-2 pr-7">
                            <p class="text-[11px] uppercase tracking-[0.18em] text-ink-400">Taxminiy narx</p>

                            <div class="flex items-center gap-2">
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-ink-900/[0.06] px-3 py-1 text-[11px] font-medium text-ink-600">
                                    <span class="h-1.5 w-1.5 rounded-full"
                                          :class="result?.price_source === 'market' ? 'bg-ink-950' : 'bg-ink-300'"></span>
                                    <span x-text="result?.source_label"></span>
                                </span>
                                <span class="rounded-full bg-ink-900/[0.06] px-3 py-1 text-[11px] font-medium text-ink-600">
                                    <span x-text="result?.confidence"></span>% aniqlik
                                </span>
                            </div>
                        </div>

                        <p class="result-range mt-4 font-display text-3xl font-semibold tracking-tight text-ink-950 sm:text-4xl"
                           x-text="result?.range"></p>
                        <p class="mt-1.5 text-sm text-ink-400" x-text="result?.range_usd"></p>
                        <p class="mt-2 text-sm text-ink-500" x-text="result?.device"></p>

                        <div class="mt-7 grid grid-cols-3 overflow-hidden rounded-2xl border border-ink-900/10">
                            <div class="bg-white p-3 text-center">
                                <p class="text-[10px] uppercase tracking-wider text-ink-300">Holat</p>
                                <p class="mt-1 text-xs font-semibold text-ink-950" x-text="result?.condition"></p>
                            </div>
                            <div class="border-x border-ink-900/10 bg-white p-3 text-center">
                                <p class="text-[10px] uppercase tracking-wider text-ink-300">Batareya</p>
                                <p class="mt-1 text-xs font-semibold text-ink-950" x-text="result?.battery"></p>
                            </div>
                            <div class="bg-white p-3 text-center">
                                <p class="text-[10px] uppercase tracking-wider text-ink-300">Xotira</p>
                                <p class="mt-1 text-xs font-semibold text-ink-950" x-text="result?.storage"></p>
                            </div>
                        </div>

                        <p class="mt-7 text-sm leading-relaxed text-ink-600" x-text="result?.insight"></p>

                        {{-- Bozor maslahati (Sotish / Kutish tavsiyasi) --}}
                        <template x-if="result?.recommendation">
                            <div class="mt-6 rounded-2xl border border-ink-900/10 bg-paper-100 p-4 sm:p-5">
                                <div class="flex items-center gap-2.5">
                                    <span class="grid h-6 w-6 place-items-center rounded-lg bg-ink-950 text-[10px] text-paper">
                                        <i class="fa-solid fa-lightbulb"></i>
                                    </span>
                                    <div class="flex flex-wrap items-baseline gap-2">
                                        <span class="text-[10px] uppercase tracking-[0.16em] text-ink-400">Bozor maslahati:</span>
                                        <span class="text-xs font-semibold text-ink-950" x-text="result.recommendation.badge"></span>
                                    </div>
                                </div>
                                <p class="mt-3 text-xs leading-relaxed text-ink-600" x-text="result.recommendation.reason"></p>
                                <div class="mt-3 flex items-start gap-2 border-t border-ink-900/5 pt-2.5">
                                    <i class="fa-solid fa-circle-info mt-0.5 text-[10px] text-ink-400"></i>
                                    <p class="text-[11px] leading-relaxed text-ink-400" x-text="result.recommendation.disclaimer || 'Ixtiyor o\'zingizda, bu sun\'iy intellektning bozor tahlili bo\'yicha maslahati xolos.'"></p>
                                </div>
                            </div>
                        </template>

                        {{-- Topilgan e'lonlar (OLX / BirBir) --}}
                        <template x-if="result?.sources?.length">
                            <div class="mt-6 rounded-2xl border border-ink-900/10 bg-paper-100 p-4">
                                <p class="text-[10px] uppercase tracking-[0.16em] text-ink-400">
                                    Topilgan e'lonlar
                                    <template x-if="result?.checked_at">
                                        <span x-text="'· ' + result.checked_at"></span>
                                    </template>
                                </p>

                                <ul class="mt-3 space-y-2">
                                    <template x-for="source in result.sources" :key="source.url">
                                        <li>
                                            <a :href="source.url" target="_blank" rel="noopener nofollow"
                                               class="flex items-center gap-3 text-xs leading-relaxed text-ink-600 transition-colors duration-500 hover:text-ink-950">
                                                <span class="relative grid h-6 w-6 shrink-0 place-items-center overflow-hidden rounded-lg bg-white text-[8px] font-bold uppercase text-ink-400 ring-1 ring-ink-900/10"
                                                      :title="source.label">
                                                    <span x-text="source.short"></span>
                                                    <img :src="source.favicon" :alt="source.label" loading="lazy"
                                                         class="absolute inset-0 m-auto h-4 w-4"
                                                         onload="this.previousElementSibling.style.display = 'none'"
                                                         onerror="this.remove()">
                                                </span>
                                                <span class="min-w-0 flex-1 truncate" x-text="source.title"></span>
                                                <span class="shrink-0 font-medium" x-text="source.price"></span>
                                            </a>
                                        </li>
                                    </template>
                                </ul>

                                <p class="mt-3 text-[10px] leading-relaxed text-ink-300">
                                    Narx OLX va BirBir'dagi topilgan e'lonlar asosida hisoblandi — yakuniy narx holat va kelishuvga bog'liq.
                                </p>
                            </div>
                        </template>

                        {{-- Baholash sertifikati yuklab olish --}}
                        <div class="mt-6 rounded-2xl border border-ink-900/10 bg-paper-100 p-4 sm:p-5">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2">
                                        <span class="grid h-6 w-6 place-items-center rounded-lg bg-ink-950 text-[10px] text-paper">
                                            <i class="fa-solid fa-award"></i>
                                        </span>
                                        <p class="text-xs font-semibold text-ink-950">Rasmiy baholash sertifikati</p>
                                    </div>
                                    <p class="mt-1 text-[11px] leading-relaxed text-ink-500">
                                        OLX yoki Telegram'da e'lon berish uchun tayyor grafik xulosa (PNG).
                                    </p>
                                </div>
                                <button type="button"
                                        @click="downloadCertificate()"
                                        :disabled="generatingCertificate"
                                        class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-ink-950 px-4 py-2.5 text-xs font-semibold text-paper transition duration-300 hover:bg-ink-800 disabled:opacity-50">
                                    <template x-if="!generatingCertificate">
                                        <span class="inline-flex items-center gap-2">
                                            <i class="fa-solid fa-download text-[11px]"></i>
                                            <span>Sertifikatni yuklab olish</span>
                                        </span>
                                    </template>
                                    <template x-if="generatingCertificate">
                                        <span class="inline-flex items-center gap-2">
                                            <i class="fa-solid fa-circle-notch animate-spin text-[11px]"></i>
                                            <span>Tayyorlanmoqda…</span>
                                        </span>
                                    </template>
                                </button>
                            </div>
                        </div>

                        <div x-show="saved" x-cloak class="mt-6 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-ink-900/10 bg-paper-100 px-4 py-3">
                            <span class="inline-flex items-center gap-2 text-xs font-medium text-ink-600">
                                <i class="fa-solid fa-circle-check text-ink-950"></i>
                                Natija hisobingizga saqlandi
                            </span>
                            <a :href="historyUrl" data-nav-loader
                               class="text-xs font-medium text-ink-950 underline decoration-ink-900/20 underline-offset-2 transition-colors duration-500 hover:text-ink-600">
                                <span>Tarixni ko'rish</span>
                            </a>
                        </div>

                        <p x-show="!saved" x-cloak class="mt-6 rounded-2xl border border-dashed border-ink-900/15 px-4 py-3 text-xs leading-relaxed text-ink-400">
                            Natijani saqlab qo'yish uchun
                            <a href="{{ route('login') }}" class="font-medium text-ink-950 underline decoration-ink-900/20 underline-offset-2 transition-colors duration-500 hover:text-ink-600">tizimga kiring</a>.
                        </p>

                        <p class="mt-7 border-t border-ink-900/10 pt-5 text-[11px] leading-relaxed text-ink-400">
                            Bu taxminiy baho — yakuniy narx qurilma holati, bozor talabi va hududga qarab farq qilishi mumkin.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Mobilda pastki qulay panel --}}
        <div class="valuation-bar fixed inset-x-0 bottom-0 z-40 px-4 pt-3 pb-[max(1rem,env(safe-area-inset-bottom))] sm:hidden">
            <div class="mx-auto flex max-w-6xl items-center gap-3 rounded-2xl border border-ink-900/10 bg-paper/95 p-3 shadow-soft">
                <button type="button" @click="submit()" :disabled="!canSubmit || loading"
                        class="inline-flex flex-1 items-center justify-center gap-2.5 rounded-xl bg-ink-950 px-6 py-3.5 text-sm font-semibold text-paper transition hover:bg-ink-900 disabled:cursor-not-allowed disabled:opacity-40">
                    <i class="fa-solid" :class="loading ? 'fa-circle-notch fa-spin' : 'fa-wand-magic-sparkles'"></i>
                    <span x-text="loading ? 'Tahlil qilinmoqda…' : 'Narxni aniqlash'"></span>
                </button>

                <button type="button" @click="reset()" aria-label="Tozalash" title="Tozalash"
                        class="inline-flex shrink-0 items-center justify-center rounded-xl border border-ink-900/15 px-4 py-3.5 text-sm text-ink-500 transition hover:border-ink-900/40 hover:text-ink-950">
                    <i class="fa-solid fa-rotate-left text-xs"></i>
                </button>
            </div>
        </div>
    </section>
@endsection

@push('styles')
    <style>
        /* --- Yuklanish animatsiyasi ("Narxla" minimal) --- */
        .loader-wordmark {
            font-family: var(--font-display);
            font-size: 1.55rem;
            font-weight: 600;
            line-height: 1;
            letter-spacing: -0.02em;
            color: var(--color-ink-950);
            display: inline-flex;
            user-select: none;
        }

        .loader-letter {
            display: inline-block;
            animation: narxla-glow 1.4s ease-in-out infinite;
            animation-delay: calc(var(--i) * 0.1s);
            will-change: opacity, transform;
        }

        @keyframes narxla-glow {
            0%, 100% {
                opacity: 0.2;
                transform: translateY(0);
            }
            50% {
                opacity: 1;
                transform: translateY(-2px);
            }
        }

        .loader-line {
            animation: loader-sweep 1.8s cubic-bezier(0.4, 0, 0.2, 1) infinite;
        }

        @keyframes loader-sweep {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(300%); }
        }

        /* --- Modal va Bottom Sheet --- */
        body.modal-open {
            overflow: hidden;
        }

        .modal-backdrop {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 60;
            background: rgba(20, 20, 18, 0.45);
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
        }

        .modal-backdrop.is-open {
            display: block;
            animation: modal-fade-in 0.32s ease-out both;
        }

        .modal-backdrop.is-closing {
            animation: modal-fade-out 0.24s ease-in both;
        }

        /* Asosiy modal konteyneri odatiy holatda butunlay yashirin */
        .valuation-modal {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 61;
        }

        /* 1. Ixcham markaziy modal: Loader va Xatolik holatida (Desktop va Mobil) */
        .valuation-modal.is-compact-view.is-open,
        .valuation-modal.is-compact-view.is-closing {
            display: flex;
            position: fixed;
            inset: 0;
            z-index: 61;
            align-items: center;
            justify-content: center;
            padding: 1.25rem;
            pointer-events: none;
        }

        .valuation-modal.is-compact-view .modal-dialog {
            position: relative;
            width: 100%;
            max-width: 24rem;
            border-radius: 1.75rem;
            background: #FFFFFF;
            border: 1px solid rgba(20, 20, 18, 0.08);
            box-shadow: 0 25px 60px -15px rgba(20, 20, 18, 0.35);
            padding: 2rem 1.75rem;
            pointer-events: auto;
            animation: modal-pop-in 0.36s cubic-bezier(0.16, 1, 0.3, 1) both;
        }

        .valuation-modal.is-compact-view.is-closing .modal-dialog {
            animation: modal-pop-out 0.22s cubic-bezier(0.4, 0, 1, 1) both;
        }

        /* 2. Natija chiqqanda desktop ko'rinishi */
        @media (min-width: 1024px) {
            .valuation-modal.is-result-view.is-open,
            .valuation-modal.is-result-view.is-closing {
                display: flex;
                position: fixed;
                inset: 0;
                z-index: 61;
                align-items: center;
                justify-content: center;
                padding: 1.5rem;
                pointer-events: none;
            }

            .valuation-modal.is-result-view .modal-dialog {
                position: relative;
                width: 100%;
                max-width: 38rem;
                max-height: 88vh;
                display: flex;
                flex-direction: column;
                border-radius: 2rem;
                background: var(--color-paper);
                border: 1px solid rgba(20, 20, 18, 0.1);
                box-shadow: 0 25px 70px -15px rgba(20, 20, 18, 0.35);
                overflow: hidden;
                pointer-events: auto;
                animation: modal-pop-in 0.38s cubic-bezier(0.16, 1, 0.3, 1) both;
            }

            .valuation-modal.is-result-view.is-closing .modal-dialog {
                animation: modal-pop-out 0.22s cubic-bezier(0.4, 0, 1, 1) both;
            }

            .modal-scroll-area {
                max-height: 88vh;
                overflow-y: auto;
                overscroll-behavior: contain;
                scrollbar-width: thin;
                scrollbar-color: rgba(20, 20, 18, 0.2) transparent;
            }

            /* Nozik, toza modal scrollbari (burchaklardan chiqib ketmaydi) */
            .modal-scroll-area::-webkit-scrollbar {
                width: 6px;
            }

            .modal-scroll-area::-webkit-scrollbar-track {
                background: transparent;
                margin: 1.5rem 0;
            }

            .modal-scroll-area::-webkit-scrollbar-thumb {
                background: rgba(20, 20, 18, 0.18);
                border-radius: 9999px;
            }

            .modal-scroll-area::-webkit-scrollbar-thumb:hover {
                background: rgba(20, 20, 18, 0.35);
            }

            .sheet-handle {
                display: none !important;
            }
        }

        /* 3. Natija chiqqanda mobil ko'rinishi (pastdan chiqadigan bottom sheet) */
        @media (max-width: 1023px) {
            .valuation-modal.is-result-view.is-open,
            .valuation-modal.is-result-view.is-closing {
                display: flex;
                position: fixed;
                inset: 0;
                z-index: 61;
                align-items: flex-end;
                justify-content: center;
                pointer-events: none;
            }

            .valuation-modal.is-result-view .modal-dialog {
                position: relative;
                width: 100%;
                max-height: 90dvh;
                display: flex;
                flex-direction: column;
                border-radius: 1.75rem 1.75rem 0 0;
                background: var(--color-paper);
                border: 1px solid rgba(20, 20, 18, 0.1);
                border-bottom: 0;
                box-shadow: 0 -20px 50px -15px rgba(20, 20, 18, 0.45);
                overflow: hidden;
                pointer-events: auto;
                animation: sheet-up 0.38s cubic-bezier(0.16, 1, 0.3, 1) both;
            }

            .valuation-modal.is-result-view.is-closing .modal-dialog {
                animation: sheet-down 0.25s cubic-bezier(0.4, 0, 1, 1) both;
            }

            .valuation-modal.is-result-view .modal-close-button {
                display: none;
            }

            .sheet-handle {
                display: block;
                position: relative;
                z-index: 5;
                height: 2.75rem;
                flex-shrink: 0;
                background: var(--color-paper);
            }

            .sheet-grabber {
                position: absolute;
                left: 50%;
                top: 0.7rem;
                width: 2.5rem;
                height: 0.25rem;
                border-radius: 9999px;
                background: rgba(20, 20, 18, 0.16);
                transform: translateX(-50%);
            }

            .sheet-close {
                position: absolute;
                right: 0.5rem;
                top: 0.4rem;
                display: grid;
                place-items: center;
                width: 2rem;
                height: 2rem;
                border-radius: 9999px;
                border: 1px solid rgba(20, 20, 18, 0.1);
                background: rgba(255, 255, 255, 0.85);
                color: var(--color-ink-500);
                transition: color 0.3s ease, border-color 0.3s ease;
            }

            .sheet-close:hover {
                border-color: rgba(20, 20, 18, 0.3);
                color: var(--color-ink-950);
            }

            .modal-scroll-area {
                flex: 1 1 auto;
                overflow-y: auto;
                overscroll-behavior: contain;
                padding-bottom: calc(2rem + env(safe-area-inset-bottom));
                scrollbar-width: none;
                -webkit-overflow-scrolling: touch;
            }

            .modal-scroll-area::-webkit-scrollbar {
                display: none;
            }

            .examples-row {
                flex-wrap: nowrap;
                overflow-x: auto;
                scrollbar-width: none;
                -webkit-overflow-scrolling: touch;
            }

            .examples-row::-webkit-scrollbar {
                display: none;
            }

            .examples-row > * {
                flex: 0 0 auto;
            }
        }

        /* Umumiy yopish tugmasi */
        .modal-close-button {
            position: absolute;
            top: 1.25rem;
            right: 1.25rem;
            z-index: 10;
            display: grid;
            place-items: center;
            width: 2rem;
            height: 2rem;
            border-radius: 9999px;
            border: 1px solid rgba(20, 20, 18, 0.08);
            background: rgba(255, 255, 255, 0.85);
            color: var(--color-ink-500);
            transition: all 0.2s ease;
        }

        .modal-close-button:hover {
            background: #FFFFFF;
            color: var(--color-ink-950);
            border-color: rgba(20, 20, 18, 0.25);
            transform: scale(1.05);
        }

        @keyframes modal-pop-in {
            from {
                opacity: 0;
                transform: scale(0.94) translateY(12px);
            }
            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }

        @keyframes modal-pop-out {
            from {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
            to {
                opacity: 0;
                transform: scale(0.96) translateY(8px);
            }
        }

        @keyframes sheet-up {
            from { transform: translateY(100%); }
            to { transform: none; }
        }

        @keyframes sheet-down {
            from { transform: none; }
            to { transform: translateY(100%); }
        }

        @keyframes modal-fade-in {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes modal-fade-out {
            from { opacity: 1; }
            to { opacity: 0; }
        }

        @media (prefers-reduced-motion: reduce) {
            .loader-letter,
            .loader-line,
            .modal-backdrop,
            .valuation-modal,
            .modal-dialog {
                animation: none;
            }
        }
    </style>
@endpush

@push('scripts')
    <script>
        function valuationForm() {
            return {
                examples: [
                    'iPhone 15 Pro Max, 256 GB, batareya 90%, ideal holat',
                    'MacBook Air M2, 256 GB, ideal holat',
                    'Samsung Galaxy Tab S9, 128 GB',
                    'Xiaomi Redmi Note 13 Pro, 128 GB',
                ],

                description: '',
                loading: false,
                result: null,
                saved: false,
                historyUrl: null,
                notice: null,
                error: '',
                generatingCertificate: false,

                // Modal holati (desktop va mobil)
                modalOpen: false,
                modalClosing: false,

                // Yuklanish paytidagi status matnlari zaxirasi — har safar tasodifiy saralanadi
                statusIndex: 0,
                statusTimer: null,
                statusPhrasesPool: [
                    "OLX platformasidagi so'nggi e'lonlar qidirilmoqda…",
                    "BirBir ilovasidagi mos variantlar tekshirilmoqda…",
                    "Bozordagi taklif narxlari taqqoslanmoqda…",
                    "Sun'iy oshirilgan va shubhali e'lonlar filtrlanmoqda…",
                    "Batareya va korpus holatining narxga ta'siri hisoblanmoqda…",
                    "Toshkent va viloyatlardagi narx tafovuti qiyoslanmoqda…",
                    "So'nggi haftadagi o'xshash bitimlar tahlil qilinmoqda…",
                    "Bozor talabi va modelning likvidligi baholanmoqda…",
                    "Eng ishonchli sotuvchilar takliflari saralanmoqda…",
                    "Haqiqiy sotiladigan narx koridori aniqlanmoqda…",
                    "Valyuta kursi va so'mdagi qiymat muvofiqlashtirilmoqda…",
                    "Xotira hajmi va rang varianti bo'yicha farq tekshirilmoqda…",
                    "Qurilmaning eskirish va amortizatsiya darajasi o'rganilmoqda…",
                    "Faol va aktual e'lonlar bazasi saralanmoqda…",
                    "Bozordagi minimal va maksimal chegaralar o'rganilmoqda…",
                    "Qurilma holatiga mos narx koeffitsiyentlari qo'llanilmoqda…",
                ],
                statusMessages: [
                    "Qurilma modeli va parametrlari aniqlanmoqda…",
                ],

                get canSubmit() {
                    return this.description.trim().length >= 10;
                },

                get summary() {
                    const text = this.description.trim();

                    return text.length >= 10 ? text : 'Qurilma haqida yozing';
                },

                get statusText() {
                    return this.statusMessages[this.statusIndex] || this.statusMessages[0];
                },

                openModal() {
                    this.modalOpen = true;
                    this.modalClosing = false;
                    document.body.classList.add('modal-open');
                },

                closeModal() {
                    if (this.loading || !this.modalOpen || this.modalClosing) {
                        return;
                    }

                    this.modalClosing = true;

                    window.setTimeout(() => {
                        this.modalOpen = false;
                        this.modalClosing = false;
                        this.error = '';
                        document.body.classList.remove('modal-open');
                    }, 260);
                },

                startStatusRotation() {
                    // Har gal so'rov ketganda tasodifiy yangi iboralar ketma-ketligi tuziladi
                    const pool = [...this.statusPhrasesPool];
                    for (let i = pool.length - 1; i > 0; i--) {
                        const j = Math.floor(Math.random() * (i + 1));
                        [pool[i], pool[j]] = [pool[j], pool[i]];
                    }

                    this.statusMessages = [
                        "Qurilma modeli va parametrlari aniqlanmoqda…",
                        ...pool.slice(0, 6),
                        "Yakuniy tahlil va tavsiyalar tayyorlanmoqda…",
                    ];

                    this.statusIndex = 0;
                    window.clearInterval(this.statusTimer);
                    this.statusTimer = window.setInterval(() => {
                        if (this.statusIndex < this.statusMessages.length - 1) {
                            this.statusIndex += 1;
                        }
                    }, 2200);
                },

                stopStatusRotation() {
                    window.clearInterval(this.statusTimer);
                    this.statusTimer = null;
                },

                reset() {
                    this.description = '';
                    this.result = null;
                    this.loading = false;
                    this.saved = false;
                    this.historyUrl = null;
                    this.notice = null;
                    this.error = '';
                    this.generatingCertificate = false;
                    this.statusIndex = 0;
                    this.stopStatusRotation();
                    this.closeModal();
                },

                async submit() {
                    if (!this.canSubmit || this.loading) {
                        return;
                    }

                    this.loading = true;
                    this.result = null;
                    this.saved = false;
                    this.notice = null;
                    this.error = '';
                    this.startStatusRotation();
                    this.openModal();

                    try {
                        const [response] = await Promise.all([
                            fetch('{{ route('valuations.store') }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                },
                                body: JSON.stringify({ description: this.description }),
                            }),
                            new Promise((resolve) => window.setTimeout(resolve, 900)),
                        ]);

                        let payload = null;
                        try {
                            payload = await response.json();
                        } catch (_) {
                            payload = null;
                        }

                        if (!response.ok) {
                            this.error = (payload && payload.message)
                                ? payload.message
                                : 'Serverda xatolik yuz berdi. Iltimos, birozdan so\'ng qaytadan urinib ko\'ring.';

                            return;
                        }

                        if (!payload) {
                            this.error = 'Serverdan kutilmagan javob keldi. Qaytadan urinib ko\'ring.';

                            return;
                        }

                        if (payload.status === 'requested') {
                            this.notice = {
                                icon: 'fa-check',
                                title: "So'rov qabul qilindi",
                                message: payload.message,
                            };
                        } else if (payload.status === 'unsupported') {
                            this.notice = {
                                icon: 'fa-circle-info',
                                title: 'Bu turdagi narsa baholanmaydi',
                                message: payload.message,
                            };
                        } else {
                            this.result = payload.result;
                            this.saved = payload.saved;
                            this.historyUrl = payload.history_url;
                        }
                    } catch (requestError) {
                        this.error = "Serverga ulanib bo'lmadi. Internetni tekshirib, qaytadan urinib ko'ring.";
                    } finally {
                        this.loading = false;
                        this.stopStatusRotation();
                    }
                },

                downloadCertificate() {
                    if (this.generatingCertificate || !this.result) {
                        return;
                    }

                    this.generatingCertificate = true;

                    try {
                        const canvas = document.createElement('canvas');
                        const scale = 2;
                        const width = 1200;
                        const height = 675;

                        canvas.width = width * scale;
                        canvas.height = height * scale;

                        const ctx = canvas.getContext('2d');
                        if (!ctx) {
                            this.generatingCertificate = false;
                            return;
                        }

                        ctx.scale(scale, scale);

                        // Fon (paper-100)
                        ctx.fillStyle = '#F7F6F3';
                        ctx.fillRect(0, 0, width, height);

                        // Asosiy oq kartochka
                        const cardX = 40;
                        const cardY = 40;
                        const cardW = width - 80;
                        const cardH = height - 80;

                        this.drawRoundedRect(ctx, cardX, cardY, cardW, cardH, 24);
                        ctx.fillStyle = '#FFFFFF';
                        ctx.fill();
                        ctx.lineWidth = 1;
                        ctx.strokeStyle = '#EBE9E2';
                        ctx.stroke();

                        // Yuqori header: Logotip
                        this.drawRoundedRect(ctx, 80, 75, 46, 46, 12);
                        ctx.fillStyle = '#141412';
                        ctx.fill();

                        ctx.fillStyle = '#FFFFFF';
                        ctx.font = 'bold 22px Poppins, Inter, sans-serif';
                        ctx.textAlign = 'center';
                        ctx.textBaseline = 'middle';
                        ctx.fillText('N', 80 + 23, 75 + 23);

                        ctx.textAlign = 'left';
                        ctx.textBaseline = 'alphabetic';
                        ctx.font = 'bold 24px Poppins, Inter, sans-serif';
                        ctx.fillStyle = '#141412';
                        ctx.fillText('NARXLA', 140, 102);

                        ctx.font = '500 13px Inter, sans-serif';
                        ctx.fillStyle = '#71716A';
                        ctx.fillText('AI Bozor Baholash Tizimi · narxla.uz', 260, 102);

                        // O'ng tomonda: Sana va Sertifikat ID
                        const dateStr = this.result.checked_at || new Date().toLocaleDateString('uz-UZ', { day: '2-digit', month: '2-digit', year: 'numeric' });
                        const certId = 'NX-' + Math.floor(100000 + Math.random() * 900000);
                        const metaText = `Sana: ${dateStr}   ·   Sertifikat: #${certId}`;

                        ctx.font = '600 12px Inter, sans-serif';
                        const metaWidth = ctx.measureText(metaText).width;
                        const pillW = metaWidth + 28;
                        const pillX = cardX + cardW - 40 - pillW;

                        this.drawRoundedRect(ctx, pillX, 80, pillW, 36, 18);
                        ctx.fillStyle = '#F7F6F3';
                        ctx.fill();
                        ctx.lineWidth = 1;
                        ctx.strokeStyle = '#EBE9E2';
                        ctx.stroke();

                        ctx.fillStyle = '#474743';
                        ctx.textAlign = 'left';
                        ctx.textBaseline = 'middle';
                        ctx.fillText(metaText, pillX + 14, 80 + 18);

                        // Ajratuvchi chiziq
                        ctx.beginPath();
                        ctx.moveTo(80, 145);
                        ctx.lineTo(cardX + cardW - 40, 145);
                        ctx.strokeStyle = '#EBE9E2';
                        ctx.lineWidth = 1;
                        ctx.stroke();

                        // 1-bo'lim: Qurilma nomi va parametrlari
                        ctx.textBaseline = 'alphabetic';
                        ctx.font = '600 11px Inter, sans-serif';
                        ctx.fillStyle = '#8F8F86';
                        ctx.fillText('BAHOLANGAN QURILMA', 80, 175);

                        ctx.font = 'bold 28px Poppins, Inter, sans-serif';
                        ctx.fillStyle = '#141412';
                        const deviceTitle = this.result.device || 'Qurilma';
                        ctx.fillText(this.truncateText(ctx, deviceTitle, 960), 80, 212);

                        // Parametr tabletkalari (Holat, Batareya, Xotira, Manba)
                        const badges = [
                            { label: 'Holat: ' + (this.result.condition || 'Yaxshi'), color: '#141412', bg: '#F7F6F3' },
                            { label: 'Batareya: ' + (this.result.battery || '—'), color: '#141412', bg: '#F7F6F3' },
                            { label: 'Xotira: ' + (this.result.storage || '—'), color: '#141412', bg: '#F7F6F3' },
                            { label: (this.result.source_label || 'Bozor tahlili') + ' (' + (this.result.confidence || 75) + '% aniqlik)', color: '#047857', bg: '#ECFDF5' },
                        ];

                        let bX = 80;
                        const bY = 230;
                        ctx.font = '600 12px Inter, sans-serif';
                        for (const b of badges) {
                            const bw = ctx.measureText(b.label).width + 24;
                            this.drawRoundedRect(ctx, bX, bY, bw, 30, 8);
                            ctx.fillStyle = b.bg;
                            ctx.fill();
                            ctx.lineWidth = 1;
                            ctx.strokeStyle = '#EBE9E2';
                            ctx.stroke();

                            ctx.fillStyle = b.color;
                            ctx.textAlign = 'center';
                            ctx.textBaseline = 'middle';
                            ctx.fillText(b.label, bX + bw / 2, bY + 15);
                            bX += bw + 10;
                        }

                        // 2-bo'lim: To'q qora Narx Koridori qutisi
                        const priceBoxX = 80;
                        const priceBoxY = 280;
                        const priceBoxW = cardX + cardW - 40 - priceBoxX;
                        const priceBoxH = 125;

                        this.drawRoundedRect(ctx, priceBoxX, priceBoxY, priceBoxW, priceBoxH, 18);
                        ctx.fillStyle = '#141412';
                        ctx.fill();

                        ctx.textAlign = 'left';
                        ctx.textBaseline = 'alphabetic';
                        ctx.font = '600 11px Inter, sans-serif';
                        ctx.fillStyle = '#B4B4AC';
                        ctx.fillText("TAHLILIY BOZOR NARXI ORALIG'I", priceBoxX + 32, priceBoxY + 40);

                        ctx.font = 'bold 36px Poppins, Inter, sans-serif';
                        ctx.fillStyle = '#FFFFFF';
                        ctx.fillText(this.result.range || '', priceBoxX + 32, priceBoxY + 86);

                        // O'ng tarafda Dollar narxi
                        ctx.textAlign = 'right';
                        ctx.font = '600 11px Inter, sans-serif';
                        ctx.fillStyle = '#B4B4AC';
                        ctx.fillText('AQSH DOLLARI HISOBIDA', priceBoxX + priceBoxW - 32, priceBoxY + 40);

                        ctx.font = '600 24px Poppins, Inter, sans-serif';
                        ctx.fillStyle = '#E9E9E5';
                        ctx.fillText(this.result.range_usd || '', priceBoxX + priceBoxW - 32, priceBoxY + 84);

                        // 3-bo'lim: AI Bozor maslahati
                        const rec = this.result.recommendation || {
                            action: 'fair_price',
                            badge: 'Bozor narxida sotish mumkin',
                            reason: "Qurilma bozorida talab barqaror. O'rtacha bozor narxi atrofida sotish maqsadga muvofiq.",
                            disclaimer: "Ixtiyor o'zingizda, bu sun'iy intellektning bozor tahlili bo'yicha maslahati xolos.",
                        };

                        const recBoxX = 80;
                        const recBoxY = 425;
                        const recBoxW = priceBoxW;
                        const recBoxH = 95;

                        this.drawRoundedRect(ctx, recBoxX, recBoxY, recBoxW, recBoxH, 14);
                        ctx.fillStyle = '#FCFBF9';
                        ctx.fill();
                        ctx.lineWidth = 1;
                        ctx.strokeStyle = '#EBE9E2';
                        ctx.stroke();

                        ctx.textAlign = 'left';
                        ctx.textBaseline = 'alphabetic';
                        ctx.font = '600 11px Inter, sans-serif';
                        ctx.fillStyle = '#8F8F86';
                        ctx.fillText('AI BOZOR MASLAHATI:', recBoxX + 24, recBoxY + 34);

                        ctx.font = 'bold 12px Inter, sans-serif';
                        ctx.fillStyle = '#141412';
                        ctx.fillText(rec.badge || 'Bozor tavsiyasi', recBoxX + 165, recBoxY + 34);

                        ctx.font = '400 13px Inter, sans-serif';
                        ctx.fillStyle = '#474743';
                        const reasonText = this.truncateText(ctx, rec.reason || '', recBoxW - 48);
                        ctx.fillText(reasonText, recBoxX + 24, recBoxY + 64);

                        // 4-bo'lim: Pastki ogohlantirish (Majburiy disclaimer)
                        ctx.beginPath();
                        ctx.moveTo(80, 545);
                        ctx.lineTo(cardX + cardW - 40, 545);
                        ctx.strokeStyle = '#EBE9E2';
                        ctx.lineWidth = 1;
                        ctx.stroke();

                        ctx.textAlign = 'left';
                        ctx.textBaseline = 'alphabetic';
                        ctx.font = 'italic 12px Inter, sans-serif';
                        ctx.fillStyle = '#71716A';
                        const disclaimerText = rec.disclaimer || "Ixtiyor o'zingizda, bu sun'iy intellektning bozor tahlili bo'yicha maslahati xolos.";
                        ctx.fillText(disclaimerText, 80, 578);

                        ctx.textAlign = 'right';
                        ctx.font = '600 12px Inter, sans-serif';
                        ctx.fillStyle = '#8F8F86';
                        ctx.fillText('Tasdiqlangan sertifikat · narxla.uz', cardX + cardW - 40, 578);

                        // PNG sifatida yuklab olish
                        canvas.toBlob((blob) => {
                            if (!blob) {
                                this.generatingCertificate = false;
                                return;
                            }
                            const url = URL.createObjectURL(blob);
                            const a = document.createElement('a');
                            const safeName = (this.result?.device || 'qurilma')
                                .toLowerCase()
                                .replace(/[^a-z0-9]+/g, '-')
                                .replace(/(^-|-$)/g, '');
                            a.href = url;
                            a.download = `narxla-${safeName}-sertifikat.png`;
                            document.body.appendChild(a);
                            a.click();
                            document.body.removeChild(a);
                            URL.revokeObjectURL(url);
                            this.generatingCertificate = false;
                        }, 'image/png');
                    } catch (e) {
                        this.generatingCertificate = false;
                    }
                },

                drawRoundedRect(ctx, x, y, width, height, radius) {
                    ctx.beginPath();
                    if (typeof ctx.roundRect === 'function') {
                        ctx.roundRect(x, y, width, height, radius);
                    } else {
                        ctx.moveTo(x + radius, y);
                        ctx.lineTo(x + width - radius, y);
                        ctx.quadraticCurveTo(x + width, y, x + width, y + radius);
                        ctx.lineTo(x + width, y + height - radius);
                        ctx.quadraticCurveTo(x + width, y + height, x + width - radius, y + height);
                        ctx.lineTo(x + radius, y + height);
                        ctx.quadraticCurveTo(x, y + height, x, y + height - radius);
                        ctx.lineTo(x, y + radius);
                        ctx.quadraticCurveTo(x, y, x + radius, y);
                        ctx.closePath();
                    }
                },

                truncateText(ctx, text, maxWidth) {
                    if (!text) return '';
                    if (ctx.measureText(text).width <= maxWidth) return text;
                    let truncated = text;
                    while (truncated.length > 0 && ctx.measureText(truncated + '…').width > maxWidth) {
                        truncated = truncated.slice(0, -1);
                    }
                    return truncated + '…';
                },
            };
        }
    </script>
@endpush
