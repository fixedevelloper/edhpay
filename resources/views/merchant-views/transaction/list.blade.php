@extends('layouts.merchant.app')

@section('title', translate('Transaction'))

@push('css_or_js')

@endpush

@section('content')

    <div class="content container-fluid">
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col-sm mb-2 mb-sm-0 flex-between">
                    <h1 class="page-header-title">{{translate('transaction')}}</h1>
                </div>
            </div>
            <div class="d-flex flex-wrap justify-content-between align-items-center border-bottom gap-3">
                <ul class="nav nav-tabs">
                    <li class="nav-item">
                        <a class="nav-link {{$trx_type=='all'?'active':''}}"
                           href="{{url()->current()}}?trx_type=all">
                            {{translate('all')}}
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{$trx_type=='debit'?'active':''}}"
                           href="{{url()->current()}}?trx_type=debit">
                            {{translate('debit')}}
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{$trx_type=='credit'?'active':''}}"
                           href="{{url()->current()}}?trx_type=credit">
                            {{translate('credit')}}
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        <div class="row gx-2 gx-lg-3">
            <div class="col-sm-12 col-lg-12 mb-3 mb-lg-2">
                <div class="card">
                    <div class="card-header __wrap-gap-10 flex-between">
                        <div class="flex-start">
                            <h5 class="card-header-title">{{translate('transaction Table')}}</h5>
                            <h5 class="card-header-title text-primary mx-1">({{ $transactions->total() }})</h5>
                        </div>
                        <div>
                            <form action="{{url()->current()}}" method="GET">
                                <div class="input-group">
                                    <input id="datatableSearch_" type="search" name="search"
                                           class="form-control"
                                           placeholder="{{translate('Search')}}" aria-label="Search"
                                           value="{{$search}}" required autocomplete="off">
                                    <div class="input-group-append">
                                        <button type="submit" class="input-group-text"><i class="tio-search"></i>
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    <!-- Table -->
                    <div class="table-responsive datatable-custom">
                        <table
                            class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                            <thead class="thead-light">
                            <tr>
                                <th>{{translate('No#')}}</th>
                                <th>{{translate('Transaction Id')}}</th>
                                <th>{{translate('Sender')}}</th>
                                <th>{{translate('Receiver')}}</th>
                                <th>{{translate('Debit')}}</th>
                                <th>{{translate('Credit')}}</th>
                                <th>{{translate('Type')}}</th>
                                <th>{{translate('Balance')}}</th>
                                <th>{{translate('Time')}}</th>
                            </thead>

                            <tbody>
                            @foreach($transactions as $key=>$transaction)
                                <tr>
                                    <td>{{$transactions->firstitem()+$key}}</td>
                                    <td>{{ $transaction->transaction_id??'' }}</td>
                                    <td>
                                        @php($sender_info = Helpers::get_user_info($transaction['from_user_id']))
                                        @if($sender_info != null)
                                        <div>
                                           <span>{{ $sender_info->f_name ?? '' }}</span>
                                        </div>
                                        <div>
                                            <span>{{ $sender_info->phone ?? ''}}</span>
                                        </div>

                                        @else
                                            <span class="text-muted badge badge-danger text-dark">{{ translate('User unavailable') }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @php($receiver_info = Helpers::get_user_info($transaction['to_user_id']))
                                        @if($receiver_info != null)
                                            <div>
                                                <span>{{ $receiver_info->f_name ?? '' }}</span>
                                            </div>
                                            <div>
                                                <span>{{ $receiver_info->phone ?? '' }}</span>
                                            </div>
                                        @else
                                            <span class="text-muted badge badge-danger text-dark">{{ translate('User unavailable') }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="">
                                            {{ Helpers::set_symbol($transaction['debit']) }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="">
                                            {{ Helpers::set_symbol($transaction['credit']) }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="text-uppercase text-muted badge badge-light">{{ translate($transaction['transaction_type']) }}</span>
                                    </td>
                                    <td>
                                        <span class="">{{ Helpers::set_symbol($transaction['balance']) }}</span>
                                    </td>
                                    <td>
                                        <span class="text-muted badge badge-light">{{ $transaction->created_at->diffForHumans() }}</span>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>

                        <hr>
                        <table>
                            <tfoot>
                            {!! $transactions->links() !!}
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

@endpush
