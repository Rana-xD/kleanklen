@php
    $title = $shortcode->title;
    $subtitle = $shortcode->subtitle;
@endphp

<section class="tp-product-area position-relative pb-30 pt-30">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-xl-6 col-lg-6">
                {!! Theme::partial('section-title', compact('shortcode')) !!}
            </div>
            <div class="col-xl-6 col-lg-6">
                <div class="tp-product-tab-2 tp-product-tab-3 tp-tab mb-50 text-center">
                    <div class="tp-product-tab-inner-3 d-flex align-items-center justify-content-center justify-content-lg-end">
                        <nav>
                            <div
                                class="nav nav-tabs justify-content-center tp-product-tab tp-tab-menu p-relative"
                                id="productTab"
                                role="tablist"
                                data-ajax-url="{{ route('public.ajax.products', ['limit' => $shortcode->limit ?: 8]) }}"
                            >
                                @foreach($productTabs as $key => $tab)
                                    @continue(! in_array($key, $selectedTabs) || (! EcommerceHelper::isReviewEnabled() && $key === 'top-rated'))

                                    <button
                                        @class(['nav-link', 'active' => $loop->first])
                                        id="{{ $key }}-tab"
                                        data-bs-toggle="tab"
                                        data-bs-target="#tab-pane"
                                        type="button"
                                        role="tab"
                                        aria-controls="tab-pane"
                                        @if ($loop->first) aria-selected="true" @endif
                                        data-bb-toggle="product-tab"
                                        data-bb-value="{{ $key }}"
                                    >
                                        {{ $tab }}
                                        <span class="tp-product-tab-tooltip">0</span>
                                    </button>
                                @endforeach
                                <span id="productTabMarker" class="tp-tab-line d-none d-sm-inline-block"></span>
                            </div>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-xl-12">
                <div class="tab-content" id="productTabContent">
                    <div class="tab-pane fade show active" id="tab-pane" role="tabpanel" aria-labelledby="tab" tabindex="0">
                        @foreach($groups as $key => $tab)
                            @continue(! isset($tab['products']))

                            @include(
                                Theme::getThemeNamespace('views.ecommerce.includes.product-items'),
                                ['products' => $tab['products'], 'itemsPerRow' => get_products_per_row(), 'layout' => 'grid']
                            )
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
