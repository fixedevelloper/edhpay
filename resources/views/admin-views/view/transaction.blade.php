@extends('layouts.admin.app')

@section('title', translate('Transaction'))

@push('css_or_js')

@endpush

@section('content')

    <div class="content container-fluid">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a
                        href="{{route('admin.dashboard')}}">{{translate('Dashboard')}}</a>
                </li>
                <li class="breadcrumb-item"
                    aria-current="page">{{translate('Transactions')}}</li>
            </ol>
        </nav>

        <!-- Page Header -->
        <div class="page-header mb-3">
            <div class="flex-between row mx-1">
                <div>
                    <h1 class="page-header-title"></h1>
                </div>
            </div>
            <!-- Nav Scroller -->
            <div class="js-nav-scroller hs-nav-scroller-horizontal">
                <!-- Nav -->
                @include('admin-views.view.partails.navbar')
                <!-- End Nav -->
            </div>
            <!-- End Nav Scroller -->
        </div>
        <!-- End Page Header -->

        <div class="row gx-2 gx-lg-3">
            <div class="col-sm-12 col-lg-12 mb-3 mb-lg-2">
                <div class="card">
                    <div class="card-header flex-between __wrap-gap-10">
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
                                    @php
                                        $sender = $transaction['transaction_type'] == 'payment' ? $transaction['to_user_id'] : $transaction['from_user_id'];
                                        $receiver = $transaction['transaction_type'] == 'payment' ? $transaction['from_user_id'] : $transaction['to_user_id'];
                                    @endphp
                                    <td>
                                        @php($sender_info = Helpers::get_user_info($sender))
                                        @if($sender_info != null)
                                            <a href="{{route('admin.customer.view',[$sender])}}">
                                                {{ $sender_info->f_name ?? '' }} {{ $sender_info->phone ?? ''}}
                                            </a>
                                        @else
                                            <span class="text-muted badge badge-danger text-dark">{{ translate('User unavailable') }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @php($receiver_info = Helpers::get_user_info($receiver))
                                        @if($receiver_info != null)
                                            <a href="{{route('admin.customer.view',[$receiver])}}">
                                                {{ $receiver_info->f_name ?? '' }} {{ $receiver_info->phone ?? '' }}
                                            </a>
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
