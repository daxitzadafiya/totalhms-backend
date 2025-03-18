<li>
    <a href="{{ $item->url ?? '' }}"
       aria-expanded="false"
       @isset($item->target) target="{{ $item->target ?? '' }}" @endisset
    >
        @isset($item->class) <i class="{{ $item->class ?? '' }}"></i> @endisset

        <span>
            {{ $item->title ?? '' }}
            {!! $item->badge ?? '' !!}
        </span>
    </a>
</li>