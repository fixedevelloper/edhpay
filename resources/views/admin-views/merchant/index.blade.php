@extends('layouts.admin.app')

@section('title', translate('Add New Merchant'))

@push('css_or_js')

@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col-sm mb-2 mb-sm-0">
                    <h1 class="page-header-title"><i class="tio-add-circle-outlined"></i>
                        {{translate('Add New Merchant')}}</h1>
                </div>
            </div>
        </div>
        <!-- End Page Header -->
        <div class="row gx-2 gx-lg-3">
            <div class="col-sm-12 col-lg-12 mb-3 mb-lg-2 card card-body">
                <form action="{{route('admin.merchant.store')}}" method="post" enctype="multipart/form-data">
                    @csrf
                    <div class="row">
                        <div class="col-md-6 col-12">
                            <div class="form-group">
                                <label class="input-label" for="exampleFormControlInput1">{{translate('First Name')}}</label>
                                <input type="text" name="f_name" class="form-control" value="{{ old('f_name') }}"
                                       placeholder="{{translate('First Name')}}" required>
                            </div>
                        </div>
                        <div class="col-md-6 col-12">
                            <div class="form-group">
                                <label class="input-label" for="exampleFormControlInput1">{{translate('Last Name')}}</label>
                                <input type="text" name="l_name" class="form-control" value="{{ old('l_name') }}"
                                       placeholder="{{translate('Last Name')}}" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-4 col-12">
                            <div class="form-group">
                                <label class="input-label" for="exampleFormControlInput1">{{translate('email')}}
                                    <small class="text-muted">({{translate('optional')}})</small></label>
                                <input type="email" name="email" class="form-control" value="{{ old('email') }}"
                                       placeholder="{{translate('Ex : ex@example.com')}}">
                            </div>
                        </div>
                        <div class="col-lg-4 col-12">
                            <div class="form-group">
                                <label class="input-label d-block"
                                       for="exampleFormControlInput1">{{translate('phone')}}<small class="text-danger"></small></label>
                                    <div class="input-group __input-grp">
                                        <select id="country_code" name="country_code" class="__input-grp-select" required>
                                            <option value="">{{ translate('select') }}</option>
                                            @foreach(PHONE_CODE as $country_code)
                                                <option value="{{ $country_code['code'] }}" {{ strpos($country_code['name'], $current_user_info->countryName) !== false ? 'selected' : '' }}>{{ $country_code['name'] }}</option>
                                            @endforeach
                                        </select>
                                        <input type="number" name="phone" class="form-control __input-grp-input" value="{{ old('phone') }}"
                                               placeholder="{{translate('Ex : 171*******')}}" required>
                                    </div>
                            </div>
                        </div>

                        <div class="col-lg-4 col-12">
                            <label class="input-label" for="exampleFormControlInput1">{{translate('Password')}}</label>
                            <div class="input-group input-group-merge">
                                <input type="password" name="password" class="js-toggle-password form-control form-control input-field"
                                       placeholder="{{translate('8 digit Password')}}" required minlength="8"
                                       data-hs-toggle-password-options='{
                                            "target": "#changePassTarget",
                                            "defaultClass": "tio-hidden-outlined",
                                            "showClass": "tio-visible-outlined",
                                            "classChangeTarget": "#changePassIcon"
                                            }'>
                                <div id="changePassTarget" class="input-group-append">
                                    <a class="input-group-text" href="javascript:">
                                        <i id="changePassIcon" class="tio-visible-outlined"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-4 mt-lg-0">
                        <div class="col-md-4 col-12">
                            <div class="form-group">
                                <label class="input-label" for="exampleFormControlInput1">{{translate('Identification Type')}}</label>
                                <select name="identification_type" class="form-control">
                                    <option value="passport">{{translate('passport')}}</option>
                                    <option value="driving_license">{{translate('driving')}} {{translate('license')}}</option>
                                    <option value="nid">{{translate('nid')}}</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4 col-12">
                            <div class="form-group">
                                <label class="input-label"
                                       for="exampleFormControlInput1">{{translate('Identification Number')}}</label>
                                <input type="text" name="identification_number" class="form-control" value="{{ old('identification_number') }}"
                                       placeholder="{{ translate('Ex : DH-23434-LS') }}" required>
                            </div>
                        </div>

                        <div class="col-md-4 col-12">
                            <div class="form-group mb-3">
                                <div class="form-group mb-0">
                                    <label class="input-label" for="exampleFormControlInput1">{{translate('Identification Image')}}
                                        <small class="text-danger"> *( {{translate('ratio 1:1')}} )</small></label>

                                </div>
                            </div>
                            <div>
                                <div class="product--coba spartan_item_wrapper-area">
                                    <div class="row g-2" id="coba"></div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <div class="form-group">
                        <label class="text-dark">{{translate('Merchant Image')}}</label><small
                            class="text-danger"> *( {{translate('ratio 1:1')}} )</small>
                        <div class="custom-file">
                            <input type="file" name="image" id="customFileEg1" class="custom-file-input"
                                   accept=".jpg, .png, .jpeg, .gif, .bmp, .tif, .tiff|image/*" required>
                            <label class="custom-file-label"
                                   for="customFileEg1">{{translate('choose')}} {{translate('file')}}</label>
                        </div>

                        <div class="text-center mt-3">
                            <img style="height: 200px;border: 1px solid; border-radius: 10px;" id="viewer"
                                 src="{{asset('assets/admin/img/400x400/img2.jpg')}}" alt="merchant image"/>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 col-12">
                            <div class="form-group">
                                <label class="input-label" for="exampleFormControlInput1">{{translate('Store Name')}}</label>
                                <input type="text" name="store_name" class="form-control" value="{{ old('store_name') }}"
                                       placeholder="{{translate('Store Name')}}" required>
                            </div>
                        </div>
                        <div class="col-md-6 col-12">
                            <div class="form-group">
                                <label class="input-label" for="exampleFormControlInput1">{{translate('Address')}}</label>
                                <input type="text" name="address" class="form-control" value="{{ old('address') }}"
                                       placeholder="{{translate('Address')}}" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 col-12">
                            <div class="form-group">
                                <label class="input-label" for="exampleFormControlInput1">{{translate('BIN')}}</label>
                                <input type="text" name="bin" class="form-control" value="{{ old('bin') }}"
                                       placeholder="{{translate('BIN')}}" required>
                            </div>
                        </div>
                        <div class="col-md-6 col-12">
                            <div class="form-group">
                                <label class="input-label" for="exampleFormControlInput1">{{translate('callback')}}</label>
                                <input type="text" name="callback" class="form-control" value="{{ old('callback') }}"
                                       placeholder="{{translate('callback')}}" required>
                            </div>
                        </div>

                    </div>

                    <div class="form-group">
                        <label class="text-dark">{{translate('Logo')}}</label><small
                            class="text-danger"> *( {{translate('ratio 1:1')}} )</small>
                        <div class="custom-file">
                            <input type="file" name="logo" id="customFileEg2" class="custom-file-input"
                                   accept=".jpg, .png, .jpeg, .gif, .bmp, .tif, .tiff|image/*" required>
                            <label class="custom-file-label"
                                   for="customFileEg1">{{translate('choose')}} {{translate('file')}}</label>
                        </div>

                        <div class="text-center mt-3">
                            <img style="height: 200px;border: 1px solid; border-radius: 10px;" id="viewer_1"
                                 src="{{asset('assets/admin/img/400x400/img2.jpg')}}" alt="merchant image"/>
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
        function readURL(input) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();

                reader.onload = function (e) {
                    $('#viewer').attr('src', e.target.result);
                }

                reader.readAsDataURL(input.files[0]);
            }
        }
        function readURL2(input) {
            if (input.files && input.files[0]) {
                var reader_1 = new FileReader();

                reader_1.onload = function (e) {
                    $('#viewer_1').attr('src', e.target.result);
                }

                reader_1.readAsDataURL(input.files[0]);
            }
        }

        $("#customFileEg1").change(function () {
            readURL(this);
        });

        $("#customFileEg2").change(function () {
            readURL2(this);
        });
    </script>

    <script src="{{asset('assets/admin/js/spartan-multi-image-picker.js')}}"></script>
    <script type="text/javascript">
        $(function () {
            $("#coba").spartanMultiImagePicker({
                fieldName: 'identification_image[]',
                maxCount: 5,
                rowHeight: '120px',
                groupClassName: 'col-4',
                maxFileSize: '',
                placeholderImage: {
                    image: '{{asset('assets/admin/img/400x400/img2.jpg')}}',
                    width: '100%'
                },
                dropFileLabel: "Drop Here",
                onAddRow: function (index, file) {

                },
                onRenderedPreview: function (index) {

                },
                onRemoveRow: function (index) {

                },
                onExtensionErr: function (index, file) {
                    toastr.error('Please only input png or jpg type file', {
                        CloseButton: true,
                        ProgressBar: true
                    });
                },
                onSizeErr: function (index, file) {
                    toastr.error('File size too big', {
                        CloseButton: true,
                        ProgressBar: true
                    });
                }
            });
        });
    </script>

    <script>

        /*function delete_domain_field(row_id) {
            //console.log(row_id);
            $( `#domain-row--${row_id}` ).remove();
            count--;
        }


        jQuery(document).ready(function ($) {
            count = 1;
            $('#add-domain').on('click', function (event) {
                if(count <= 15) {
                    event.preventDefault();

                    $('#domain-div').append(
                        `<div class="row bg-light" id="domain-row--${count}">
                            <div class="col-md-5 col-12">
                                <div class="form-group">
                                    <label class="input-label" for="exampleFormControlInput1">{{translate('Store Domain')}}</label>
                                    <input type="text" name="domain[]" id="domain_${count}" class="form-control" value="{{ old('domain') }}"
                                           placeholder="{{translate('Domain')}}" required>
                                </div>
                            </div>
                            <div class="col-md-5 col-12">
                                <div class="form-group">
                                    <label class="input-label">{{translate('Domain Callback')}}</label>
                                    <input type="text" name="callback[]" id="callback_${count}" class="form-control" value="{{ old('callback') }}"
                                           placeholder="{{translate('Callback')}}" required>
                                </div>
                            </div>

                            <div class="col-1 p-1"
                                 data-toggle="tooltip" data-placement="top" title="{{translate('Remove the input field')}}">
                                <div class="btn form-control mt-4" onclick="delete_domain_field(${count})">
                                    <i class="tio-delete-outlined"></i>
                                </div>
                            </div>
                        </div>`
                    );

                    count++;
                } else {
                    Swal.fire({
                        title: '{{translate('Reached maximum')}}',
                        confirmButtonText: '{{translate('ok')}}',
                    });
                }
            })
        });*/
    </script>
@endpush
