@extends('layouts.admin.app')

@section('title', translate('Update Merchant'))

@push('css_or_js')

@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col-sm mb-2 mb-sm-0">
                    <h1 class="page-header-title"><i
                            class="tio-edit"></i> {{translate('update')}} {{translate('merchant')}}
                    </h1>
                </div>
            </div>
        </div>
        <!-- End Page Header -->
        <div class="row gx-2 gx-lg-3">
            <div class="col-sm-12 col-lg-12 mb-3 mb-lg-2 card card-body">
                <form action="{{route('admin.merchant.update', $user->id)}}" method="post" enctype="multipart/form-data">
                    @csrf
                    <div class="row">
                        <div class="col-md-6 col-12">
                            <div class="form-group">
                                <label class="input-label" for="exampleFormControlInput1">{{translate('First Name')}}</label>
                                <input type="text" name="f_name" class="form-control" value="{{ $user->f_name }}"
                                       placeholder="{{translate('First Name')}}" required>
                            </div>
                        </div>
                        <div class="col-md-6 col-12">
                            <div class="form-group">
                                <label class="input-label" for="exampleFormControlInput1">{{translate('Last Name')}}</label>
                                <input type="text" name="l_name" class="form-control" value="{{ $user->l_name }}"
                                       placeholder="{{translate('Last Name')}}" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 col-12">
                            <div class="form-group">
                                <label class="input-label" for="exampleFormControlInput1">{{translate('email')}}
                                    <small class="text-muted">({{translate('optional')}})</small></label>
                                <input type="email" name="email" class="form-control" value="{{ $user->email }}"
                                       placeholder="{{translate('Ex : ex@example.com')}}">
                            </div>
                        </div>
                        <div class="col-md-6 col-12">
                            <label class="input-label" for="exampleFormControlInput1">{{translate('Password')}}</label>
                            <div class="input-group input-group-merge">
                                <input type="password" name="password" class="js-toggle-password form-control form-control input-field"
                                       placeholder="{{translate('8 digit password')}}" minlength="8"
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

                    <div class="row mt-4 mt-md-0">
                        <div class="col-md-4 col-12">
                            <div class="form-group">
                                <label class="input-label" for="exampleFormControlInput1">{{translate('Identification Type')}}</label>
                                <select name="identification_type" class="form-control">
                                    <option value="passport" {{ $user->identification_type == 'passport' ? 'selected' : '' }}>{{translate('passport')}}</option>
                                    <option value="driving_license" {{ $user->identification_type == 'driving_license' ? 'selected' : '' }}>{{translate('driving')}} {{translate('license')}}</option>
                                    <option value="nid" {{ $user->identification_type == 'nid' ? 'selected' : '' }}>{{translate('nid')}}</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4 col-12">
                            <div class="form-group">
                                <label class="input-label"
                                       for="exampleFormControlInput1">{{translate('Identification Number')}}</label>
                                <input type="number" name="identification_number" class="form-control" value="{{ $user->identification_number }}"
                                       placeholder="{{ translate('Ex: 534354') }}" required>
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
                                    <div class="row g-2" id="coba">
                                        @foreach(json_decode($user['identification_image'],true) as $img)
                                            <div class="two__item w-50">
                                                <div class="max-h-140px existing-item">
                                                    <img onerror="this.src='{{asset('assets/admin/img/160x160/img1.jpg')}}'" style="height: 100px; width: 100px;"
                                                         src="{{asset('storage/app/public/merchant').'/'.$img}}">
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <div class="form-group">
                        <label class="text-dark">{{translate('Merchant Image')}}</label><small
                            class="text-danger"> *( {{translate('ratio 1:1')}} )</small>
                        <div class="custom-file">
                            <input type="file" name="image" id="customFileEg1" class="custom-file-input"
                                   accept=".jpg, .png, .jpeg, .gif, .bmp, .tif, .tiff|image/*" id="file"  onchange="loadFileImage(event)" style="display: none;">
                            <label class="custom-file-label"
                                   for="customFileEg1">{{translate('choose')}} {{translate('file')}}</label>
                        </div>

                        <div class="text-center mt-3">
                            <img style="height: 200px;border: 1px solid; border-radius: 10px;" id="viewer1"
                                 onerror="this.src='{{asset('assets/admin/img/400x400/img2.jpg')}}'"
                                 src="{{asset('storage/app/public/merchant').'/'.$user['image']}}" alt="merchant image"/>
                        </div>
                    </div>

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
                            <input type="file" name="logo" id="customFileEg2" class="custom-file-input"
                                   accept=".jpg, .png, .jpeg, .gif, .bmp, .tif, .tiff|image/*" onchange="loadFileLogo(event)" style="display: none;">
                            <label class="custom-file-label"
                                   for="customFileEg2">{{translate('choose')}} {{translate('file')}}</label>
                        </div>
                        <div class="text-center mt-3">
                            <img style="height: 200px;border: 1px solid; border-radius: 10px;" id="viewer2"
                                 onerror="this.src='{{asset('assets/admin/img/400x400/img2.jpg')}}'"
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
        var loadFileImage = function(event) {
            var image = document.getElementById('viewer1');
            image.src = URL.createObjectURL(event.target.files[0]);
        };

        var loadFileLogo = function(event) {
            var image2 = document.getElementById('viewer2');
            image2.src = URL.createObjectURL(event.target.files[0]);
        };
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
                    //image: '{{asset('assets/admin/img/400x400/img2.jpg')}}',
                    //width: '100%'
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
/*
        function delete_domain_field(row_id) {
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
        });

        function delete_existing_field(key) {
            $('#domain_'+ key).val('');
            $('#callback_'+ key).val('');
            $('#domain_section_'+ key).remove();
            $('#callback_section_'+ key).remove();
            $('#remove_section_'+ key).remove();
            $('#add_section_'+ key).remove();
            $('#domain_'+ key).removeAttr('required');
            $('#callback_'+ key).removeAttr('required');
        }*/

    </script>
@endpush
