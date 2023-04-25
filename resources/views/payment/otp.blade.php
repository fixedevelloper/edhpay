@extends('payment.main')

@section('title', translate('OTP'))

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
            <div class="text-right __text-danger">
                <a class="text-right __text-danger"
                   onclick="route_alert('{{ $frontend_callback }}','{{ translate("") }}')"
                   href="javascript:">{{ translate('Cancel') }}
                </a>
            </div>
            <form action="{{ route('verify-otp') }}" method="POST">
                @csrf
                <input class="__form-control" type="hidden" name="payment_id" value="{{$payment_id}}">
                <label class="__form-label">{{ translate('Enter Verification Code') }}</label>
                <input class="__form-control" type="text" name="otp" maxlength="4" placeholder="{{ translate('Verification Code') }}" required>
                <div class="__btn-wrap __mt-16">
                    <button type="submit" class="__btn __btn-primary">
                        {{ translate('Proceed') }}
                    </button>
                </div>
            </form>

            <div class="hotline text-right">
                <div>
                    <svg width="17" height="17" viewBox="0 0 17 17" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path
                            d="M9.53002 1.33325C11.1576 1.33325 12.7185 1.97979 13.8693 3.13064C15.0202 4.28149 15.6667 5.84237 15.6667 7.46992M9.83336 4.66659C10.4964 4.66659 11.1323 4.92998 11.6011 5.39882C12.07 5.86766 12.3334 6.50354 12.3334 7.16659M4.83336 2.99992L2.76252 3.51742C2.02086 3.70325 1.48419 4.37492 1.63586 5.12409C2.51919 9.46825 7.53169 14.4808 11.8759 15.3633C12.6259 15.5158 13.2967 14.9799 13.4825 14.2383L13.9992 12.1666L11.0825 10.4999L9.83252 11.7499C8.16586 10.9166 6.08252 8.83325 5.24919 7.16659L6.50002 5.91659L4.83336 2.99992Z"
                            stroke="black" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    <span>
                        <a class="text-right __text-danger"
                           onclick="resend_otp('{{route('resend-otp')}}','{{ translate("") }}')"
                           href="javascript:">{{ translate('Resend Code') }}
                        </a>
                    </span>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script>
        function resend_otp(route, message) {
            Swal.fire({
                title: '{{ translate("Are you sure?") }}',
                text: message,
                type: 'warning',
                showCancelButton: true,
                cancelButtonColor: 'default',
                confirmButtonColor: '#01684b',
                cancelButtonText: '{{ translate("No") }}',
                confirmButtonText: '{{ translate("Yes") }}',
                reverseButtons: true
            }).then((result) => {
                if (result.value) {
                    $.ajax({
                        url: '{{route('resend-otp')}}',
                        type: "get",
                        success: function (data) {
                            toastr.success(data.message, {
                                CloseButton: true,
                                ProgressBar: true
                            });
                        }
                    });
                }
            })
        }

        function route_alert(route, message) {
            Swal.fire({
                title: '{{ translate("Are you sure?") }}',
                text: message,
                type: 'warning',
                showCancelButton: true,
                cancelButtonColor: 'default',
                confirmButtonColor: '#01684b',
                cancelButtonText: '{{ translate("No") }}',
                confirmButtonText: '{{ translate("Yes") }}',
                reverseButtons: true
            }).then((result) => {
                if (result.value) {
                    location.href = route;
                }
            })
        }
    </script>
@endpush
