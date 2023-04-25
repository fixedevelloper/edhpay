@extends('layouts.admin.app')

@section('title', translate('Merchant Settings'))

@push('css_or_js')

@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col-sm mb-sm-0">
                    <h1 class="page-header-title">{{translate('Settings')}}</h1>
                </div>
            </div>
        </div>
        <!-- End Page Header -->

        <div class="row" style="padding-bottom: 20px">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <div class="">
                            <form action="{{route('admin.merchant-config.settings-update')}}" method="post">
                                @csrf
                                <div class="row">
                                    <div class="col-md-12 col-12">
                                        @php($merchant_commission_percent=\App\CentralLogics\helpers::get_business_settings('merchant_commission_percent'))
                                        <div class="form-group">
                                            <label class="input-label text-capitalize" for="merchant_commission_percent">{{translate('Transaction Commission')}} <small class="text-danger">( {{translate('percent (%)')}} )</small></label>
                                            <input type="number" name="merchant_commission_percent" class="form-control" id="merchant_commission_percent" value="{{$merchant_commission_percent??''}}" min="0" step=".02" max="100" required>
                                        </div>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary mb-2">{{translate('save')}}</button>
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
