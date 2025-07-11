<header>
    {!! Theme::partial('header.top', ['colorMode' => 'light', 'headerTopClass' => 'container-fluid pl-85 pr-85', 'showUserMenu' => true]) !!}

    <div
        id="header-sticky"
        @class(['tp-header-area tp-header-sticky has-dark-logo tp-header-height', 'header-main' => ! Theme::get('hasSlider'), 'tp-header-style-transparent-white tp-header-transparent' => Theme::get('hasSlider')])
        {!! Theme::partial('header.sticky-data') !!}
    >
        <div class="tp-header-bottom-3 pl-85 pr-85" style="background-color: {{ $headerMainBackgroundColor }}; color: {{ $headerMainTextColor }}">
            <div class="container-fluid">
                <div class="row align-items-center">
                    <div class="col-xl-2 col-lg-2 col-6">
                        {!! Theme::partial('header.logo', ['hasLogoLight' => true]) !!}
                    </div>
                    <div class="col-xxl-4 col-xl-6 d-none d-xl-block">
                        <div class="main-menu">
                            <nav class="tp-main-menu-content">
                                {!! Menu::renderMenuLocation('main-menu', ['view' => 'main-menu']) !!}
                            </nav>
                        </div>
                    </div>
                    <div class="col-xl-2 col-lg-2 col-6">
                        {!! Theme::partial('header.actions', ['class' => 'justify-content-end ml-50', 'showSearchButton' => true]) !!}
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>
