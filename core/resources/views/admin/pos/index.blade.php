@extends('admin.layouts.app')
@push('style')
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

    <style>
        .select2-container {
            z-index: 9999 !important;
        }
        .select2-results__option--selectable{
            color: darkslategray !important;
        }
        .select2-results__option--selectable:hover{
            color: black !important;
        }
        .select2 .select2-selection{
            /* display: inline-block; */
            width: 100%;
            height: 45px;
        }
    </style>
@endpush
@section('panel')
    {{--  Search start  --}}
    <div class="row pl-2 pb-2 ml-2">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body p-0">
                    <form class="m-2" action="{{ route('admin.pos.index') }}" method="GET">
                        <div class="row pb-2">
                            <div class="col-4">
                                <label for="currency_id">Currency</label>
                                <select name="currency_id[]" id="currency_id" class="form-control select2" multiple>
                                    <option value="">Select Currency</option>
                                    @foreach($currencies as $currency)
                                        <option value="{{ $currency->id }}"
                                            {{ is_array($request->currency_id) && in_array($currency->id, $request->currency_id) ? 'selected' : '' }}>
                                            {{ $currency->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-4">
                                <label for="exchange_id">Exchange ID</label>
                                <input @if($request->exchange_id) value="{{ $request->exchange_id }}" @endif type="text" name="exchange_id" class="form-control">
                            </div>
                            <div class="col-4">
                                <label for="status">Status</label>
                                <select name="status" id="status" class="form-control">
                                    <option value="">--------</option>
                                    <option value="0" @if($request->status == 0) selected @endif>INITIAL</option>
                                    <option value="1" @if(empty($request->status) || $request->status == 1) selected @endif>APPROVED</option>
                                    <option value="2" @if($request->status == 2) selected @endif>PENDING</option>
                                    <option value="3" @if($request->status == 3) selected @endif>REFUND</option>
                                    <option value="4" @if($request->status == 4) selected @endif>HOLD</option>
                                    <option value="5" @if($request->status == 5) selected @endif>PROCESSING</option>
                                    <option value="9" @if($request->status == 9) selected @endif>CANCEL</option>
                                </select>
                            </div>
                            <div class="col-4">
                                <label for="transaction_type">Transaction Type</label>
                                <select name="transaction_type" id="transaction_type" class="form-control">
                                    <option value="">--------</option>
                                    <option value="EXCHANGE" @if($request->transaction_type == 'EXCHANGE') selected @endif>EXCHANGE</option>
                                    <option value="DEPOSIT" @if($request->transaction_type == 'DEPOSIT') selected @endif>DEPOSIT</option>
                                    <option value="WITHDRAW" @if($request->transaction_type == 'WITHDRAW') selected @endif>WITHDRAW</option>
                                </select>
                            </div>
                            <div class="col-4">
                                <label for="created_from">Created From</label>
                                <input value="{{ $request->created_from ?? now()->format('Y-m-d') }}" type="date" name="created_from" class="form-control">
                            </div>
                            <div class="col-4">
                                <label for="created_to">Created To</label>
                                <input value="{{ $request->created_to ?? now()->format('Y-m-d') }}" type="date" name="created_to" class="form-control">
                            </div>
                        </div>
                        <button type="Submit" class="btn btn-sm btn-primary">Submit to View Profit</button>
                        @if(checkSpecificPermission('Download - Pos Report'))
                        <button type="Submit" name="submit_button" class="btn btn-sm btn-success" value="DOWNLOAD">Download</button>
                        @endif
                        <a href="{{ route('admin.pos.index') }}" class="btn btn-sm btn-info">Reset</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
    {{--  Search end  --}}

    {{--  POS Show  --}}
    @if($transactions)
    <div class="row">
        <div class="col-lg-12">
            <div class="card b-radius--10 ">
                <div class="card-body p-0">
                    <div class="table-responsive--md  table-responsive">
                        <table class="table table--light style--two">
                            <thead>
                                <tr>
                                    <th>@lang('Currency')</th>
                                    <th>@lang('Previous Currency Reserved (CR) + Customer Sent (CS)')</th>
                                    <th>@lang('Previous Currency Total (CR * CSAR) + Receied Any (CR)')</th>
                                    <th>@lang('Customer Sent Avg (CR / CS = CSA)')</th>
                                    <th>@lang('Customer Received (R_CR) + Hidden charge (HC) = R_CRHC')</th>
                                    <th>@lang('Sent Any (R_CS)')</th>
                                    <th>@lang('Customer Received Avg (R_CS / R_CR = CRA)')</th>
                                    <th>@lang('Avg Profit Rate (CRA - CSA = APR)')</th>
                                    <th>@lang('Total Profit (R_CR * APR)')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($transactions as $currency => $data)
                                    <tr>
                                        <td>
                                            <span class="fw-bold" style="color: #0DB3F1;">{{ $currency }}</span>
                                        </td>
                                        <td>
                                            <span class="fw-bold">
                                                {{ $data['last_day_reserved'] }}
                                                +
                                                {{ $data['customer_sent_amount_by_this_currency'] }}
                                                =
                                                {{ $data['sent_profit'] }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="fw-bold">
                                                {{ $data['last_day_currency_total'] }}
                                                +
                                                {{ $data['customer_received_amount_by_any_currency'] }}
                                                =
                                                {{ $data['received_profit'] }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="fw-bold" style="color: #09B05C;">{{ $data['customer_avg_sent_rate'] }}</span>
                                        </td>
                                        <td>
                                            <span class="fw-bold">{{ $data['customer_received_amount_by_this_currency'] }} + {{ $data['hidden_charge_amount'] }} = {{ $data['customer_received_amount_by_this_currency'] + $data['hidden_charge_amount'] }}</span>
                                        </td>
                                        <td>
                                            <span class="fw-bold">{{ $data['customer_sent_amount_by_any_currency'] }}</span>
                                        </td>
                                        <td>
                                            <span class="fw-bold" style="color: #C15451;">{{ $data['customer_avg_received_rate'] }}</span>
                                        </td>
                                        <td>
                                            <span class="fw-bold">{{ $data['avg_profit_rate'] }}</span>
                                        </td>
                                         <td>
                                            <span class="fw-bold">{{ $data['total_profit'] }}</span>
                                        </td>
                                    </tr>
                                @endforeach
                                <tr>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td><span class="fw-bold" style="color: #4634FF;">Total: </span></td>
                                    <td><span class="fw-bold" style="color: #4634FF;">{{ $totalProfitAll }}</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
    {{--  POS show end  --}}

    {{--  Data Show  --}}
    @if($exchanges)
    <div class="row">
        <div class="col-lg-12">
            <div class="card b-radius--10 ">
                <div class="card-body p-0">
                    <div class="table-responsive--md  table-responsive">
                        <table class="table table--light style--two">
                            <thead>
                                <tr>
                                    <th>@lang('Exchange ID')</th>
                                    <th>@lang('User')</th>
                                    <th>@lang('Transaction Type')</th>
                                    <th>@lang('Send Method')</th>
                                    <th>@lang('Send Amount')</th>
                                    <th>@lang('Received Method')</th>
                                    <th>@lang('Received Amount')</th>
                                    <th>@lang('Status')</th>
                                    <th>@lang('Action')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($exchanges as $exchange)
                                    <tr>
                                        <td>
                                            <span class="fw-bold">{{ $exchange->exchange_id }}</span>
                                            <br>
                                            <small class="text-muted">{{ showDateTime($exchange->created_at) }}</small>
                                        </td>
                                        <td>
                                            <span class="d-block">{{ __(@$exchange->user->fullname) }}</span>
                                            <span>
                                                <a class="text--primary"
                                                   href="{{ route('admin.users.detail', @$exchange->user_id) }}">
                                                    <span class="text--primary">@</span>{{ __(@$exchange->user->username) }}
                                                </a>
                                            </span>
                                        </td>
                                        <td>{{ $exchange->transaction_type }}</td>
                                        <td>
                                            <span class="d-block">{{ __(@$exchange->sendCurrency->name) }}</span>
                                            <span class="text--primary">{{ __(@$exchange->sendCurrency->cur_sym) }}</span>
                                        </td>
                                        <td>
                                            <span class="d-block">
                                                {{ number_format($exchange->sending_amount, $exchange->sendCurrency->show_number_after_decimal) }}
                                                {{ __(@$exchange->sendCurrency->cur_sym) }}
                                            </span>
                                            <span>
                                                {{ number_format($exchange->sending_amount, $exchange->sendCurrency->show_number_after_decimal) }}
                                            </span>
                                            +
                                            <span class="text--danger">
                                                {{ number_format($exchange->sending_charge, $exchange->sendCurrency->show_number_after_decimal) }}
                                            </span>
                                            =
                                            <span>
                                                {{ number_format($exchange->sending_amount + $exchange->sending_charge, $exchange->sendCurrency->show_number_after_decimal) }}
                                                {{ __(@$exchange->sendCurrency->cur_sym) }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="d-block">{{ __(@$exchange->receivedCurrency->name) }}</span>
                                            <span class="text--primary">{{ __(@$exchange->receivedCurrency->cur_sym) }}</span>
                                        </td>
                                        <td>
                                            <span class="d-block">
                                                {{ number_format($exchange->receiving_amount, $exchange->receivedCurrency ? $exchange->receivedCurrency->show_number_after_decimal : 2) }}
                                                {{ __(@$exchange->receivedCurrency->cur_sym) }}
                                            </span>
                                            <span>
                                                {{ number_format($exchange->receiving_amount, $exchange->receivedCurrency ? $exchange->receivedCurrency->show_number_after_decimal : 2) }}
                                            </span>
                                            -
                                            <span class="text--danger">
                                                {{ number_format($exchange->receiving_charge, $exchange->receivedCurrency ? $exchange->receivedCurrency->show_number_after_decimal : 2) }}
                                            </span>
                                            =
                                            <span>
                                                {{ number_format($exchange->receiving_amount - $exchange->receiving_charge, $exchange->receivedCurrency ? $exchange->receivedCurrency->show_number_after_decimal : 2) }}
                                                {{ __(@$exchange->receivedCurrency->cur_sym) }}
                                            </span>
                                        </td>
                                        <td> @php echo $exchange->badgeData() @endphp </td>
                                        <td>
                                            <a href=""
                                               class="btn btn-sm btn-outline--primary">
                                                <i class="las la-desktop"></i>@lang('Details')
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="100%" class="text-muted text-center">{{ __($emptyMessage) }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                {{--  @if ($exchanges->hasPages())
                    <div class="card-footer py-4">
                        {{ paginateLinks($exchanges) }}
                    </div>
                @endif  --}}
            </div>
        </div>
    </div>
    @endif
    {{--  Data show end  --}}
@endsection

{{--  @push('breadcrumb-plugins')
    <button type="button" class="btn  btn-outline--warning h-45 exportBtn">
        <i class="las la-cloud-download-alt"></i> @lang('Export')
    </button>
@endpush  --}}

@push('script')
    <!-- Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        "use strict";

        function toggleOrderBy() {
            let checkbox = document.getElementById('orderByCheckbox');
            let orderByInput = document.querySelector('input[name="order_by"]');
            orderByInput.value = checkbox.checked ? 'asc' : 'desc';
        }

        (function($) {
            $('.exportBtn').on('click', function() {
                $('#exportModal').modal('show');
            });
        })(jQuery);

        $(document).ready(function() {
            $('#send_currency_id').select2({
                placeholder: 'Select currencies',
                allowClear: true,
                width: '100%' // Important for layout issues
            });
            $('#receive_currency_id').select2({
                placeholder: 'Select currencies',
                allowClear: true,
                width: '100%' // Important for layout issues
            });
        });
    </script>
@endpush
