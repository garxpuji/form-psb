@props(['action', 'label', 'target' => null])

<form method="POST" action="{{ $action }}" {{ $attributes->class(['inline-flex']) }}
    x-data="{ testing: false }" x-on:submit="testing = true" x-on:pageshow.window="testing = false">
    @csrf
    <button type="submit" x-bind:disabled="testing" x-bind:aria-busy="testing"
        class="inline-flex min-h-[44px] cursor-pointer items-center justify-center rounded-lg border-[1.5px] border-[#4A7396] bg-[#EDF5FC] px-3 py-2 text-xs font-bold text-[#06233B] hover:bg-[#E0F2FE] active:bg-[#CDE5F9] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#005F99] disabled:cursor-wait">
        <span x-text="testing ? 'Menguji…' : @js($label)">{{ $label }}</span>
        @if ($target)
            <span class="sr-only">{{ $target }}</span>
        @endif
    </button>
</form>
