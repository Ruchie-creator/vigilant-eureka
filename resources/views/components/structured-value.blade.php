@props(['value' => null, 'empty' => 'Not provided', 'depth' => 0])

@php
    $resolved = $value;

    if ($resolved instanceof \BackedEnum) {
        $resolved = $resolved->value;
    } elseif ($resolved instanceof \JsonSerializable) {
        $resolved = $resolved->jsonSerialize();
    }
@endphp

@if(is_array($resolved))
    @if($resolved === [])
        <span class="text-slate-400">{{ $empty }}</span>
    @elseif(array_is_list($resolved))
        <ul class="space-y-1.5 {{ $depth > 0 ? 'mt-1' : '' }}">
            @foreach($resolved as $item)
                <li class="flex items-start gap-2">
                    <span class="mt-2 size-1.5 shrink-0 rounded-full bg-teal/70"></span>
                    <div class="min-w-0 flex-1 break-words">
                        <x-structured-value :value="$item" :empty="$empty" :depth="$depth + 1" />
                    </div>
                </li>
            @endforeach
        </ul>
    @else
        <dl class="grid gap-2 {{ $depth > 0 ? 'mt-1 border-l border-slate-200 pl-3' : '' }}">
            @foreach($resolved as $key => $item)
                <div class="min-w-0">
                    <dt class="text-xs font-semibold uppercase text-slate-500">{{ str_replace('_', ' ', (string) $key) }}</dt>
                    <dd class="mt-0.5 break-words text-slate-700">
                        <x-structured-value :value="$item" :empty="$empty" :depth="$depth + 1" />
                    </dd>
                </div>
            @endforeach
        </dl>
    @endif
@elseif(is_null($resolved) || $resolved === '')
    <span class="text-slate-400">{{ $empty }}</span>
@elseif(is_bool($resolved))
    {{ $resolved ? 'Yes' : 'No' }}
@elseif(is_scalar($resolved) || $resolved instanceof \Stringable)
    {{ (string) $resolved }}
@else
    <span class="text-slate-400">Structured value unavailable</span>
@endif
