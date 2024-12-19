@php
    $logo = \App\Models\Utility::get_file('uploads/is_cover_image/');
@endphp
{{-- <div class="modal-body p-0"> --}}
    <input type="hidden" id="product_id" value="{{ $products->id }}">
    <input type="hidden" id="variant_id" value="">
    <input type="hidden" id="variant_qty" value="">
    <div class="cart-variant-body">
        <div class="row">
            <div class="col-lg-4 col-md-5 col-12">
                <div class="cart-variant-img">
                    <div class="variant-main-media">
                        <img src="{{ $logo . (isset($products->is_cover) && !empty($products->is_cover) ? $products->is_cover : 'default_img.png') }}"
                            class="default-img" target="_blank" alt="logitech Keys">{{-- style=" height: 6rem; width: 100%;"--}}
                    </div>
                </div>
            </div>
            <div class="col-lg-8 col-md-7 col-12">
                <div class="cart-variant-detail">
                    <span
                        class="ctg-badge">{{ isset($products->categories) && !empty($products->categories) ? $products->categories->name : '' }}</span>
                    <h3>{{ $products->name }}</h3>
                    <p class="pt-2">{{__('VARIATION:')}}</p>
                    <div class="pv-selection">
                        @foreach ($product_variant_names as $key => $variant)
                            <label for="" class="pt-2">{{ ucfirst($variant->variant_name) }}</label>
                            <select name="product[{{ $key }}]" id="pro_variants_name"
                                class="form-control custom-select variant-selection pro_variants_name{{ $key }}">
                                <option value="0">{{ __('Select Option') }}</option>
                                @foreach ($variant->variant_options as $key => $values)
                                    <option value="{{ $values }}">
                                        {{ $values }}
                                    </option>
                                @endforeach
                            </select>
                        @endforeach
                    </div>
                    <div class="cart-variable row pt-3">
                        <div class="col-md-6">
                            <div class="variant_qty" style=" font-size: large; ">
                                {{__('QTY')}}
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="variation_price1 text-end" style=" font-size: large; ">
                                @if ($products->enable_product_variant == 'on')
                                    <p style=" font-size: large; ">{{__('Please Select Variants')}}</p>
                                @else
                                    <p>{{ \App\Models\Utility::priceFormat($products->price) }}</p>

                                @endif

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
{{-- </div> --}}
<div class="col-12 d-flex justify-content-end col-form-label">
    <input type="button" value="{{ __('Cancel') }}" class="btn btn-secondary btn-light" data-bs-dismiss="modal">
    <a href="#!" type="submit" class="btn btn-primary add_to_cart_variant toacartvariant ms-2" data-toggle="tooltip" data-id="{{ $products->id }}" >{{--data-url="{{ url('addToCartVariant/' . $products->id . '/' . $session_key) }}"--}}
        {{ __('Add To Cart') }}
        <i class="fas fa-shopping-basket ms-1" style="font-size: initial;"></i>
    </a>
</div>
