@extends('layouts.merchant.app')

@section('title', translate('Integration Settings'))

@push('css_or_js')

@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header_ pb-4">
            <div class="row align-items-center">
                <div class="col-sm mb-2 mb-sm-0">
                    <h1 class="page-header-title">{{translate('shop')}} {{translate('settings')}}</h1>
                </div>
            </div>
        </div>
        <!-- End Page Header -->
        <div class="row gx-2 gx-lg-3">
            <div class="col-sm-12 col-lg-12 mb-3 mb-lg-2">
                <form action="{{route('merchant.business-settings.shop-settings-update')}}" method="post"  enctype="multipart/form-data">
                    @csrf
                    <div class="row">
                        <div class="col-md-6 col-12">
                            <div class="form-group">
                                <label class="input-label" for="exampleFormControlInput1">{{translate('Store Name')}}</label>
                                <input type="text" name="store_name" class="form-control" value="{{ $merchant->store_name }}"
                                       placeholder="{{translate('Store Name')}}" required>
                            </div>
                        </div>
                        <div class="col-md-6 col-12">
                            <div class="form-group">
                                <label class="input-label" for="exampleFormControlInput1">{{translate('Address')}}</label>
                                <input type="text" name="address" class="form-control" value="{{ $merchant->address }}"
                                       placeholder="{{translate('Address')}}" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 col-12">
                            <div class="form-group">
                                <label class="input-label" for="exampleFormControlInput1">{{translate('BIN')}}</label>
                                <input type="text" name="bin" class="form-control" value="{{ $merchant->bin }}"
                                       placeholder="{{translate('BIN')}}" required>
                            </div>
                        </div>
                        <div class="col-md-6 col-12">
                            <div class="form-group">
                                <label class="input-label" for="exampleFormControlInput1">{{translate('callback')}}</label>
                                <input type="text" name="callback" class="form-control" value="{{ $merchant->callback }}"
                                       placeholder="{{translate('callback')}}" required>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="text-dark">{{translate('Logo')}}</label><small
                            class="text-danger"> *( {{translate('ratio 1:1')}} )</small>
                        <div class="custom-file">
                            <input type="file" name="logo" id="customFileEg1" class="custom-file-input"
                                   accept=".jpg, .png, .jpeg, .gif, .bmp, .tif, .tiff|image/*" onchange="loadFileLogo(event)" style="display: none;">
                            <label class="custom-file-label"
                                   for="customFileEg1">{{translate('choose')}} {{translate('file')}}</label>
                        </div>

                        <div class="text-center mt-3">
                            <img style="height: 200px;border: 1px solid; border-radius: 10px;" id="viewer2"
                                 src="{{asset('storage/app/public/merchant').'/'.$merchant['logo']}}" alt="merchant image"/>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">{{translate('submit')}}</button>
                </form>
            </div>
        </div>
    </div>

@endsection

@push('script_2')
    <script>
        var loadFileLogo = function(event) {
            var image2 = document.getElementById('viewer2');
            image2.src = URL.createObjectURL(event.target.files[0]);
        };
    </script>

    <script src="{{asset('assets/admin/js/spartan-multi-image-picker.js')}}"></script>

@endpush
