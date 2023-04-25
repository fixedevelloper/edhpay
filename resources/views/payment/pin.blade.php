@extends('payment.main')

@section('title', translate('PIN'))

@section('content')
    <div class="__section-wrapper-inner">
        <div class="logo">
            @php($logo=\App\Models\BusinessSetting::where('key','logo')->first())
            @php($logo=$logo->value??'')
            <a href="#">
                <img onerror="this.src='{{asset('public/assets/admin/img/160x160/img2.jpg')}}'"
                     src="{{asset('storage/app/public/business/'.$logo)}}" alt="">
            </a>
        </div>
        <div class="__wrapper">
            <form action="{{ route('verify-pin') }}" method="post">
                @csrf
                <input class="__form-control" type="hidden" name="payment_id" value="{{$payment_id}}">
                <label class="__form-label">{{ translate('Enter PIN Number') }}</label>
                <input class="__form-control" type="text" name="pin" maxlength="4" placeholder="{{ translate('PIN Number') }}" required>
                <div class="__btn-wrap __mt-10">
                    <a class="__btn __btn-close"
                       onclick="route_alert('{{ $frontend_callback }}','{{ translate("") }}')"
                       href="javascript:">{{ translate('Close') }}
                    </a>
                    <button type="submit" class="__btn __btn-primary">
                        {{ translate('Confirm') }}
                    </button>
                </div>
            </form>

        </div>
    </div>
@endsection

@push('script')
    <script>
        function route_alert(route, message) {
            Swal.fire({
                title: '<?php echo e(translate("Are you sure?")); ?>',
                text: message,
                type: 'warning',
                showCancelButton: true,
                cancelButtonColor: 'default',
                confirmButtonColor: '#01684b',
                cancelButtonText: '<?php echo e(translate("No")); ?>',
                confirmButtonText: '<?php echo e(translate("Yes")); ?>',
                reverseButtons: true
            }).then((result) => {
                if (result.value) {
                    location.href = route;
                }
            })
        }
    </script>
@endpush
