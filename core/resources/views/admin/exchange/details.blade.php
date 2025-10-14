@extends('admin.layouts.app')
@section('panel')
    <div class="row gy-4 justify-content-center">
        <div class="col-xl-4 col-sm-12">
            <div class="card {{ $userBlocked ? 'blocked-user' : '' }}">
                <div class="card-header">
                    <h5 class="card-title">@lang('Sent by user')</h5>
                    {{-- <p>{{ json_encode($userBlocked) }}</p> --}}
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between flex-wrap flex-column">
                            <h5>{{ __(@$exchange->sendCurrency->name) }}</h5>
                            <small class="text-muted"> @lang('Payment Method')</small>
                        </li>
                        <li class="list-group-item d-flex justify-content-between flex-wrap flex-column">
                            <h5>{{ __(@$exchange->sendCurrency->cur_sym) }}</h5>
                            <small class="text-muted"> @lang('Received Currency')</small>
                        </li>
                        <li class="list-group-item d-flex justify-content-between flex-wrap flex-column">
                            <h5>
                                {{ number_format($exchange->sending_amount, @$exchange->sendCurrency->show_number_after_decimal) }}
                                {{ __(@$exchange->sendCurrency->cur_sym) }}
                            </h5>
                            <small class="text-muted"> @lang('Amount')</small>
                        </li>
                        <li class="list-group-item d-flex justify-content-between flex-wrap flex-column">
                            <h5 class="text--danger">
                                {{ number_format( 0, 4) }} {{ __(@$exchange->sendCurrency->cur_sym) }}
                            </h5>
                            <small class="text-muted"> @lang('Hidden Charge Percentage')</small>
                        </li>
                        <li class="list-group-item d-flex justify-content-between flex-wrap flex-column">
                            <h5 class="text--danger">
                                {{ number_format( 0, 4) }} {{ __(@$exchange->sendCurrency->cur_sym) }}
                            </h5>
                            <small class="text-muted"> @lang('Hidden Charge Fixed')</small>
                        </li>

                        
                        @if (isset($charges['sell']))
                            @if (isset($charges['sell']['percent']))
                                @foreach ($charges['sell']['percent'] as $charge)
                                    @php
                                        $charge_amount =  (float)number_format(( ($charge['charge_percent'] / 100) * $exchange->sending_amount) ?? 0, $exchange->sendCurrency->show_number_after_decimal);
                                    @endphp
                                    <li class="list-group-item d-flex justify-content-between flex-wrap flex-column">
                                        <h5 class="{{ $charge_amount > 0? 'text--success': 'text--danger'}}">
                                            {{ $charge_amount }} {{ $exchange->sendCurrency->cur_sym }}
                                        </h5>
                                        <small class="text-muted"> @lang($charge['title'])</small>
                                    </li>
                                @endforeach
                            @endif
                            @if (isset($charges['sell']['fixed']))
                            @foreach ($charges['sell']['fixed'] as $charge)
                                    @php
                                        $charge_amount =  (float)number_format($charge['charge_fixed'] ?? 0, $exchange->sendCurrency->show_number_after_decimal);
                                    @endphp
                                    <li class="list-group-item d-flex justify-content-between flex-wrap flex-column">
                                        <h5 class="{{ $charge_amount > 0? 'text--success': 'text--danger'}}">
                                            {{ $charge_amount }} {{ $exchange->sendCurrency->cur_sym }}
                                        </h5>
                                        <small class="text-muted"> @lang($charge['title'])</small>
                                    </li>
                                @endforeach
                            @endif
                        @endif

                        <li class="list-group-item d-flex justify-content-between flex-wrap flex-column">
                            <h5 class="{{ $exchange->sending_charge > 0? 'text--success': 'text--danger'}}">
                                {{ number_format($exchange->sending_charge, @$exchange->sendCurrency->show_number_after_decimal) }}
                                {{ __(@$exchange->sendCurrency->cur_sym) }}
                            </h5>
                            <small class="text-muted"> @lang('Total Charge')</small>
                        </li>
                        <li class="list-group-item d-flex justify-content-between flex-wrap flex-column">
                            <h5>
                                {{ number_format($exchange->sending_charge + $exchange->sending_amount, @$exchange->sendCurrency->show_number_after_decimal) }}
                                {{ __(@$exchange->sendCurrency->cur_sym) }}
                            </h5>
                            <small class="text-muted"> @lang('Total Amount Sent By User')</small>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-sm-12">
            <div class="card {{ $userBlocked ? 'blocked-user' : '' }}">
                <div class="card-header">
                    <h5 class="card-title">@lang('Receivable for User')</h5>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between flex-wrap flex-column">
                            <h5>{{ __(@$exchange->receivedCurrency->name) }}</h5>
                            <small class="text-muted"> @lang('Payment Method')</small>
                        </li>
                        <li class="list-group-item d-flex justify-content-between flex-wrap flex-column">
                            <h5>{{ __($exchange->receivedCurrency->cur_sym) }}</h5>
                            <small class="text-muted"> @lang('Currency')</small>
                        </li>
                        <li class="list-group-item d-flex justify-content-between flex-wrap flex-column">
                            <h5>
                                {{ number_format($exchange->receiving_amount, $exchange->receivedCurrency->show_number_after_decimal) }}
                                {{ __($exchange->receivedCurrency->cur_sym) }}
                            </h5>
                            <small class="text-muted"> @lang('Amount')</small>
                        </li>
                        @if ($exchange->hidden_charge_percent != null)
                            <li class="list-group-item d-flex justify-content-between flex-wrap flex-column">
                                <h5 class="text--danger">
                                    {{ number_format(($exchange->hidden_charge_percent / 100) * $exchange->receiving_amount ?? 0, 4) }}
                                    {{ __($exchange->receivedCurrency->cur_sym) }}
                                </h5>
                                <small class="text-muted"> @lang('Hidden Charge Percentage')</small>
                            </li>
                        @endif
                        @if ($exchange->hidden_charge_fixed != null)
                            <li class="list-group-item d-flex justify-content-between flex-wrap flex-column">
                                <h5 class="text--danger">
                                    {{ number_format($exchange->hidden_charge_fixed ?? 0, 4) }}
                                    {{ __($exchange->receivedCurrency->cur_sym) }}
                                </h5>
                                <small class="text-muted"> @lang('Hidden Charge Fixed')</small>
                            </li>
                        @endif
                        @if($exchange->hidden_charge_percent == null && $exchange->hidden_charge_fixed == null)
                            <li class="list-group-item d-flex justify-content-between flex-wrap flex-column">
                                <h5 class="text--danger">
                                    {{ number_format($exchange->hidden_charge_fixed ?? 0, 4) }}
                                    {{ __($exchange->receivedCurrency->cur_sym) }}
                                </h5>
                                <small class="text-muted"> @lang('Hidden Percentage')</small>
                            </li>
                            <li class="list-group-item d-flex justify-content-between flex-wrap flex-column">
                                <h5 class="text--danger">
                                    {{ number_format($exchange->hidden_charge_fixed ?? 0, 4) }}
                                    {{ __($exchange->receivedCurrency->cur_sym) }}
                                </h5>
                                <small class="text-muted"> @lang('Hidden Fixed')</small>
                            </li>
                        @endif


                        <!-- @if ($exchange->sell_charge_fixed != null)
                            @if ($exchange->sell_charge_fixed < 0)
                                <li class="list-group-item d-flex justify-content-between flex-wrap flex-column">
                                    <h5 class="text--danger">
                                        {{ number_format($exchange->sell_charge_fixed ?? 0, 4) }}
                                    </h5>
                                    <small class="text-muted"> @lang('Discount')</small>
                                </li>
                            @elseif($exchange->sell_charge_fixed > 0)
                                <li class="list-group-item d-flex justify-content-between flex-wrap flex-column">
                                    <h5 class="text--danger">
                                        {{ number_format($exchange->sell_charge_fixed ?? 0, 4) }}
                                    </h5>
                                    <small class="text-muted"> @lang('Discount')</small>
                                </li>
                            @endif
                        @endif -->
                        @if (isset($charges['buy']))
                            @if (isset($charges['buy']['percent']))
                                @foreach ($charges['buy']['percent'] as $charge)
                                    @php
                                        $charge_amount = (($charge['charge_percent'] / 100) * $exchange->receiving_amount) ?? 0;
                                    @endphp
                                    <li class="list-group-item d-flex justify-content-between flex-wrap flex-column">
                                        <h5 class="{{ $charge_amount > 0? 'text--success': 'text--danger'}}">
                                            {{ number_format(-1 * $charge_amount, $exchange->receivedCurrency->show_number_after_decimal) }} {{ $exchange->receivedCurrency->cur_sym }}
                                        </h5>
                                        <small class="text-muted"> @lang($charge['title'])</small>
                                    </li>
                                @endforeach
                            @endif
                            @if (isset($charges['buy']['fixed']))
                                @foreach ($charges['buy']['fixed'] as $charge)
                                    @php
                                        $charge_amount =   ($charge['charge_fixed']) ?? 0;
                                    @endphp
                                    <li class="list-group-item d-flex justify-content-between flex-wrap flex-column">
                                        <h5 class="{{ $charge_amount > 0? 'text--success': 'text--danger'}}">
                                            {{ number_format(-1 * $charge_amount, $exchange->receivedCurrency->show_number_after_decimal) }} {{ $exchange->receivedCurrency->cur_sym }}
                                        </h5>
                                        <small class="text-muted"> @lang($charge['title'])</small>
                                    </li>
                                @endforeach
                            @endif
                        @endif


                        <li class="list-group-item d-flex justify-content-between flex-wrap flex-column">
                            <h5 class="{{ $exchange->receiving_charge > 0? 'text--success': 'text--danger'}}">
                                {{ number_format(-1 * $exchange->receiving_charge, @$exchange->receivedCurrency->show_number_after_decimal) }}
                                {{ __(@$exchange->receivedCurrency->cur_sym) }}
                            </h5>
                            <small class="text-muted"> @lang('Total Charge')</small>
                        </li>
                        <li class="list-group-item d-flex justify-content-between flex-wrap flex-column">
                            <h5>
                                {{ number_format($exchange->receiving_amount - $exchange->receiving_charge, @$exchange->receivedCurrency->show_number_after_decimal) }}
                                {{ __(@$exchange->receivedCurrency->cur_sym) }}
                            </h5>
                            <small class="text-muted"> @lang('Receivable Amount for User')</small>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-xl-8 col-sm-12">
            @if ($exchange->user_data != null)
                <div class="card b-radius--10 overflow-hidden box--shadow1 mt-3 {{ $userBlocked ? 'blocked-user' : '' }}">
                    <div class="card-header">
                        <h5>@lang('Sending Details')</h5>
                    </div>
                    <div class="card-body">
                        <x-view-form-data :data="$exchange->user_data" />
                    </div>
                </div>
            @endif
            <div class="my-4"></div>
            @if ($exchange->transaction_proof_data != null)

                <div class="card b-radius--10 overflow-hidden box--shadow1 mt-3 {{ $userBlocked ? 'blocked-user' : '' }}">
                    <div class="card-header">
                        <h5>@lang('Transaction Proof')</h5>
                    </div>
                    <div class="card-body">
                        <x-view-form-data :data="$exchange->transaction_proof_data" />
                    </div>
                </div>
                <div class="card b-radius--10 overflow-hidden box--shadow1 mt-3 {{ $userBlocked ? 'blocked-user' : '' }}">
                    <div class="card-header">
                        <h5>@lang('KYC Verification')</h5>
                    </div>
                    <div class="card-body">
                        @foreach ($user_kyc_data as $data)
                            <div class="kyc-row">
                                <div>{{ $data->name }}: </div> <div class="kyc-value {{ $data->value? "true": "false" }}">{{ $data->value? "True" : "False" }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>

            @endif
            <div class="my-4"></div>
            <div class="card {{ $userBlocked ? 'blocked-user' : '' }}">
                <div class="card-header d-flex flex-wrap justify-content-between align-items-center">
                    <h5 class="card-title">@lang('Exchange Information')</h5>
                    {{-- @if ($exchange->status == Status::EXCHANGE_PENDING) --}}
                    @if(checkSpecificPermission('Update - Exchange'))
                    <div class="d-flex flex-wrap justify-content-end mb-3 gap-2">
                        {{-- @if ($exchange->status != Status::EXCHANGE_APPROVED) --}}
                        <button class="btn btn-outline--warning btn-pending flex-grow-1" type="button">
                            <i class="fas fa-clock"></i>
                            @lang('Pending')
                        </button>
                        <button class="btn btn-outline--success btn-hold flex-grow-1" type="button">
                            <i class="fas fa-undo"></i>
                            @lang('Hold')
                        </button>
                        <button class="btn btn-outline--success btn-processing flex-grow-1" type="button">
                            <i class="fas fa-clock"></i>
                            @lang('Processing')
                        </button>
                        {{-- @endif --}}
                        <button class="btn btn-outline--success btn-approved flex-grow-1" type="button">
                            <i class="fas fa-check"></i>
                            @lang('Approve')
                        </button>
                        <button type="button" class="btn-outline--danger btn btn-cancel flex-grow-1" type="button">
                            <i class="fas fa-times"></i>
                            @lang('Cancel')
                        </button>
                        <button type="button" class="btn btn-outline--warning btn-refund flex-grow-1" type="button">
                            <i class="fas fa-undo"></i>
                            @lang('Refund')
                        </button>
                    </div>
                    @endif
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between flex-wrap">
                            <span class="fw-bold"> @lang('Exchange ID')</span>
                            <span><strong>{{ $exchange->exchange_id }}</strong></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between flex-wrap">
                            <span class="fw-bold"> @lang('User Name')</span>
                            <span>
                                <a class="text--primary" href="{{ route('admin.users.detail', $exchange->user_id) }}">
                                    <span class="text--primary">@</span>{{ __(@$exchange->user->username) }}
                                </a>
                            </span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between flex-wrap">
                            <span class="fw-bold"> @lang('Status')</span>
                            <div class="text-end">
                                @php echo $exchange->badgeData() @endphp
                            </div>
                        </li>
                        <li class="list-group-item d-flex justify-content-between flex-wrap">
                            <span class="fw-bold"> @lang('Automatic Payment Status')</span>
                            <div class="text-end">
                                @if ($exchange->automatic_payment_status)
                                    <span class="badge badge--success">@lang('Completed')</span>
                                @else
                                    <span class="badge badge--danger">@lang('Not Completed')</span>
                                @endif
                            </div>
                        </li>

                        @php
                            $sell_rate_original = 0;
                            $buy_rate_original = 0;
                            if($exchange->transaction_type == "EXCHANGE"){
                                $buy_rate_original =  cutDecimals((1 / (float)$exchange->sell_rate) * (float)$exchange->buy_rate, $exchange->receivedCurrency->show_number_after_decimal);
                                $sell_rate_original = cutDecimals((1 / (float)$exchange->buy_rate) * (float)$exchange->sell_rate , $exchange->sendCurrency->show_number_after_decimal);
                                $buy_rate_customer =  cutDecimals(1 / (float)$exchange->customer_buying_rate, $exchange->receivedCurrency->show_number_after_decimal);
                                $sell_rate_customer = cutDecimals(1 / (float)$exchange->customer_selling_rate, $exchange->sendCurrency->show_number_after_decimal);
                            } else if($exchange->transaction_type == "DEPOSIT") {

                                $buy_rate_original = cutDecimals((float)$exchange->sendCurrency->buy_at, $exchange->receivedCurrency->show_number_after_decimal);
                                $sell_rate_original = cutDecimals(1 / (float)$exchange->sendCurrency->buy_at, $exchange->sendCurrency->show_number_after_decimal);
                                $buy_rate_customer = cutDecimals($exchange->buy_rate, $exchange->receivedCurrency->show_number_after_decimal);
                                $sell_rate_customer = cutDecimals(1 / (float)$exchange->buy_rate, $exchange->sendCurrency->show_number_after_decimal); 
                                
                            } else if($exchange->transaction_type == "WITHDRAW") {

                                $buy_rate_original = cutDecimals((float)$exchange->receivedCurrency->sell_at, $exchange->receivedCurrency->show_number_after_decimal);
                                $sell_rate_original = cutDecimals(1 / (float)$exchange->receivedCurrency->sell_at, $exchange->receivedCurrency->show_number_after_decimal);
                                $buy_rate_customer = cutDecimals($exchange->buy_rate, $exchange->sendCurrency->show_number_after_decimal);
                                $sell_rate_customer = cutDecimals(1 / (float)$exchange->buy_rate, $exchange->sendCurrency->show_number_after_decimal);
                            }

                        @endphp

                        <li class="list-group-item d-flex justify-content-between flex-wrap">
                            <span class="fw-bold"> @lang('Buy Rate')</span>
                            <div>
                                <span>
                                    1 {{ $exchange->sendCurrency->name }} {{ $exchange->sendCurrency->cur_sym }}
                                    = {{ $buy_rate_original }}
                                    {{ $exchange->receivedCurrency->name }} {{ $exchange->receivedCurrency->cur_sym }}
                                </span>
                            </div>
                        </li>

                        <li class="list-group-item d-flex justify-content-between flex-wrap">
                            <span class="fw-bold"> @lang('Sell Rate')</span>
                            <div>
                                <span>
                                    1 {{ $exchange->receivedCurrency->name }} {{ $exchange->receivedCurrency->cur_sym }}
                                    = {{ $sell_rate_original }}
                                    {{ $exchange->sendCurrency->name }} {{ $exchange->sendCurrency->cur_sym }}
                                </span>
                            </div>
                        </li>

                        
                        <li class="list-group-item d-flex justify-content-between flex-wrap">
                            <span class="fw-bold"> @lang('Customer Buying Rate')</span>
                            <div>
                                <span @if(number_format($buy_rate_original, 4) != number_format($buy_rate_customer,4)) style="color: red;" @endif>
                                    1 {{ $exchange->sendCurrency->name }} {{ $exchange->sendCurrency->cur_sym }}
                                    = {{ $buy_rate_customer }}
                                    {{ $exchange->receivedCurrency->name }} {{ $exchange->receivedCurrency->cur_sym }}
                                </span>
                            </div>
                        </li>
                        <li class="list-group-item d-flex justify-content-between flex-wrap">
                            <span class="fw-bold"> @lang('Customer Selling Rate')</span>
                            <div>
                                <span @if(number_format($sell_rate_original, 4) != number_format($sell_rate_customer, 4)) style="color: red;" @endif>
                                    1 {{ $exchange->receivedCurrency->name }} {{ $exchange->receivedCurrency->cur_sym }}
                                    = {{ $sell_rate_customer }}
                                    {{ $exchange->sendCurrency->name }} {{ $exchange->sendCurrency->cur_sym }}
                                </span>
                            </div>
                        </li>

                        {{-- ✅ New Conditional Row for Discount or Charge --}}
                        @if ($exchange->currency_discount > 0)
                            <li class="list-group-item d-flex justify-content-between flex-wrap border-dotted">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="svg__icon">
                                        <i class="fas fa-percentage"></i>
                                    </span>
                                    <small class="fw-bold">@lang('Discount')</small>
                                </div>
                                <span class="fw-bold text--success">
                                    {{ number_format($exchange->currency_discount ?? 0, 4) }}%
                                </span>
                            </li>
                        @elseif ($exchange->currency_charge > 0)
                            <li class="list-group-item d-flex justify-content-between flex-wrap border-dotted">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="svg__icon">
                                        <i class="fas fa-coins"></i>
                                    </span>
                                    <small class="fw-bold">@lang('Charge')</small>
                                </div>
                                <span class="fw-bold text--danger">
                                    {{ number_format($exchange->currency_charge ?? 0, 4) }}%
                                </span>
                            </li>
                        @endif
                        <li class="list-group-item d-flex justify-content-between flex-wrap">
                            <span class="fw-bold"> @lang('Customer Wallet')</span>
                            <span class="fw-bold">{{ __(@$exchange->wallet_id) }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between flex-wrap">
                            <span class="fw-bold"> @lang('Exchange Time')</span>
                            <div class="text-end">
                                <span class="d-block">{{ $exchange->created_at->format('d/m/y h:i:s A') }}</span>
                                <span> {{ diffForHumans($exchange->created_at) }}</span>
                            </div>
                        </li>
                        @if ($exchangeLog->count())
                            <div class="card mt-4">
                                <div class="card-header">
                                    <h5 class="card-title">@lang('Exchange Status Log')</h5>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table--light">
                                            <thead>
                                                <tr>
                                                    <th>@lang('Status Changed')</th>
                                                    <th>@lang('Updated By')</th>
                                                    <th>@lang('Updated At')</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($exchangeLog as $log)
                                                    <tr>
                                                        <td>{{ $log->exchange_status }}</td>
                                                        <td>{{ @$log->adminUser->name ?? 'N/A' }}</td>
                                                        <td>{{ \Carbon\Carbon::parse($log->updated_date)->format('d M Y h:i A') }}
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        @endif

                        @if ($exchange->admin_feedback)
                            <li class="list-group-item d-flex justify-content-between flex-wrap">
                                <span class="fw-bold">
                                    @if ($exchange->status == Status::EXCHANGE_REFUND)
                                        @lang('Reason of refund')
                                    @elseif($exchange->status == Status::EXCHANGE_CANCEL)
                                        @lang('Reason of cancel')
                                    @endif
                                </span>
                                <span>{{ __($exchange->admin_feedback) }}</span>
                            </li>
                        @endif
                    </ul>
                    @if ($exchange->status == Status::EXCHANGE_APPROVED)
                        <div class="form-group alert alert-success p-3">
                            <span class="fw-bold text-dark">@lang('This exchange is paid successfully')</span>
                        </div>
                    @endif
                </div>
            </div>

        </div>
    </div>


    <div id="modal" class="modal fade">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">@lang('Confirmation Alert!')</h5>
                    <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                        <i class="las la-times"></i>
                    </button>
                </div>
                <form method="POST" class="disableSubmission">
                    @csrf
                    <input type="hidden" name="id" value="{{ $exchange->id }}">
                    <div class="modal-body"></div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn--primary h-45 w-100">@lang('Submit')</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('breadcrumb-plugins')
    <x-back route="{{ route('admin.exchange.list', 'list') }}" />
    @if (!@$exchange->deposit)
        <a href="{{ route('admin.exchange.download', $exchange->id) }}" class="btn btn-sm btn-outline--info">
            <i class="la la-download"></i>@lang('Download')
        </a>
    @endif
@endpush

@push('script')
    <script>
        "use strict";
        (function($) {
            let modal = $('#modal');

            $('.btn-pending').on('click', function(e) {
                let html = `<p class="text-center">@lang('Are you sure you want to put this exchange on Pending?')</p>`;
                modal.find(".modal-body").html(html);
                modal.find('form').attr('action', `{{ route('admin.exchange.pending', $exchange->id) }}`);
                modal.find(".modal-title").text(`@lang('Pending Exchange')`);
                modal.modal('show');
            });

            $('.btn-hold').on('click', function(e) {
                let html = `<p class="text-center">@lang('Are you sure you want to put this exchange on hold?')</p>`;
                modal.find(".modal-body").html(html);
                modal.find('form').attr('action', `{{ route('admin.exchange.hold', $exchange->id) }}`);
                modal.find(".modal-title").text(`@lang('Hold Exchange')`);
                modal.modal('show');
            });

            $('.btn-processing').on('click', function(e) {
                let html = `<p class="text-center">@lang('Are you sure you want to mark this exchange as processing?')</p>`;
                modal.find(".modal-body").html(html);
                modal.find('form').attr('action', `{{ route('admin.exchange.processing', $exchange->id) }}`);
                modal.find(".modal-title").text(`@lang('Processing Exchange')`);
                modal.modal('show');
            });
        })(jQuery);
    </script>
@endpush


@push('script')
    <script>
        "use strict";
        (function($) {
            let modal = $('#modal');
            $('.btn-approved').on('click', function(e) {
                let html = `
        <div class="form-group">
            <label for="">Transaction Number</label>
            <input type="text" name="transaction" required class="form-control">
        </div>`;
                modal.find(".modal-body").html(html);
                modal.find('form').attr('action', `{{ route('admin.exchange.approve', $exchange->id) }}`)
                modal.find(".modal-title").text(`Approve Exchange`);
                modal.modal('show');
            });
            $('.btn-cancel').on('click', function(e) {
                let html = `
        <div class="form-group">
            <label>Reason Of Cancel</label>
            <textarea type="text" name="cancel_reason" required class="form-control"></textarea>
        </div>`;
                modal.find(".modal-body").html(html);
                modal.find('form').attr('action', `{{ route('admin.exchange.cancel', $exchange->id) }}`)
                modal.find(".modal-title").text(`Cancel Exchange`);
                modal.modal('show');
            });
            $('.btn-refund').on('click', function(e) {
                let html = `
        <div class="form-group">
            <label>Reason Of Refund</label>
            <textarea type="text" name="refund_reason" required class="form-control"></textarea>
        </div>`;
                modal.find('form').attr('action', `{{ route('admin.exchange.refund', $exchange->id) }}`)
                modal.find(".modal-body").html(html);
                modal.find(".modal-title").text(`Refund Exchange`);
                modal.modal('show');
            });
        })(jQuery);
    </script>
@endpush
@push('style')
    <style>
        .list-group-item {
            border: 1px solid rgba(140, 140, 140, 0.125)
        }
    </style>
@endpush

@push('style')
    <style>
        .list-group-item {
            border: 1px solid rgba(140, 140, 140, 0.125);
        }

        .blocked-user {
            border: 5px solid red !important;
        }
        .kyc-row{
            display: flex;
            gap: 20px;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        .kyc-row .kyc-value{
            border-radius: 8px;
            width: 70px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }
        .kyc-row .false{
            border: 1px solid red;
            color: red;
            padding: 5px;
        }
        .kyc-row .true{
            border: 1px solid green;
            color: green;
            padding: 5px;
        }
    </style>
@endpush
