@extends('layouts.admin.app')

@section('title', translate('Merchant List'))

@push('css_or_js')
    <script src="https://use.fontawesome.com/74721296a6.js"></script>
@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <div class="__wrap-gap-10 justify-content-between d-flex align-items-center">
                <h1 class="page-header-title mb-0"><i
                        class="tio-filter-list"></i> {{translate('merchant')}} {{translate('list')}}
                </h1>
                <a href="{{route('admin.merchant.add')}}" class="btn btn-primary pull-right mr-3"><i
                        class="tio-add-circle"></i> {{translate('Add')}} {{translate('merchant')}}
                </a>
            </div>
        </div>
        <!-- End Page Header -->
        <div class="row gx-2 gx-lg-3">
            <div class="col-sm-12 col-lg-12 mb-3 mb-lg-2">
                <!-- Card -->
                <div class="card">
                    <!-- Header -->
                    <div class="card-header __wrap-gap-10">
                        <div class="flex-start">
                            <h5 class="card-header-title">{{translate('merchant Table')}}</h5>
                            <h5 class="card-header-title text-primary mx-1">({{ $merchants->total() }})</h5>
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
                    <!-- End Header -->

                    <!-- Table -->
                    <div class="table-responsive datatable-custom">
                        <table
                            class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                            <thead class="thead-light">
                            <tr>
                                <th>{{translate('#NO')}}</th>
                                <th style="">{{translate('image')}}</th>
                                <th style="">{{translate('name')}}</th>
                                <th>{{translate('phone')}}</th>
                                <th>{{ translate('callback') }}</th>
                                <th>{{translate('status')}}</th>
                                <th>{{translate('action')}}</th>
                            </tr>
                            </thead>

                            <tbody id="set-rows">
                            @foreach($merchants as $key=>$merchant)
                                <tr>
                                    <td>{{$merchants->firstitem()+$key}}</td>
                                    <td>
                                        <img class="rounded-circle" height="60px" width="60px" style="cursor: pointer"
                                             onclick="location.href='{{route('admin.customer.view',[$merchant['id']])}}'"
                                             onerror="this.src='{{asset('assets/admin/img/160x160/img1.jpg')}}'"
                                             src="{{asset('storage/app/public/merchant')}}/{{$merchant['image']}}">
                                    </td>
                                    <td>
                                        <a href="{{route('admin.merchant.view',[$merchant['id']])}}" class="d-block font-size-sm text-body">
                                            {{$merchant['f_name'].' '.$merchant['l_name']}}
                                        </a>
                                    </td>
                                    <td>
                                        <div>
                                            {{$merchant['phone']}}
                                        </div>
                                        <div>
                                            @if(isset($merchant['email']))
                                                <a href="mailto:{{ $merchant['email'] }}" class="text-primary">{{ $merchant['email'] }}</a>
                                            @else
                                                <span class="badge-pill badge-soft-dark text-muted">Email unavailable</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        {{$merchant->merchant['callback']}}
                                    </td>
                                    <td>
                                        <label class="toggle-switch d-flex align-items-center mb-3" for="welcome_status_{{$merchant['id']}}">
                                            <input type="checkbox" name="welcome_status" class="toggle-switch-input"
                                                   id="welcome_status_{{$merchant['id']}}" {{$merchant?($merchant['is_active']==1?'checked':''):''}}
                                                   onclick="location.href='{{route('admin.merchant.status',[$merchant['id']])}}'">
                                            <span class="toggle-switch-label p-1">
                                                <span class="toggle-switch-indicator"></span>
                                            </span>
                                        </label>
                                    </td>
                                    <td>
                                        <a class="btn-sm btn-primary p-1 m-1"
                                           href="{{route('admin.merchant.view',[$merchant['id']])}}">
                                            <i class="fa fa-eye pl-1" aria-hidden="true"></i>
                                        </a>
                                        <a class="btn-sm btn-secondary p-1 pr-2 m-1"
                                           href="{{route('admin.merchant.edit',[$merchant['id']])}}">
                                            <i class="fa fa-pencil pl-1" aria-hidden="true"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>

                        <div class="page-area">
                            <table>
                                <tfoot>
                                {!! $merchants->links() !!}
                                </tfoot>
                            </table>
                        </div>

                    </div>
                    <!-- End Table -->
                </div>
                <!-- End Card -->
            </div>
        </div>
    </div>
@endsection

@push('script_2')

@endpush
