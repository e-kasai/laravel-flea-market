@props([
    "items" => [],
])

<nav class="tabs__nav" role="tablist">
    @foreach ($items as $tab)
        <a
            href="{{ $tab["href"] }}"
            class="{{ ! empty($tab["active"]) ? "is-active" : "" }}"
            role="tab"
            aria-selected="{{ ! empty($tab["active"]) ? "true" : "false" }}"
        >
            {{ $tab["label"] }}

            {{-- バッジ（未読数）表示 --}}
            @if (! empty($tab["badge"]) && $tab["badge"] > 0)
                <span class="tabs__badge">{{ $tab["badge"] }}</span>
            @endif
        </a>
    @endforeach
</nav>
