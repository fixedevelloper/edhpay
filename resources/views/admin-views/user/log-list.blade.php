@extends('layouts.admin.app')

@section('title', translate('User Logs'))

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://use.fontawesome.com/74721296a6.js"></script>
@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Card -->
        <div class="card">
            <!-- Header -->
            <div class="card-header __wrap-gap-10">
                <div class="flex-start">
                    <h5 class="card-header-title">{{translate('User Logs')}}</h5>
                    <h5 class="card-header-title text-primary mx-1">({{ $user_logs->total() }})</h5>
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
            <!-- End Header -->

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
{{--                        <th>{{translate('browser')}}</th>--}}
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
                                       @if($user_log->user->type == 1)
                                       href="{{route('admin.agent.log',[$user_log->user['id']])}}"
                                    @elseif($user_log->user->type == 2)
                                       href="{{route('admin.customer.log',[$user_log->user['id']])}}"
                                    @endif
                                    >
                                        {{$user_log->user['f_name'].' '.$user_log->user['l_name']}}
                                    </a>
                                </td>
                                <td>
                                    {{$user_log->user['phone']}}
                                </td>
                                <td>{{ $user_log->ip_address }}</td>
                                <td>{{ $user_log->device_id }}</td>
{{--                                <td>--}}
{{--                                    @if($user_log->browser)--}}
{{--                                        {{$user_log->browser}}--}}
{{--                                    @else--}}
{{--                                        <small class="badge-pill badge-light">{{translate('Not_available')}}</small>--}}
{{--                                    @endif--}}
{{--                                </td>--}}
                                <td>{{ $user_log->os }}</td>
                                <td>{{ $user_log->device_model }}</td>
                                <td>{{ date_time_formatter($user_log->created_at) }}</td>
                            </tr>
                        @endif
                    @endforeach
                    </tbody>
                </table>
            </div>
            <!-- End Table -->

            <!-- Footer -->
            <div class="card-footer">
                <!-- Pagination -->
                <div class="row justify-content-center justify-content-sm-between align-items-sm-center">
                    <div class="col-sm-auto">
                        <div class="d-flex justify-content-center justify-content-sm-end">
                            <!-- Pagination -->
                            {!! $user_logs->links() !!}
                            <nav id="datatablePagination" aria-label="Activity pagination"></nav>
                        </div>
                    </div>
                </div>
                <!-- End Pagination -->
            </div>
            <!-- End Footer -->
        </div>
        <!-- End Card -->
    </div>
@endsection

@push('script_2')

@endpush
