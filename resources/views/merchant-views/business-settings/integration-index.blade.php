@extends('layouts.merchant.app')

@section('title', translate('Integration Settings'))

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}" />
@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header_ pb-4">
            <div class="row align-items-center">
                <div class="col-sm mb-2 mb-sm-0">
                    <h1 class="page-header-title">{{translate('integration')}} </h1>
                    <span>{{ translate('Merchant Number') }} : {{ $merchant->merchant_number }}</span>
                </div>
            </div>
        </div>
        <!-- End Page Header -->
        <div class="row gx-2 gx-lg-3">
            <div class="col-sm-12 col-lg-12 mb-3 mb-lg-2">
                <form>
                    @csrf
                    <div class="row">
                        <div class="col-md-6 col-12">
                            <div class="form-group">
                                <label class="input-label" for="exampleFormControlInput1">{{translate('Public Key')}}</label>
                                <input type="text" name="public_key" class="form-control" value="{{ $merchant->public_key }}" id="public_key"
                                       placeholder="{{translate('public_key')}}" required>
                            </div>
                        </div>
                        <div class="col-md-6 col-12">
                            <div class="form-group">
                                <label class="input-label" for="exampleFormControlInput1">{{translate('Secret Key')}}</label>

                                <input type="text" name="secret_key" class="form-control" value="{{ $merchant->secret_key }}" id="secret_key"
                                       placeholder="{{translate('secret_key')}}" required>
                            </div>
                        </div>
                    </div>

                    <a type="submit" id="btn-submit" class="btn btn-primary"
                       onclick="regenerate('{{ translate("You want to regenerate public key and merchant key") }}')">
                        {{translate('regenerate')}}
                    </a>
                </form>
            </div>
        </div>
    </div>

@endsection

@push('script_2')
    <script type="text/javascript">

        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        function regenerate( message) {
            Swal.fire({
                title: '{{ translate("Are you sure?") }}',
                text: message,
                type: 'warning',
                showCancelButton: true,
                cancelButtonColor: 'default',
                confirmButtonColor: '#01684b',
                cancelButtonText: '{{ translate("No") }}',
                confirmButtonText: '{{ translate("Yes") }}',
                reverseButtons: true
            }).then((result) => {
                if (result.value) {
                    $.ajax({
                        type:'POST',
                        "_token": "{{ csrf_token() }}",
                        url:"{{ route('merchant.business-settings.integration-settings-update') }}",
                        success: function (data) {
                            $('#public_key').val(data.merchant.public_key);
                            $('#secret_key').val(data.merchant.secret_key);

                            toastr.success(data.message, {
                                CloseButton: true,
                                ProgressBar: true
                            });
                        }
                    });
                }
            })
        }

    </script>
@endpush
