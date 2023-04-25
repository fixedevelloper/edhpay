@extends('layouts.admin.app')

@section('title', translate('Log'))

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
                    aria-current="page">{{translate('Log')}}</li>
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
                            <h5 class="card-header-title">{{translate('Logs')}}</h5>
                            <h5 class="card-header-title text-primary mx-1">({{ $user_logs->total() }})</h5>
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
                            class="table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table"
                            style="width: 100%">
                            <thead class="thead-light">
                            <tr>
                                <th >{{translate('name')}}</th>
                                <th>{{translate('phone')}}</th>
                                <th>{{translate('ip_address')}}</th>
                                <th>{{translate('device_id')}}</th>
{{--                                <th>{{translate('browser')}}</th>--}}
                                <th>{{translate('os')}}</th>
                                <th>{{translate('device_model')}}</th>
                                <th>{{translate('login_time')}}</th>
                            </tr>
                            </thead>

                            <tbody id="set-rows">
                            @foreach($user_logs as $key=>$user_log)
                                @if($user_log->user)
                                    <tr>
                                        <td>
                                            <a class="d-block font-size-sm text-body"
                                               href="{{route('admin.customer.view',[$user_log->user['id']])}}">
                                                {{$user_log->user['f_name'].' '.$user_log->user['l_name']}}
                                            </a>
                                        </td>
                                        <td>
                                            {{$user_log->user['phone']}}
                                        </td>
                                        <td>{{ $user_log->ip_address }}</td>
                                        <td>{{ $user_log->device_id }}</td>
{{--                                        <td>{{ $user_log->browser }}</td>--}}
                                        <td>{{ $user_log->os }}</td>
                                        <td>{{ $user_log->device_model }}</td>
                                        <td>{{ date('d-M-Y H:iA', strtotime($user_log->created_at)) }}</td>
                                    </tr>
                                @endif
                            @endforeach
                            </tbody>
                        </table>

                        <hr>
                        <table>
                            <tfoot>
                            {!! $user_logs->links() !!}
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
