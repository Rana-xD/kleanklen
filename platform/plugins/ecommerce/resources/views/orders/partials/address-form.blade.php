<div class="customer-address-payment-form">
    <input type="hidden" name="update-tax-url" id="update-checkout-tax-url" value="{{ route('public.ajax.checkout.update-tax') }}">
    <div class="mb-3 form-group">
        @if (auth('customer')->check())
            <p>{{ __('Account') }}: <strong>{{ auth('customer')->user()->name }}</strong> - {!! Html::email(auth('customer')->user()->email) !!} (<a href="{{ route('customer.logout') }}">{{ __('Logout') }})</a></p>
        @else
            <p>{{ __('Already have an account?') }} <a href="{{ route('customer.login') }}">{{ __('Login') }}</a></p>
        @endif
    </div>

    {!! apply_filters('ecommerce_checkout_address_form_before') !!}

    @auth('customer')
        <div class="mb-3 form-group">
            @if ($isAvailableAddress)
                <label
                    class="mb-2 form-label"
                    for="address_id"
                >{{ __('Select available addresses') }}:</label>
            @endif
            @php
                $oldSessionAddressId = old('address.address_id', $sessionAddressId);
            @endphp
            <div class="list-customer-address @if (!$isAvailableAddress) d-none @endif">
                <div class="select--arrow">
                    <select
                        class="form-control"
                        id="address_id"
                        name="address[address_id]"
                        @required($isAvailableAddress)
                    >
                        <option
                            value="new"
                            @selected($oldSessionAddressId == 'new')
                        >{{ __('Add new address...') }}</option>
                        @if ($isAvailableAddress)
                            @foreach ($addresses as $address)
                                <option
                                    value="{{ $address->id }}"
                                    @selected($oldSessionAddressId == $address->id)
                                >{{ $address->full_address }}</option>
                            @endforeach
                        @endif
                    </select>
                    <x-core::icon name="ti ti-chevron-down" />
                </div>
                <br>
                <div class="address-item-selected @if (!$sessionAddressId) d-none @endif">
                    @if ($isAvailableAddress && $oldSessionAddressId != 'new')
                        @if ($oldSessionAddressId && $addresses->contains('id', $oldSessionAddressId))
                            @include('plugins/ecommerce::orders.partials.address-item', [
                                'address' => $addresses->firstWhere('id', $oldSessionAddressId),
                            ])
                        @elseif ($defaultAddress = get_default_customer_address())
                            @include('plugins/ecommerce::orders.partials.address-item', [
                                'address' => $defaultAddress,
                            ])
                        @else
                            @include('plugins/ecommerce::orders.partials.address-item', [
                                'address' => Arr::first($addresses),
                            ])
                        @endif
                    @endif
                </div>
                <div class="list-available-address d-none">
                    @if ($isAvailableAddress)
                        @foreach ($addresses as $address)
                            <div
                                class="address-item-wrapper"
                                data-id="{{ $address->id }}"
                            >
                                @include(
                                    'plugins/ecommerce::orders.partials.address-item',
                                    compact('address'))
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>
    @endauth

    <div class="address-form-wrapper @if (auth('customer')->check() && $oldSessionAddressId !== 'new' && $isAvailableAddress) d-none @endif">
        <div class="form-group mb-3 @error('address.name') has-error @enderror">
            <div class="form-input-wrapper">
                <input
                    class="form-control"
                    id="address_name"
                    name="address[name]"
                    autocomplete="family-name"
                    type="text"
                    value="{{ old('address.name', Arr::get($sessionCheckoutData, 'name')) ?: (auth('customer')->check() ? auth('customer')->user()->name : null) }}"
                    required
                >
                <label for="address_name">{{ __('Full Name') }}</label>
            </div>
            {!! Form::error('address.name', $errors) !!}
        </div>

        <div class="row">
            @if (!in_array('email', EcommerceHelper::getHiddenFieldsAtCheckout()))
                <div @class([
                    'col-12',
                    'col-lg-8' => !in_array(
                        'phone',
                        EcommerceHelper::getHiddenFieldsAtCheckout()),
                ])>
                    <div class="form-group mb-3 @error('address.email') has-error @enderror">
                        <div class="form-input-wrapper">
                            <input
                                class="form-control"
                                id="address_email"
                                name="address[email]"
                                autocomplete="email"
                                type="email"
                                value="{{ old('address.email', Arr::get($sessionCheckoutData, 'email')) ?: (auth('customer')->check() ? auth('customer')->user()->email : null) }}"
                                required
                            >
                            <label for="address_email">{{ __('Email') }}</label>
                        </div>
                        {!! Form::error('address.email', $errors) !!}
                    </div>
                </div>
            @endif
            @if (!in_array('phone', EcommerceHelper::getHiddenFieldsAtCheckout()))
                <div @class([
                    'col-12',
                    'col-lg-4' => !in_array(
                        'email',
                        EcommerceHelper::getHiddenFieldsAtCheckout()),
                ])>
                    <div class="form-group mb-3 @error('address.phone') has-error @enderror">
                        <div class="form-input-wrapper">
                            <input
                                class="form-control"
                                id="address_phone"
                                name="address[phone]"
                                autocomplete="phone"
                                type="tel"
                                value="{{ old('address.phone', Arr::get($sessionCheckoutData, 'phone')) ?: (auth('customer')->check() ? auth('customer')->user()->phone : null) }}"
                            >
                            <label for="address_phone">{{ __('Phone') }}</label>
                        </div>
                        {!! Form::error('address.phone', $errors) !!}
                    </div>
                </div>
            @endif
        </div>

        {!! apply_filters('ecommerce_checkout_address_form_inside', null) !!}

        @if (EcommerceHelper::isUsingInMultipleCountries() && !in_array('country', EcommerceHelper::getHiddenFieldsAtCheckout()))
            <div class="form-group mb-3 @error('address.country') has-error @enderror">
                <div class="select--arrow form-input-wrapper">
                    <select
                        class="form-control"
                        id="address_country"
                        name="address[country]"
                        autocomplete="country"
                        data-form-parent=".customer-address-payment-form"
                        data-type="country"
                        required
                    >
                        @foreach (EcommerceHelper::getAvailableCountries() as $countryCode => $countryName)
                            <option
                                value="{{ $countryCode }}"
                                @selected(old('address.country', Arr::get($sessionCheckoutData, 'country', EcommerceHelper::getDefaultCountryId())) == $countryCode)
                            >
                                {{ $countryName }}
                            </option>
                        @endforeach
                    </select>
                    <x-core::icon name="ti ti-chevron-down" />
                    <label for="address_country">{{ __('Country') }}</label>
                </div>
                {!! Form::error('address.country', $errors) !!}
            </div>
        @else
            <input
                id="address_country"
                name="address[country]"
                type="hidden"
                value="{{ EcommerceHelper::getFirstCountryId() }}"
            >
        @endif

        <div class="row">
            

            @if (!in_array('city', EcommerceHelper::getHiddenFieldsAtCheckout()))
                <div @class(['col-sm-6 col-12' => ! in_array('state', EcommerceHelper::getHiddenFieldsAtCheckout()), 'col-12' => in_array('state', EcommerceHelper::getHiddenFieldsAtCheckout())])>
                    <div class="form-group mb-3 @error('address.city') has-error @enderror">
                        @if (EcommerceHelper::useCityFieldAsTextField())
                            <div class="form-input-wrapper">
                                <input
                                    class="form-control"
                                    id="address_city"
                                    name="address[city]"
                                    autocomplete="city"
                                    type="text"
                                    value="{{ old('address.city', Arr::get($sessionCheckoutData, 'city')) }}"
                                    required
                                >
                                <label for="address_city">{{ __('City') }}</label>
                            </div>
                        @else
                            <div class="select--arrow form-input-wrapper">
                                <select
                                    class="form-control"
                                    id="address_city"
                                    name="address[city]"
                                    autocomplete="city"
                                    data-type="city"
                                    data-using-select2="false"
                                    data-url="{{ route('ajax.cities-by-state') }}"
                                    required
                                >
                                    <option value="">{{ __('Select city...') }}</option>
                                    @if (old('address.state', Arr::get($sessionCheckoutData, 'state')) || in_array('state', EcommerceHelper::getHiddenFieldsAtCheckout()))
                                        @foreach (EcommerceHelper::getAvailableCitiesByState(old('address.state', Arr::get($sessionCheckoutData, 'state')), old('address.country', Arr::get($sessionCheckoutData, 'country'))) as $cityId => $cityName)
                                            <option
                                                value="{{ $cityId }}"
                                                @if (old('address.city', Arr::get($sessionCheckoutData, 'city')) == $cityId) selected @endif
                                            >{{ $cityName }}</option>
                                        @endforeach
                                    @endif
                                </select>
                                <x-core::icon name="ti ti-chevron-down" />
                                <label for="address_city">{{ __('City') }}</label>
                            </div>
                        @endif
                        {!! Form::error('address.city', $errors) !!}
                    </div>
                </div>
            @endif
        </div>

        {!! apply_filters('ecommerce_checkout_address_form_after_city_field', null, $sessionCheckoutData) !!}

        @if (!in_array('address', EcommerceHelper::getHiddenFieldsAtCheckout()))
            <div class="form-group mb-3">
                <label class="mb-2 form-label">{{ __('Location') }}</label>
                <div class="location-options mb-3">
                    <label class="form-check form-check-inline">
                        <input
                            class="form-check-input"
                            type="radio"
                            name="address[location_type]"
                            value="phnom_penh"
                            checked
                            id="location_phnom_penh"
                        >
                        <span class="form-check-label">{{ __('Phnom Penh') }}</span>
                    </label>
                    <label class="form-check form-check-inline">
                        <input
                            class="form-check-input"
                            type="radio"
                            name="address[location_type]"
                            value="province"
                            id="location_province"
                        >
                        <span class="form-check-label">{{ __('Province') }}</span>
                    </label>
                </div>

                <!-- <div id="province_selector" class="mb-3 d-none">
                    <div class="select--arrow form-input-wrapper">
                        <select
                            class="form-control"
                            id="cambodia_province"
                            name="address[cambodia_province]"
                            data-type="province"
                        >
                            <option value="">{{ __('Select province...') }}</option>
                            <option value="Banteay Meanchey">Banteay Meanchey</option>
                            <option value="Battambang">Battambang</option>
                            <option value="Kampong Cham">Kampong Cham</option>
                            <option value="Kampong Chhnang">Kampong Chhnang</option>
                            <option value="Kampong Speu">Kampong Speu</option>
                            <option value="Kampong Thom">Kampong Thom</option>
                            <option value="Kampot">Kampot</option>
                            <option value="Kandal">Kandal</option>
                            <option value="Kep">Kep</option>
                            <option value="Koh Kong">Koh Kong</option>
                            <option value="Kratie">Kratie</option>
                            <option value="Mondulkiri">Mondulkiri</option>
                            <option value="Oddar Meanchey">Oddar Meanchey</option>
                            <option value="Pailin">Pailin</option>
                            <option value="Preah Sihanouk">Preah Sihanouk</option>
                            <option value="Preah Vihear">Preah Vihear</option>
                            <option value="Prey Veng">Prey Veng</option>
                            <option value="Pursat">Pursat</option>
                            <option value="Ratanakiri">Ratanakiri</option>
                            <option value="Siem Reap">Siem Reap</option>
                            <option value="Stung Treng">Stung Treng</option>
                            <option value="Svay Rieng">Svay Rieng</option>
                            <option value="Takeo">Takeo</option>
                            <option value="Tbong Khmum">Tbong Khmum</option>
                        </select>
                        <x-core::icon name="ti ti-chevron-down" />
                        <label for="cambodia_province">{{ __('Province') }}</label>
                    </div>
                </div> -->

                @if (!in_array('state', EcommerceHelper::getHiddenFieldsAtCheckout()))
                <div  id="province_selector" class="col-sm-6 col-12">
                    <div class="form-group mb-3 @error('address.state') has-error @enderror">
                        @if (EcommerceHelper::loadCountriesStatesCitiesFromPluginLocation())
                            <div class="select--arrow form-input-wrapper">
                                <select
                                    class="form-control"
                                    id="address_state"
                                    name="address[state]"
                                    autocomplete="state"
                                    data-form-parent=".customer-address-payment-form"
                                    data-type="state"
                                    data-url="{{ route('ajax.states-by-country') }}"
                                    required
                                    onchange="handleStateChange(this)"
                                >
                                    <option value="">{{ __('Select state...') }}</option>
                                    @if (old('address.country', Arr::get($sessionCheckoutData, 'country') ?: EcommerceHelper::getDefaultCountryId()) || !EcommerceHelper::isUsingInMultipleCountries())
                                        @foreach (EcommerceHelper::getAvailableStatesByCountry(old('address.country', Arr::get($sessionCheckoutData, 'country') ?: EcommerceHelper::getDefaultCountryId())) as $stateId => $stateName)
                                            <option
                                                value="{{ $stateId }}"
                                                @if (old('address.state', Arr::get($sessionCheckoutData, 'state')) == $stateId) selected @endif
                                            >{{ $stateName }}</option>
                                        @endforeach
                                    @endif
                                </select>
                                <x-core::icon name="ti ti-chevron-down" />
                                <label for="address_state">{{ __('State') }}</label>
                            </div>
                        @else
                            <div class="form-input-wrapper">
                                <input
                                    class="form-control"
                                    id="address_state"
                                    name="address[state]"
                                    autocomplete="state"
                                    type="text"
                                    value="{{ old('address.state', Arr::get($sessionCheckoutData, 'state')) }}"
                                    required
                                >
                                <label for="address_state">{{ __('State') }}</label>
                            </div>
                        @endif
                        {!! Form::error('address.state', $errors) !!}
                    </div>
                </div>
            @endif

                <div id="address_input" class="form-group @error('address.address') has-error @enderror">
                    <div class="form-input-wrapper">
                        <input
                            class="form-control"
                            id="address_address"
                            name="address[address]"
                            autocomplete="address"
                            type="text"
                            value="{{ old('address.address', Arr::get($sessionCheckoutData, 'address')) }}"
                            required
                        >
                        <label for="address_address">{{ __('Address') }}</label>
                    </div>
                    {!! Form::error('address.address', $errors) !!}
                </div>
            </div>

            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const phnomPenhRadio = document.getElementById('location_phnom_penh');
                    const provinceRadio = document.getElementById('location_province');
                    const provinceSelector = document.getElementById('province_selector');
                    const addressInput = document.getElementById('address_input');

                    function updateFields() {
                        const stateInput = document.getElementById('address_state');
                        const addressField = document.getElementById('address_address');
                        
                        if (phnomPenhRadio.checked) {
                            provinceSelector.classList.add('d-none');
                            addressInput.classList.remove('d-none');
                            // Make state field not required when Phnom Penh is selected
                            if (stateInput) {
                                stateInput.removeAttribute('required');
                                
                                // Set the state field value to Phnom Penh (value 15)
                                stateInput.value = '15';
                            }
                            // Address is required for Phnom Penh
                            if (addressField) {
                                addressField.setAttribute('required', 'required');
                            }
                        } else if (provinceRadio.checked) {
                            provinceSelector.classList.remove('d-none');
                            addressInput.classList.add('d-none');
                            // State is required for Province
                            if (stateInput) {
                                stateInput.setAttribute('required', 'required');
                                // Reset state value when switching to province
                                stateInput.value = '';
                            }
                            // Make address field not required when Province is selected
                            if (addressField) {
                                addressField.removeAttribute('required');
                            }
                        }
                    }
                    


                    phnomPenhRadio.addEventListener('change', updateFields);
                    provinceRadio.addEventListener('change', updateFields);

                    // Initialize on page load
                    updateFields();
                });
            </script>
        @endif

        @if (EcommerceHelper::isZipCodeEnabled())
            <div class="form-group mb-3 @error('address.zip_code') has-error @enderror">
                <div class="form-input-wrapper">
                    <input
                        class="form-control"
                        id="address_zip_code"
                        name="address[zip_code]"
                        autocomplete="postal-code"
                        type="text"
                        value="{{ old('address.zip_code', Arr::get($sessionCheckoutData, 'zip_code')) }}"
                        required
                    >
                    <label for="address_zip_code">{{ __('Zip code') }}</label>
                </div>
                {!! Form::error('address.zip_code', $errors) !!}
            </div>
        @endif
    </div>

    @if (!auth('customer')->check())
        <div id="register-an-account-wrapper">
            <div class="mb-3">
                <label class="form-check">
                    <input
                        id="create_account"
                        name="create_account"
                        type="checkbox"
                        value="1"
                        class="form-check-input"
                        @if (old('create_account') == 1) checked @endif
                    >
                    <span
                        class="form-check-label"
                    >{{ __('Register an account with above information?') }}</span>
                </label>
            </div>

            <div class="password-group @if (!$errors->has('password') && !$errors->has('password_confirmation')) d-none @endif">
                <div class="row">
                    <div class="col-md-6 col-12">
                        <div class="form-group  @error('password') has-error @enderror">
                            <div class="form-input-wrapper">
                                <input
                                    class="form-control"
                                    id="password"
                                    name="password"
                                    type="password"
                                    autocomplete="new-password"
                                >
                                <label for="password">{{ __('Password') }}</label>
                            </div>
                            {!! Form::error('password', $errors) !!}
                        </div>
                    </div>

                    <div class="col-md-6 col-12">
                        <div class="form-group @error('password_confirmation') has-error @enderror">
                            <div class="form-input-wrapper">
                                <input
                                    class="form-control"
                                    id="password-confirm"
                                    name="password_confirmation"
                                    type="password"
                                    autocomplete="password-confirmation"
                                >
                                <label for="password-confirm">{{ __('Password confirmation') }}</label>
                            </div>
                            {!! Form::error('password_confirmation', $errors) !!}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {!! apply_filters('ecommerce_checkout_address_form_after', null, $sessionCheckoutData) !!}
</div>
