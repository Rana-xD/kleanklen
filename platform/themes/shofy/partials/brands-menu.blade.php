@php
    use Botble\Ecommerce\Models\Brand;
    
    $brands = Brand::query()
        ->where('status', 'published')
        ->orderBy('order')
        ->orderBy('name')
        ->get();
@endphp

<ul>
    <li class="has-dropdown">
        <a href="#" title="Brand">
            Brands
            <svg class="icon svg-icon-ti-ti-chevron-down" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                <path d="M6 9l6 6l6 -6"></path>
            </svg>
        </a>
        
        @if($brands->count() > 0)
            <ul class="tp-submenu">
                @foreach($brands as $brand)
                    <li class="">
                        <a href="{{ $brand->url }}" title="{{ $brand->name }}">
                            {{ $brand->name }}
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </li>
</ul>
