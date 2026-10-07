<div class="sticky top-2.5 sm:top-4 lg:top-5 z-50 w-full px-3 sm:px-6 lg:px-8 pointer-events-none transition-all duration-300">
    <header data-navbar
            class="nav-squash pointer-events-auto relative mx-auto flex h-14 sm:h-16 max-w-5xl items-center justify-between overflow-hidden rounded-full border border-ink-900/10 bg-paper/70 px-4 sm:px-6 shadow-[0_8px_30px_rgba(20,20,18,0.04)] backdrop-blur-xl transition-all duration-300 data-[scrolled]:border-ink-900/15 data-[scrolled]:bg-paper/85 data-[scrolled]:shadow-[0_12px_36px_rgba(20,20,18,0.08)]">

        {{-- Sahifa o'tish (redirect) progress chizig'i --}}
        <div class="page-progress pointer-events-none absolute inset-x-0 top-0 h-[3px] overflow-hidden rounded-t-full bg-ink-900/[0.08]" aria-hidden="true">
            <span class="block h-full w-full origin-left bg-ink-950"></span>
        </div>

        {{-- Sahifani skroll qilish indikatori (pastki nozik chiziq) --}}
        <div class="pointer-events-none absolute inset-x-0 bottom-0 h-[2px] bg-ink-900/[0.04]" aria-hidden="true">
            <span data-progress class="block h-full origin-left scale-x-0 bg-ink-950/20"></span>
        </div>

        {{-- Logo: harflar ketma-ket --}}
        <a href="{{ route('home') }}" class="group inline-flex items-baseline" aria-label="Narxla — bosh sahifa">
            <span class="inline-flex overflow-hidden font-display text-[1.3rem] sm:text-[1.4rem] font-semibold tracking-tight text-ink-950" style="perspective: 500px">
                @foreach (str_split('Narxla') as $index => $letter)
                    <span class="logo-letter inline-block" style="--i: {{ $index }}">{{ $letter }}</span>
                @endforeach
            </span>
        </a>

        <nav class="relative flex items-center gap-1.5 sm:gap-2 lg:gap-3" data-nav-links>
            {{-- Siljitiladigan pill indikator (joylashuv CSS + JS transform orqali) --}}
            <span data-pill
                  class="nav-pill pointer-events-none absolute left-0 h-9 w-0 rounded-full bg-ink-900/[0.07] opacity-0"></span>

            @if (request()->routeIs('home'))
                <a href="#how" data-nav-link
                   class="nav-item relative z-10 hidden items-center justify-center px-3.5 py-2 text-sm font-medium text-ink-500 transition-colors duration-200 hover:text-ink-950 sm:inline-flex"
                   style="--nav-i: 0">
                    Qanday ishlaydi?
                </a>
            @endif

            @auth
                <a href="{{ route('valuations.index') }}" data-nav-link
                   class="nav-item relative z-10 inline-flex items-center justify-center gap-2 px-3 py-1.5 text-xs sm:text-sm font-medium text-ink-500 transition-colors duration-500 hover:text-ink-950 sm:px-4 sm:py-2"
                   style="--nav-i: 1">
                    <i class="fa-solid fa-clock-rotate-left text-xs"></i>
                    <span class="hidden sm:inline">Tarix</span>
                </a>

                <span class="nav-item relative z-10 flex items-center gap-2 rounded-full border border-ink-900/10 bg-white/70 py-1 pl-1 pr-2 sm:gap-2.5 sm:py-1.5 sm:pl-1.5 sm:pr-4"
                      style="--nav-i: 2">
                    @php($avatarUrl = auth()->user()->avatarUrl())

                    @if ($avatarUrl)
                        <img src="{{ $avatarUrl }}" alt="" referrerpolicy="no-referrer" loading="lazy"
                             onerror="this.style.display='none'; this.nextElementSibling.style.display='grid';"
                             class="h-7 w-7 rounded-full border border-ink-900/10 object-cover">

                        {{-- Rasm yuklanmasa — bosh harf ko'rsatiladi --}}
                        <span style="display: none"
                              class="grid h-7 w-7 place-items-center rounded-full bg-ink-950 text-[11px] font-semibold text-paper">
                            {{ mb_substr(auth()->user()->name, 0, 1) }}
                        </span>
                    @else
                        <span class="grid h-7 w-7 place-items-center rounded-full bg-ink-950 text-[11px] font-semibold text-paper">
                            {{ mb_substr(auth()->user()->name, 0, 1) }}
                        </span>
                    @endif

                    <span class="hidden max-w-[9rem] truncate text-xs sm:text-sm font-medium text-ink-950 sm:inline">
                        {{ auth()->user()->name }}
                    </span>
                </span>

                <form method="POST" action="{{ route('logout') }}" class="nav-item relative z-10" style="--nav-i: 3">
                    @csrf
                    <button type="submit" data-nav-loader
                            class="inline-flex items-center gap-1.5 rounded-full border border-ink-900/15 px-3 py-1.5 text-xs sm:text-sm font-medium text-ink-600 transition duration-500 hover:-translate-y-0.5 hover:border-ink-900/35 hover:text-ink-950 active:scale-95 sm:gap-2 sm:px-4 sm:py-2">
                        <i class="fa-solid fa-arrow-right-from-bracket text-xs"></i>
                        <span class="hidden sm:inline">Chiqish</span>
                    </button>
                </form>
            @else
                <a href="{{ route('login') }}" data-nav-loader
                   class="nav-item group relative z-10 inline-flex items-center gap-2 overflow-hidden rounded-full bg-ink-950 px-3.5 py-2 sm:px-5 sm:py-2.5 text-xs sm:text-sm font-medium text-paper transition duration-200 hover:-translate-y-0.5 hover:bg-ink-900 active:scale-95"
                   style="--nav-i: 1">
                    <span class="pointer-events-none absolute inset-0 -translate-x-full bg-gradient-to-r from-transparent via-white/25 to-transparent transition-transform duration-700 ease-out group-hover:translate-x-full"></span>
                    <i class="fa-regular fa-user text-xs transition-transform duration-200 group-hover:-translate-y-0.5"></i>
                    <span class="relative">Tizimga kirish</span>
                </a>
            @endauth
        </nav>
    </header>
</div>