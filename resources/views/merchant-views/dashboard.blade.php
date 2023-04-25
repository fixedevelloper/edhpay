@extends('layouts.merchant.app')

@section('title', translate('dashboard'))

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <style>
        .grid-card {
            border: 2px solid #00000012;
            border-radius: 10px;
            padding: 10px;
        }

        .label_1 {
            position: absolute;
            font-size: 10px;
            background: #FF4C29;
            color: #ffffff;
            width: 146px;
            padding: 2px;
            font-weight: bold;
            border-radius: 6px;
            text-align: center;
        }

        .center-div {
            text-align: center;
            border-radius: 6px;
            padding: 6px;
            border: 2px solid #8080805e;
        }
    </style>
@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header"
             style="padding-bottom: 0!important;border-bottom: 0!important;margin-bottom: 1.25rem!important;">
            <div class="row align-items-center">
                <div class="col-sm mb-2 mb-sm-0">
                    <h1 class="page-header-title">{{translate('welcome')}}
                        , {{auth('user')->user()->f_name}}.</h1>
                    <p>{{ translate('welcome_to_6cash_merchant_panel') }}</p>
                </div>
                <div class="col-sm mb-2 mb-sm-0" style="height: 25px">
                    <label class="badge badge-soft-success float-right">
                        {{ translate('Software Version') }} : {{ env('SOFTWARE_VERSION') }}
                    </label>
                </div>
            </div>
        </div>
        <!-- End Page Header -->

        <!-- Card -->
        <div class="card card-body mb-3 mb-lg-5">
            <div class="gx-2 gx-lg-3 mb-2">
                <div class="flex-between">
                    <h4>{{translate('EMoney Statistics')}}</h4>
                    <h4><i style="font-size: 30px" class="tio-money-vs pr-1"></i></h4>
                </div>
            </div>
            <div class="row gx-2 gx-lg-3" id="order_stats">
                @include('merchant-views.partials._stats', ['data'=>$balance])
            </div>
        </div>
        <!-- End Card -->

        <div class="row gx-2 gx-lg-3">
            <div class="col-sm-12 col-lg-12 mb-3 mb-lg-2">
                <div class="card">
                    <div class="card-header flex-between">
                        <div class="flex-start">
                            <h5 class="card-header-title">{{translate('Withdraw Table')}}</h5>

                        </div>
                        <div class="flex-between">

                            <div class="form-group px-1">
                                <select name="withdrawal_method" class="form-control js-select2-custom" id="withdrawal_method" required>
                                    <option value="all" selected>{{translate('Filter by method')}}</option>
                                    @foreach($withdrawal_methods as $withdrawal_method)
                                        <option value="{{$withdrawal_method->id}}" {{ $method == $withdrawal_method->id ? 'selected' : '' }}>{{translate($withdrawal_method->method_name)}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <!-- Table -->
                    <div class="table-responsive datatable-custom">
                        <table
                            class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table table-striped">
                            <thead class="thead-light">
                            <tr>
                                <th>{{translate('No#')}}</th>
                                <th>{{translate('Requested Amount')}}</th>
                                <th>{{translate('Withdrawal Method')}}</th>
                                <th>{{translate('Withdrawal Method Fields')}}</th>
                                <th>{{translate('Sender_Note')}}</th>
                                <th>{{translate('Admin_Note')}}</th>
                                <th>{{translate('Request_Status')}}</th>
                                <th>{{translate('Payment_Status')}}</th>
                                <th>{{translate('Requested time')}}</th>
                            </tr>
                            </thead>

                            <tbody>
                            @forelse($withdraw_requests as $key=>$withdraw_request)
                                <tr>
                                    <td>{{$withdraw_requests->firstitem()+$key}}</td>
                                    <td>{{ Helpers::set_symbol($withdraw_request->amount) }}</td>
                                    <td><span class="badge badge-pill">{{ translate($withdraw_request->withdrawal_method ? $withdraw_request->withdrawal_method->method_name : '') }}</span></td>
                                    <td>
                                        @foreach($withdraw_request->withdrawal_method_fields as $key=>$item)
                                            {{translate($key) . ': ' . $item}} <br/>
                                    @endforeach
                                    <td>{{ $withdraw_request->sender_note }}</td>
                                    <td>{{ $withdraw_request->admin_note }}</td>
                                    <td>
                                        @if( $withdraw_request->request_status == 'pending' )
                                            <span class="badge badge-primary"> {{translate('Pending')}}</span>
                                        @elseif( $withdraw_request->request_status == 'approved' )
                                            <span class="badge badge-success"> {{translate('Approved')}}</span>
                                        @elseif( $withdraw_request->request_status == 'denied' )
                                            <span class="badge badge-danger"> {{translate('Denied')}}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($withdraw_request->is_paid )
                                            <span class="badge badge-pill badge-success">{{translate('Paid')}}</span>
                                        @else
                                            <span class="badge badge-pill badge-danger">{{translate('Not_Paid')}}</span>
                                        @endif
                                    </td>
                                    <td style="width: 10%">{{ date_time_formatter($withdraw_request->created_at) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="10" class="text-center">{{translate('No_data_available')}}</td></tr>
                            @endforelse
                            </tbody>
                        </table>

                        <hr>
                        <table>
                            <tfoot>
                            {!! $withdraw_requests->links() !!}
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
            <!-- End Table -->
        </div>

    </div>

        @endsection

        @push('script')
            <script src="{{asset('assets/admin')}}/vendor/chart.js/dist/Chart.min.js"></script>
            <script src="{{asset('assets/admin')}}/vendor/chart.js.extensions/chartjs-extensions.js"></script>
            <script src="{{asset('assets/admin')}}/vendor/chartjs-plugin-datalabels/dist/chartjs-plugin-datalabels.min.js"></script>
        @endpush


        @push('script_2')
            <script>
                $("#withdrawal_method").on('change', function (event) {
                    location.href = "{{route('merchant.dashboard')}}" + '?withdrawal_method=' + $(this).val();
                })
            </script>

        @endpush
