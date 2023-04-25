@extends('payment.main')

@section('title', translate('Phone'))

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
            <div class="__agent">
                <img src="{{ asset('storage/app/public/merchant')}}/{{$merchant_user->merchant->logo}}" alt="img">
                <div class="__agent-details">
                    <h5 class="name">{{$merchant_user->merchant->store_name}}</h5>
                    <span>{{$merchant_user->phone}}</span>
                </div>
            </div>
            <div class="__agent">
                <div class="__agent-details">
                    <h5 class="name">{{translate('Amount Pay')}} : {{ Helpers::set_symbol($payment_record->amount) }}</h5>
                </div>
            </div>
            <label class="__form-label text-center">{{ translate('Your 6Cash Account Number') }}</label>
            <form action="{{ route('send-otp') }}" method="POST">
                @csrf
                <div class="__px-3">
                    <input class="__form-control" type="hidden" name="payment_id" value="{{$payment_id}}">
                    <div class="input-group __input-grp">
                        <select id="dial_country_code" name="dial_country_code" class="__form-control" required>

                            <option value="">{{ translate('select country') }}</option>
                            @foreach(PHONE_CODE as $country_code)
                                <option value="{{ $country_code['code'] }}" {{ strpos($country_code['name'], $current_user_info->countryName) !== false? 'selected' : '' }}>{{ $country_code['name'] }}</option>
                            @endforeach
                        </select>
                        <input type="number" name="phone" class="__form-control" value="{{ old('phone') }}"
                               placeholder="{{translate('Ex : 171*******')}}" required>
                    </div>
                    <label class="__form-check" for="checkbox">
                        <input type="checkbox" name="terms_and_condition" id="checkbox" required>
                        <div class="__form-check-label">
                            <span>
                                {{ translate('I agree to the') }}
                            </span>
                            <a href="{{ route('pages.terms-conditions') }}" target="_blank">{{ translate('Terms & conditions') }}</a>
                        </div>
                    </label>
                </div>
                <div class="__btn-wrap">
                    <a class="__btn __btn-close"
                       onclick="route_alert('{{$payment_record->callback}}','{{ translate("") }}')"
                       href="javascript:">{{ translate('Close') }}
                    </a>
                    <button type="submit" class="__btn __btn-primary">
                        {{ translate('Proceed') }}
                    </button>
                </div>
            </form>

            <div class="hotline text-center">
                @php($hotline=\App\Models\BusinessSetting::where('key','hotline_number')->first())
                <a href="tel: {{ $hotline->value }}">
                    <svg width="17" height="17" viewBox="0 0 17 17" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path
                            d="M9.53002 1.33325C11.1576 1.33325 12.7185 1.97979 13.8693 3.13064C15.0202 4.28149 15.6667 5.84237 15.6667 7.46992M9.83336 4.66659C10.4964 4.66659 11.1323 4.92998 11.6011 5.39882C12.07 5.86766 12.3334 6.50354 12.3334 7.16659M4.83336 2.99992L2.76252 3.51742C2.02086 3.70325 1.48419 4.37492 1.63586 5.12409C2.51919 9.46825 7.53169 14.4808 11.8759 15.3633C12.6259 15.5158 13.2967 14.9799 13.4825 14.2383L13.9992 12.1666L11.0825 10.4999L9.83252 11.7499C8.16586 10.9166 6.08252 8.83325 5.24919 7.16659L6.50002 5.91659L4.83336 2.99992Z"
                            stroke="black" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    <span>{{ translate('Hotline') }}</span>
                    <span>{{ translate($hotline->value ?? '') }}</span>
                </a>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script>

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

