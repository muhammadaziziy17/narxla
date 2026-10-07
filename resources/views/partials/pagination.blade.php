@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Sahifalar" class="flex flex-col items-center justify-between gap-4 sm:flex-row">
        {{-- Natijalar soni --}}
        <div class="text-xs text-ink-500">
            Jami <span class="font-medium text-ink-950">{{ $paginator->total() }}</span> tadan
            <span class="font-medium text-ink-950">{{ $paginator->firstItem() }}</span>–<span class="font-medium text-ink-950">{{ $paginator->lastItem() }}</span>
            ko'rsatilmoqda
        </div>

        {{-- Sahifa raqamlari va tugmalar --}}
        <div class="inline-flex items-center gap-1.5 rounded-full border border-ink-900/10 bg-white/70 p-1 shadow-sm backdrop-blur">
            {{-- Oldingi sahifa --}}
            @if ($paginator->onFirstPage())
                <span class="grid h-8 w-8 place-items-center rounded-full text-ink-300 cursor-not-allowed" aria-disabled="true" aria-label="Oldingi sahifa">
                    <i class="fa-solid fa-chevron-left text-[11px]"></i>
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev"
                   class="grid h-8 w-8 place-items-center rounded-full text-ink-600 transition-colors duration-200 hover:bg-paper-100 hover:text-ink-950"
                   aria-label="Oldingi sahifa">
                    <i class="fa-solid fa-chevron-left text-[11px]"></i>
                </a>
            @endif

            {{-- Sahifalar --}}
            @foreach ($elements as $element)
                {{-- Uch nuqta ajratuvchi --}}
                @if (is_string($element))
                    <span class="grid h-8 w-8 place-items-center text-xs text-ink-400" aria-disabled="true">
                        {{ $element }}
                    </span>
                @endif

                {{-- Sahifa raqamlari massivi --}}
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span aria-current="page"
                                  class="grid h-8 min-w-[2rem] place-items-center rounded-full bg-ink-950 px-2 text-xs font-semibold text-paper shadow-sm">
                                {{ $page }}
                            </span>
                        @else
                            <a href="{{ $url }}"
                               class="grid h-8 min-w-[2rem] place-items-center rounded-full px-2 text-xs font-medium text-ink-600 transition-colors duration-200 hover:bg-paper-100 hover:text-ink-950">
                                {{ $page }}
                            </a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Keyingi sahifa --}}
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next"
                   class="grid h-8 w-8 place-items-center rounded-full text-ink-600 transition-colors duration-200 hover:bg-paper-100 hover:text-ink-950"
                   aria-label="Keyingi sahifa">
                    <i class="fa-solid fa-chevron-right text-[11px]"></i>
                </a>
            @else
                <span class="grid h-8 w-8 place-items-center rounded-full text-ink-300 cursor-not-allowed" aria-disabled="true" aria-label="Keyingi sahifa">
                    <i class="fa-solid fa-chevron-right text-[11px]"></i>
                </span>
            @endif
        </div>
    </nav>
@endif
