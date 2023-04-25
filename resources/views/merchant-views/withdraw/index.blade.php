@extends('layouts.merchant.app')

@section('title', translate('Withdraw Request'))

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col-sm mb-2 mb-sm-0">
                    <h1 class="page-header-title"><i class="tio-image"></i> {{translate('Withdraw')}}</h1>
                </div>
            </div>
        </div>

        <!-- End Page Header -->
        <div class="row gx-2 gx-lg-3">
            <div class="col-sm-12 col-lg-12 mb-3 mb-lg-2 card card-body mx-3">
                <form action="{{ route('merchant.withdraw.request-store') }}" method="post">
                    @csrf
                    <div class="row">
                        <div class="form-group col-12 col-md-4">
                            <label class="input-label" for="exampleFormControlInput1">{{translate('withdraw methods')}}</label>
                            <select name="withdrawal_method_id" class="form-control js-select2-custom" id="withdrawal_method_id" required>
                                <option value="" selected>{{translate('select')}}</option>
                                @foreach($withdrawal_methods as $method)
                                    <option value="{{ $method->id }}">{{ $method->method_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-12 col-md-4">
                            <div class="d-flex justify-content-between">
                                <label class="input-label" for="exampleFormControlInput1">{{translate('amount')}}</label>
                                <span>{{translate('Available Balance')}} : {{ Helpers::set_symbol($maximum_amount) }}</span>
                            </div>
                            <input type="number" name="amount" min="0" max="{{ $maximum_amount }}" step="any" class="form-control" placeholder="{{translate('amount')}}" required>
                        </div>
                        <div class="form-group col-12 col-md-4">
                            <label class="input-label" for="exampleFormControlInput1">{{translate('Sender Note')}}</label>
                            <input type="text" name="sender_note" class="form-control" placeholder="{{translate('Sender Note')}}">
                        </div>
                    </div>
                    <div class="row" id="payment_method_div" >

                    </div>
                    <button type="submit" class="btn btn-primary mt-4">{{translate('Add Request')}}</button>
                </form>
            </div>

        </div>
    </div>

@endsection

@push('script_2')
    <script>

        $(document).ready(function() {
            $('#withdrawal_method_id').change(function () {
                let withdrawal_method_id = this.value;
                $('#payment_method_div').empty();
                $.ajax({
                    url: '{{route('merchant.withdraw.method-data')}}',
                    dataType : 'json',
                    type : 'GET',
                    data: {
                        withdrawal_method_id : withdrawal_method_id,
                    },

                    error: function() {
                        toastr.error('Server error', Error, {
                            CloseButton: true,
                            ProgressBar: true
                        });
                    },
                    success: function(data) {
                        console.log(data.method_fields);
                        for (let i = 0; i < data.method_fields.length; i++ ) {
                            let name = data.method_fields[i].input_name;
                            let label_name = name.replace("_", " ");

                            /*label_name = label_name.toLowerCase().replace(/\b[a-z]/g, function(letter) {
                                return letter.toUpperCase();
                            });*/

                            $('#payment_method_div').append(`
                                <div class="form-group col-12  col-md-6">
                                    <label class="input-label text-capitalize" for="exampleFormControlInput1">${label_name}</label>
                                    <input type="${data.method_fields[i].input_type}" name="${data.method_fields[i].input_name}" class="form-control" placeholder="${data.method_fields[i].placeholder?? ''}" required>
                                </div>
                            `)
                        }
                    },
                });
            });

        });


    </script>
@endpush
