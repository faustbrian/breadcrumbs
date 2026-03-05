@if ($trail->isEmpty())
    <p>No breadcrumbs</p>
@else
    <ol>
        @foreach ($trail as $breadcrumb)
            @if ($breadcrumb->isCurrent())
                <li class="current">{{ $breadcrumb->label() }}</li>
            @elseif ($breadcrumb->url())
                <li><a href="{{ $breadcrumb->url() }}">{{ $breadcrumb->label() }}</a></li>
            @else
                <li>{{ $breadcrumb->label() }}</li>
            @endif
        @endforeach
    </ol>
@endif
