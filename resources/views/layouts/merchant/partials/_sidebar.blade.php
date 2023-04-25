<style>
    .nav-sub{
        background: #014F5B!important;
    }
</style>

<div id="sidebarMain" class="d-none">
    <aside
        class="js-navbar-vertical-aside navbar navbar-vertical-aside navbar-vertical navbar-vertical-fixed navbar-expand-xl navbar-bordered  ">
        <div class="navbar-vertical-container">
            <div class="navbar-brand-wrapper justify-content-between">
                <!-- Logo -->
                @php($restaurant_logo=\App\CentralLogics\helpers::get_business_settings('logo'))
                <a class="navbar-brand" href="{{route('merchant.dashboard')}}" aria-label="Front">
                    <img class="navbar-brand-logo" style="max-height: 55px; border-radius: 8px;max-width: 100%!important;"
                         onerror="this.src='{{asset('assets/admin/img/1920x400/img2.jpg')}}'"
                         src="{{asset('storage/app/public/business/'.$restaurant_logo)}}"
                         alt="Logo">
                    <img class="navbar-brand-logo-mini" style="max-height: 55px; border-radius: 8px;max-width: 100%!important;"
                         onerror="this.src='{{asset('assets/admin/img/160x160/img2.jpg')}}'"
                         src="{{asset('storage/app/public/business/'.$restaurant_logo)}}" alt="Logo">
                </a>
                <!-- End Logo -->

                <!-- Navbar Vertical Toggle -->
                <button type="button"
                        class="js-navbar-vertical-aside-toggle-invoker navbar-vertical-aside-toggle btn btn-icon btn-xs btn-ghost-dark">
                    <i class="tio-clear tio-lg"></i>
                </button>
                <!-- End Navbar Vertical Toggle -->
            </div>

            <!-- Content -->
            <div class="navbar-vertical-content" style="background-color: #014F5B;">
                <ul class="navbar-nav navbar-nav-lg nav-tabs">
                    <!-- Dashboards -->
                    <li class="navbar-vertical-aside-has-menu {{Request::is('merchant')?'show':''}}">
                        <a class="js-navbar-vertical-aside-menu-link nav-link"
                           href="{{route('merchant.dashboard')}}" title="{{translate('dashboard')}}">
                            <i class="tio-home-vs-1-outlined nav-icon"></i>
                            <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                {{translate('dashboard')}}
                            </span>
                        </a>
                    </li>
                    <li class="navbar-vertical-aside-has-menu {{Request::is('merchant/transaction')?'show':''}}">
                        <a class="js-navbar-vertical-aside-menu-link nav-link"
                           href="{{route('merchant.transaction', ['trx_type'=>'all'])}}" title="{{translate('dashboard')}}">
                            <i class="tio-money-vs nav-icon"></i>
                            <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                {{translate('transaction')}}
                            </span>
                        </a>
                    </li>
                    <!-- End Dashboards -->
                    <li class="navbar-vertical-aside-has-menu {{Request::is('merchant/withdraw*')?'active':''}}">
                        <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:"
                        >
                            <i class="tio-settings nav-icon"></i>
                            <span
                                class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">{{translate('Withdraw')}}</span>
                        </a>
                        <ul class="js-navbar-vertical-aside-submenu nav nav-sub" style="display: {{Request::is('merchant/withdraw*')?'block':'none'}}">
                            <li class="navbar-vertical-aside-has-menu {{Request::is('merchant/withdraw/request')?'active':''}}">
                                <a class="nav-link " href="{{route('merchant.withdraw.request')}}"
                                   title="{{translate('withdraw')}} {{translate('request')}}">
                                    <span class="tio-circle nav-indicator-icon"></span>
                                    <span class="text-truncate">{{translate('withdraw')}} {{translate('request')}}</span>
                                </a>
                            </li>
                            <li class="navbar-vertical-aside-has-menu {{Request::is('merchant/withdraw/list')?'active':''}}">
                                <a class="nav-link " href="{{route('merchant.withdraw.list', ['request_status'=>'all'])}}"
                                   title="{{translate('request')}} {{translate('list')}}">
                                    <span class="tio-circle nav-indicator-icon"></span>
                                    <span class="text-truncate">{{translate('request')}} {{translate('list')}} </span>
                                </a>
                            </li>
                        </ul>
                    </li>

                    <li class="navbar-vertical-aside-has-menu {{Request::is('merchant/business-settings*')?'active':''}} mb-4">
                        <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:"
                        >
                            <i class="tio-settings-outlined nav-icon"></i>
                            <span
                                class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">{{translate('developer')}}</span>
                        </a>
                        <ul class="js-navbar-vertical-aside-submenu nav nav-sub" style="display: {{Request::is('merchant/business-settings*')?'block':'none'}}">
                            <li class="navbar-vertical-aside-has-menu {{Request::is('merchant/business-settings/shop-settings')?'active':''}}">
                                <a class="nav-link " href="{{route('merchant.business-settings.shop-settings')}}"
                                   title="{{translate('shop')}} {{translate('settings')}}">
                                    <span class="tio-circle nav-indicator-icon"></span>
                                    <span class="text-truncate">{{translate('shop')}} {{translate('settings')}}</span>
                                </a>
                            </li>
                            <li class="navbar-vertical-aside-has-menu {{Request::is('merchant/business-settings/integration-settings*')?'active':''}}">
                                <a class="nav-link " href="{{route('merchant.business-settings.integration-settings')}}"
                                   title="{{translate('integration')}} {{translate('settings')}}">
                                    <span class="tio-circle nav-indicator-icon"></span>
                                    <span class="text-truncate">{{translate('integration')}}</span>
                                </a>
                            </li>
                        </ul>
                    </li>

                </ul>
            </div>
            <!-- End Content -->
        </div>
    </aside>
</div>

<div id="sidebarCompact" class="d-none">

</div>

@push('script_2')
<script>
    $(window).on('load' , function() {
        if($(".navbar-vertical-content li.active").length) {
            $('.navbar-vertical-content').animate({
                scrollTop: $(".navbar-vertical-content li.active").offset().top - 150
            }, 10);
        }
    });
</script>
@endpush
