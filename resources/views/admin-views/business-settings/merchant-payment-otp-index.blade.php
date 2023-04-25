@extends('layouts.admin.app')

@section('title', translate('Merchant OTP'))

@push('css_or_js')

@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col-sm mb-sm-0">
                    <h1 class="page-header-title">{{translate('Payment OTP Verification')}}</h1>
                </div>
            </div>
        </div>
        <!-- End Page Header -->

        <div class="row" style="padding-bottom: 20px">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body" style="padding: 20px">
                        <div class="flex-between">
                            <h3>{{translate('OTP')}}</h3>
                        </div>
                        <div class="mt-4">
                            @php($otp_status=\App\CentralLogics\Helpers::get_business_settings('payment_otp_verification'))
                            <form
                                action="{{route('admin.merchant-config.merchant-payment-otp-verification-update')}}" method="post">
                                @csrf

                                <div class="form-group mb-2 mt-2">
                                    <input type="radio" name="payment_otp_verification"
                                           value="1" {{isset($otp_status) && $otp_status==1?'checked':''}}>
                                    <label style="padding-left: 10px">{{translate('active')}}</label>
                                    <br>
                                </div>
                                <div class="form-group mb-2">
                                    <input type="radio" name="payment_otp_verification"
                                           value="0" {{isset($otp_status) && $otp_status==0?'checked':''}}>
                                    <label
                                        style="padding-left: 10px">{{translate('inactive')}} </label>
                                    <br>
                                </div>

                                {{--<button type="{{env('APP_MODE')!='demo'?'submit':'button'}}"
                                        onclick="{{env('APP_MODE')!='demo'?'':'call_demo()'}}"
                                        class="btn btn-primary mb-2">{{translate('save')}}</button>--}}

                                <button type="submit"
                                        class="btn btn-primary mb-2">{{translate('save')}}</button>
                            </form>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script_2')

@endpush
