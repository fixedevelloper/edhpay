@extends('payment.main')

@section('title', translate('PIN'))

@section('content')
    <div class="__section-wrapper-inner">
        <div class="logo">
            <a href="#">
                <img src="{{ asset('public/assets/payment/img/logo.svg') }}" alt="">
            </a>
        </div>
        <div class="__wrapper">
            <div class="__wrapper-inner">
                <img src="{{ asset('public/assets/payment/img/payment.svg') }}" alt="">
                <div>
                    {{ translate('Payment Successfully Completed') }} !
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script>
        window.setTimeout(function() {
            window.location.href = '{{ route('success-callback', ['payment_id'=>$payment_id]) }}';
        }, 3000);
    </script>
@endpush

