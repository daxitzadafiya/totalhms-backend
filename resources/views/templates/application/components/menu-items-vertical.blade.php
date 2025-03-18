@php
    if(isset($template) && $template === 'horizontal') $groupAddClass = '';
    else $groupAddClass = 'waves-effect waves-dark'
@endphp

@if(count($items) > 0)
    @foreach($items as $key => $item)

        @isset($item->separator)
            @if($item->separator === true)
                <li class="nav-small-cap">{{ $item->title ?? '' }}</li>
                @continue
            @endif
        @endisset

        @isset($item->children)
            @if(count($item->children) > 0)

                <li>
                    <a class="has-arrow {{ $groupAddClass }}" href="#" aria-expanded="false">
                        <i class="{{ $item->class ?? '' }}"></i>

                        <span
                        @if(isset($item->subgroup) && $item->subgroup)
                            class=""
                        @else
                            class="hide-menu"
                        @endif
                        >
                            {{ $item->title ?? '' }}
                            {!! $item->badge ?? '' !!}
                        </span>

                    </a>
                    <ul aria-expanded="false" class="collapse">
                        @include('templates.application.components.menu-items-vertical', [ 'items' => $item->children ])
                    </ul>
                </li>

            @else
                @include('templates.application.components.menu-item-single', [ 'item' => $item ])
            @endif

        @endisset

        @if(!isset($item->children))
            @include('templates.application.components.menu-item-single', [ 'item' => $item ])
        @endif


    @endforeach
@endif
