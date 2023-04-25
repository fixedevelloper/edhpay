@extends('layouts.admin.app')

@section('title', translate('Details'))

@push('css_or_js')

@endpush

@section('content')
    <div class="content container-fluid">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a
                        href="{{route('admin.dashboard')}}">{{translate('Dashboard')}}</a>
                </li>
                <li class="breadcrumb-item"
                    aria-current="page">{{translate('Details')}}</li>
            </ol>
        </nav>

        <!-- Page Header -->
        <div class="page-header">
            <div class="flex-between row mx-1">
                <div>
                    <h1 class="page-header-title"></h1>
                </div>
            </div>
            <!-- Nav Scroller -->
            <div class="js-nav-scroller hs-nav-scroller-horizontal">
                <!-- Nav -->
                @include('admin-views.view.partails.navbar')
                <!-- End Nav -->
            </div>
            <!-- End Nav Scroller -->
        </div>
        <!-- End Page Header -->


        <div class="row mt-4">
            <div class="col-md-6 mb-3">
                <div class="card">
                    <div class="card-header text-capitalize">{{translate('wallet')}}<i style="font-size: 25px" class="tio-wallet"></i></div>
                    <div class="card-body">
                        <div class="card shadow h-100 for-card-body-3 badge-info"
                             style="background: #444941!important;">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div
                                            class=" font-weight-bold for-card-text text-uppercase mb-1">{{translate('balance')}}</div>
                                        <div
                                            class="for-card-count">{{ $user->emoney['current_balance']??0 }}
                                        </div>
                                    </div>
                                    <div class="col-auto for-margin">
                                        <i class="tio-money-vs"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 mb-3">
                <div class="card word-break">
                    <div class="card-header text-capitalize">{{translate('Personal Info')}}<i style="font-size: 25px" class="tio-info"></i></div>
                    <div class="card-body"
                            style="text-align: {{Session::get('direction') === "rtl" ? 'right' : 'left'}};">
                        <div class="p-lg-4">
                            <div class="flex-start">
                                <div><h5>{{translate('name')}} : </h5></div>
                                <div class="mx-1"><span class="text-dark">{{$user['f_name']??''}} {{$user['l_name']??''}}</span></div>
                            </div>
                            <div class="flex-start">
                                <div><h5>{{translate('Phone')}} : </h5></div>
                                <div class="mx-1"><span class="text-dark">{{$user['phone']??''}}</span></div>
                            </div>
                            @if(isset($user['email']))
                                <div class="flex-start">
                                    <div><h5>{{translate('Email')}} : </h5></div>
                                    <div class="mx-1"><span class="text-dark">{{$user['email']}}</span></div>
                                </div>
                            @endif
                            @if(isset($user['identification_type']))
                                <div class="flex-start">
                                    <div><h5>{{translate('identification_type')}} : </h5></div>
                                    <div class="mx-1"><span class="text-dark">{{translate($user['identification_type'])}}</span></div>
                                </div>
                            @endif
                            @if(isset($user['identification_number']))
                                <div class="flex-start">
                                    <div><h5>{{translate('identification_number')}} : </h5></div>
                                    <div class="mx-1"><span class="text-dark">{{$user['identification_number']}}</span></div>
                                </div>
                            @endif
                            @if($user['type'] == 3)
                                @if(isset($user->merchant))
                                    <div class="flex-start">
                                        <div><h5>{{translate('store_name')}} : </h5></div>
                                        <div class="mx-1"><span class="text-dark">{{$user->merchant['store_name']}}</span></div>
                                    </div>
                                    <div class="flex-start">
                                        <div><h5>{{translate('store_callback')}} : </h5></div>
                                        <div class="mx-1"><span class="text-dark">{{$user->merchant['callback']}}</span></div>
                                    </div>
                                    <div class="flex-start">
                                        <div><h5>{{translate('address')}} : </h5></div>
                                        <div class="mx-1"><span class="text-dark">{{$user->merchant['address']}}</span></div>
                                    </div>
                                    <div class="flex-start">
                                        <div><h5>{{translate('BIN')}} : </h5></div>
                                        <div class="mx-1"><span class="text-dark">{{$user->merchant['bin']}}</span></div>
                                    </div>
                                    <div class="flex-start">
                                        <div><h5>{{translate('public_key')}} : </h5></div>
                                        <div class="mx-1"><span class="text-dark">{{$user->merchant['public_key']}}</span></div>
                                    </div>
                                    <div class="flex-start">
                                        <div><h5>{{translate('secret_key')}} : </h5></div>
                                        <div class="mx-1"><span class="text-dark">{{$user->merchant['secret_key']}}</span></div>
                                    </div>
                                    <div class="flex-start">
                                        <div><h5>{{translate('merchant_number')}} : </h5></div>
                                        <div class="mx-1"><span class="text-dark">{{$user->merchant['merchant_number']}}</span></div>
                                    </div>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script_2')

@endpush
