@extends('layouts.admin.app')

@section('title', translate('Verification List'))

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://use.fontawesome.com/74721296a6.js"></script>
@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <div class="row align-items-center mb-3">
                <div class="col-sm flex-between">
                    <h1 class="page-header-title"><i
                            class="tio-user-add"></i> {{translate('customers')}}
                    </h1>
                    <a href="{{route('admin.customer.add')}}" class="btn btn-primary pull-right mr-1"><i
                            class="tio-add-circle"></i> {{translate('Add')}} {{translate('Customer')}}
                    </a>
                </div>
            </div>
            <!-- End Row -->
        </div>
        <!-- End Page Header -->

        <!-- Card -->
        <div class="card">
            <!-- Header -->
            <div class="card-header __wrap-gap-10">
                <div class="flex-start">
                    <h5 class="card-header-title">{{translate('Verification requests list')}}</h5>
                    <h5 class="card-header-title text-primary mx-1">({{ $customers->total() }})</h5>
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
                    class="table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table"
                    style="width: 100%">
                    <thead class="thead-light">
                    <tr>
                        <th>{{translate('#')}}</th>
                        <th>{{translate('image')}}</th>
                        <th>{{translate('name')}}</th>
                        <th>{{translate('phone')}}</th>
                        <th>{{translate('email')}}</th>
                        <th>{{translate('Identification Type')}}</th>
                        <th>{{translate('Identification Number')}}</th>
                        <th style="width: 15%">{{translate('Identification Image')}}</th>
                        <th>{{translate('action')}}</th>
                    </tr>
                    </thead>

                    <tbody id="set-rows">
                    @foreach($customers as $key=>$customer)
                        <tr>
                            <td>{{$customers->firstitem()+$key}}</td>
                            <td>
                                <img class="rounded-circle" height="60px" width="60px" style="cursor: pointer"
                                     onclick="location.href='{{route('admin.customer.view',[$customer['id']])}}'"
                                     onerror="this.src='{{asset('assets/admin/img/160x160/img1.jpg')}}'"
                                     src="{{asset('storage/customer')}}/{{$customer['image']}}">
                            </td>
                            <td>
                                <a class="d-block font-size-sm text-body"
                                   href="{{route('admin.customer.view',[$customer['id']])}}">
                                    {{$customer['f_name'].' '.$customer['l_name']}}
                                </a>
                            </td>
                            <td>
                                {{$customer['phone']}}
                            </td>
                            <td>
                                @if(isset($customer['email']))
                                    <a href="mailto:{{ $customer['email'] }}" class="text-primary">{{ $customer['email'] }}</a>
                                @else
                                    <span class="text-muted badge badge-danger text-dark">{{ translate('Email unavailable') }}</span>
                                @endif
                            </td>
                            <td>
                                @if(isset($customer['identification_type']))
                                    {{translate($customer['identification_type'])}}
                                @else
                                    <span class="text-muted badge badge-danger text-dark">{{ translate('Type unavailable') }}</span>
                                @endif
                            </td>
                            <td>
                                @if(isset($customer['identification_number']))
                                    {{ $customer['identification_number']  }}
                                @else
                                    <span class="text-muted badge badge-danger text-dark">{{ translate('Number unavailable') }}</span>
                                @endif
                            </td>
                            <td>
                                <div data-toggle="" data-placement="top" title="{{translate('click for bigger view')}}">
                                    @foreach(json_decode($customer['identification_image'], true) as $identification_image)
                                        @php($image_full_path = asset('storage/user/identity'). '/' .$identification_image)
                                        <img class="" height="60px" width="120px" style="cursor: pointer; border-radius: 3px"
                                             onerror="this.src='{{asset('assets/admin/img/900x400/img1.jpg')}}'"
                                             src="{{$image_full_path}}"
                                             onclick="show_modal('{{$image_full_path}}')">
                                    @endforeach
                                </div>
                            </td>
                            <td>
                                <a class="btn btn-primary p-1 m-1"
                                   href="{{route('admin.customer.view',[$customer['id']])}}">
                                    <i class="fa fa-eye pl-1" aria-hidden="true"></i>
                                </a>
                                @if($customer['is_kyc_verified'] == 0)
                                    <a class="btn-sm btn-success mr-1"
                                       href="{{route('admin.customer.kyc_status_update',[$customer['id'], 1])}}">
                                        <i class="fa fa-check" aria-hidden="true"></i>
                                    </a>
                                    <a class="btn-sm btn-danger"
                                       href="{{route('admin.customer.kyc_status_update',[$customer['id'], 2])}}">
                                        <i class="fa fa-times" aria-hidden="true"></i>
                                    </a>
                                @elseif($customer['is_kyc_verified'] == 2)
                                    <span class="badge badge-danger"> {{translate('Denied')}}</span>
                                @endif

                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <!-- End Table -->

            <!-- Modal -->
            <div class="modal fade bd-example-modal-lg" id="identification_image_view_modal" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-body p-0">
                            <div data-dismiss="modal">
                                <img src="{{asset('assets/admin/img/900x400/img1.jpg')}}" alt=""
                                     class="" id="identification_image_element" style="width: 100%">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Modal End -->

            <!-- Footer -->
            <div class="card-footer">
                <!-- Pagination -->
                <div class="row justify-content-center justify-content-sm-between align-items-sm-center">
                    <div class="col-sm-auto">
                        <div class="d-flex justify-content-center justify-content-sm-end">
                            <!-- Pagination -->
                            {!! $customers->links() !!}
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
    <script>
        function show_modal(image_location) {
            $('#identification_image_view_modal').modal('show');
            if(image_location != null || image_location !== '') {
                $('#identification_image_element').attr("src", image_location);
            }
        }
    </script>
@endpush
