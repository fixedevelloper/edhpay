@extends('layouts.admin.app')

@section('title', translate('Withdraw_Requests'))

@push('css_or_js')

@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col-sm mb-2 mb-sm-0 flex-between">
                    <h1 class="page-header-title">{{translate('Withdraw_Requests')}}</h1>
                    <h1><i class="tio-user-switch"></i></h1>
                </div>
            </div>
            <div class="d-flex flex-wrap justify-content-between align-items-center border-bottom gap-3">
                <ul class="nav nav-tabs">
                    <li class="nav-item">
                        <a class="nav-link {{$request_status=='all'?'active':''}}"
                           href="{{url()->current()}}?request_status=all">
                            {{translate('all')}}
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{$request_status=='pending'?'active':''}}"
                           href="{{url()->current()}}?request_status=pending">
                            {{translate('pending')}}
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{$request_status=='approved'?'active':''}}"
                           href="{{url()->current()}}?request_status=approved">
                            {{translate('approved')}}
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{$request_status=='denied'?'active':''}}"
                           href="{{url()->current()}}?request_status=denied">
                            {{translate('denied')}}
                        </a>
                    </li>
                </ul>
            </div>
        </div>
        <!-- End Page Header -->
        <div class="row gx-2 gx-lg-3">
            <div class="col-sm-12 col-lg-12 mb-3 mb-lg-2">
                <div class="card">
                    <div class="card-header flex-between __wrap-gap-10">
                        <div class="flex-start">
                            <h5 class="card-header-title">{{translate('transaction Table')}}</h5>
                            <h5 class="card-header-title text-primary mx-1">({{ $withdraw_requests->total() }})</h5>
                        </div>
                        <div class="flex-between __wrap-gap-10 align-items-center">
                            <div class="form-group px-1 mb-0">
                                    <a class="btn-sm btn-secondary form-control" href="{{route('admin.withdraw.download', ['withdrawal_method'=>$method,'search'=>$search, 'request_status'=>$request_status])}}">{{translate('Export')}}</a>
                            </div>
                            <div class="form-group px-1 mb-0">
                                <select name="withdrawal_method" class="form-control js-select2-custom" id="withdrawal_method" required>
                                    <option value="all" selected>{{translate('Filter by method')}}</option>
                                    @foreach($withdrawal_methods as $withdrawal_method)
                                        <option value="{{$withdrawal_method->id}}" {{ $method == $withdrawal_method->id ? 'selected' : '' }}>{{translate($withdrawal_method->method_name)}}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <form action="{{url()->current()}}" method="GET">
                                    <div class="input-group">
                                        <input id="datatableSearch_" type="search" name="search"
                                               class="form-control"
                                               placeholder="{{translate('Search')}}" aria-label="Search"
                                               value="{{$search??''}}" required autocomplete="off">
                                        <div class="input-group-append">
                                            <button type="submit" class="input-group-text"><i class="tio-search"></i>
                                            </button>
                                        </div>
                                    </div>
                                </form>
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
                                <th>{{translate('Sender')}}</th>
                                <th>{{translate('Sender Type')}}</th>
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
                                    <td>
                                        @if($withdraw_request->user)
                                            <span class="d-block font-size-sm text-body">
                                                <a href="{{route('admin.customer.view',[$withdraw_request->user->id])}}">
                                                    {{ $withdraw_request->user->f_name . ' ' . $withdraw_request->user->l_name }}
                                                </a>
                                            </span>
                                        @else
                                            <span class="badge badge-pill">{{translate('User_not_available')}}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($withdraw_request->user)
                                            <small class="badge badge-pill">
                                                {{ $withdraw_request->user->type == 1 ? translate('Agent') : ($withdraw_request->user->type == 3 ? translate('Merchant') : translate('Customer')) }}
                                            </small>
                                        @else
                                            <span class="badge badge-pill">{{translate('Not_available')}}</span>
                                        @endif
                                    </td>
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
                                            <div>
                                                <a href="{{ route('admin.withdraw.status_update', ['request_id'=>$withdraw_request->id, 'request_status'=>'approve']) }}" class="btn btn-primary btn-sm"> {{translate('Approve')}}</a>
                                                <a href="{{ route('admin.withdraw.status_update', ['request_id'=>$withdraw_request->id, 'request_status'=>'deny']) }}" class="btn btn-warning btn-sm"> {{translate('Deny')}}</a>
                                            </div>
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

@push('script_2')
    <script>
        $("#withdrawal_method").on('change', function (event) {
            location.href = "{{route('admin.withdraw.requests')}}" + '?request_status=all' + '&withdrawal_method=' + $(this).val();
        })
    </script>
@endpush
