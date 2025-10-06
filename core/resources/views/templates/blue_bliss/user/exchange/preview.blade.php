@extends($activeTemplate . 'layouts.master')
@section('content')
    <div class="container">
        <div class="row gy-4">
            <div class="col-12">
                <div class="card custom--card">
                    <div class="card-body">
                        <h6 class="text-center"> @lang('Exchange ID: ') <span
                                class="text-muted">#{{ $exchange->exchange_id }}</span></h6>
                        <p class="mt-1 fw-bold text-center text--warning">
                            @lang('Send')
                            {{ number_format($exchange->sending_amount + $exchange->sending_charge, $exchange->sendCurrency->show_number_after_decimal) }}
                            {{ __(ucfirst(@$exchange->sendCurrency->cur_sym)) }} @lang('via')
                            {{ __(@$exchange->sendCurrency->name) }} @lang('to get')
                            {{ number_format($exchange->receiving_amount - $exchange->receiving_charge, $exchange->receivedCurrency->show_number_after_decimal) }}
                            {{ __(ucfirst(@$exchange->receivedCurrency->cur_sym)) }} @lang('via')
                            {{ __(@$exchange->receivedCurrency->name) }}
                        </p>

                        @if ($exchange->expired_at)
                            <div class="expire-time text-center">
                                @if ($expired)
                                    <span class="text-danger">
                                        <i class="las la-exclamation-circle"></i>
                                        {{ __(@$expireMessage) }}
                                    </span>
                                @else
                                    <span>
                                        <i class="las la-exclamation-circle"></i>
                                        {{ __(@$expireMessage) }}
                                    </span>
                                @endif
                            </div>
                        @endif

                    </div>
                </div>
            </div>
            <x-exchange-sending-receiveing-details :exchange=$exchange :charges=$charges/>
            <div class="col-md-12">
                <div class="card custom--card">
                    <div class="card-body">
                        <form method="post" action="{{ route('user.exchange.confirm') }}" enctype="multipart/form-data"
                            class="disableSubmission">
                            @csrf
                            <div class="form-group">
                                <label class="form-label">
                                    @lang('Your')
                                    {{ __(@$exchange->receivedCurrency->name) }}
                                    @lang('Wallet Number/ID')
                                </label>
                                <input type="text" class="form-control form--control" name="wallet_id" required>
                            </div>
                            <x-viser-form identifier="id"
                                identifierValue="{{ @$exchange->receivedCurrency->userDetailsData->id }}" />
                            <button class="btn btn--base w-100 confirmationBtn" type="submit" @disabled($expired)>
                                @lang('Confirm Exchange')
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    {{-- Modal --}}
    <div class="modal fade" id="exchangeInfoModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static"
        data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center">
                    <h6>@lang('Exchange ID: ') <span class="text-muted">#{{ $exchange->exchange_id }}</span></h6>
                    <p class="mt-1 fw-bold text--warning">
                        @lang('Send')
                        {{ number_format($exchange->sending_amount + $exchange->sending_charge, $exchange->sendCurrency->show_number_after_decimal) }}
                        {{ __(ucfirst(@$exchange->sendCurrency->cur_sym)) }} @lang('via')
                        {{ __(@$exchange->sendCurrency->name) }} @lang('to get')
                        {{ number_format($exchange->receiving_amount - $exchange->receiving_charge, $exchange->receivedCurrency->show_number_after_decimal) }}
                        {{ __(ucfirst(@$exchange->receivedCurrency->cur_sym)) }} @lang('via')
                        {{ __(@$exchange->receivedCurrency->name) }}
                    </p>

                    @if ($exchange->expired_at)
                        <div class="expire-time text-center">
                            @if ($expired)
                                <span class="text-danger">
                                    <i class="las la-exclamation-circle"></i>
                                    {{ __(@$expireMessage) }}
                                </span>
                            @else
                                <span>
                                    <i class="las la-exclamation-circle"></i>
                                    {{ __(@$expireMessage) }}
                                </span>
                            @endif
                        </div>
                    @endif

                    <button type="button" class="btn btn--base mt-4" id="modalOkayBtn">@lang('Okay')</button>
                </div>
            </div>
        </div>
    </div>

@endsection
@push('style')
    <style>
        .expire-time span {
            font-weight: 700;
        }
    </style>
@endpush

@push('script')
    <script>
        "use strict";
        $(document).ready(function () {
            @if(gs('exchange_auto_cancel'))
            const modal = new bootstrap.Modal(document.getElementById('exchangeInfoModal'));
            modal.show();

            $('#modalOkayBtn').on('click', function () {
                modal.hide();
            });
            @endif
        });
    </script>
@endpush