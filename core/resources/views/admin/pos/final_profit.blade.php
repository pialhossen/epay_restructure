@extends('admin.layouts.app')
@section('panel')
{{--  Search start  --}}
    <div class="row pl-2 pb-2 ml-2">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body p-0">
                    <form class="m-2" action="{{ route('admin.pos.final_profit') }}" method="GET">
                        <div class="row pb-2">
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
                                <label for="created_from">Select Currency</label>
                                <select name="currency_id[]" id="currency_id" class="form-control select2" multiple>
                                    <option value="">Select Currency</option>
                                    @foreach($currencies_all as $currency)
                                        <option value="{{ $currency->id }}"
                                            {{ is_array($request->currency_id) && in_array($currency->id, $request->currency_id) ? 'selected' : '' }}>
                                            {{ $currency->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            {{--  <div class="col-4">
                                <label for="created_to">Created To</label>
                                <input value="{{ $request->created_to ?? now()->format('Y-m-d') }}" type="date" name="created_to" class="form-control">
                            </div>  --}}
                        </div>
                        <button type="Submit" name="submit_button" class="btn btn-sm btn-primary" value="Report_VIEW">Submit to View Final Profie</button>
                        @if(checkSpecificPermission('Download - Pos Report'))
                        <button type="Submit" name="submit_button" class="btn btn-sm btn-success" value="DOWNLOAD">Download</button>
                        @endif
                        <a href="{{ route('admin.pos.final_profit') }}" class="btn btn-sm btn-info">Reset</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
    {{--  search end  --}}

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
                                    <th>@lang('CS Sent Avg Rate (CSAR)')</th>
                                    <th>@lang('Currency Reserved (CR)')</th>
                                    <th>@lang('Currency Total (CR * CSAR)')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($transactions as $currency => $data)
                                    <tr>
                                        <td>
                                            <span class="fw-bold" style="color: #0DB3F1;">{{ $currency }}</span>
                                        </td>
                                        <td>
                                            <span class="fw-bold" style="color: #C15451;">{{ isset($data['buy_at'])? $data['buy_at'] : $data['customer_avg_sent_rate'] }}</span>
                                        </td>
                                        <td>
                                            <span class="fw-bold">{{ $data['currency_reserved'] }}</span>
                                        </td>
                                        <td>
                                            <span class="fw-bold">{{ $data['currency_total'] }}</span>
                                        </td>
                                    </tr>
                                @endforeach
                                <tr>
                                    <td></td>
                                    <td></td>
                                    <td><span class="fw-bold" style="color: #C15451;">Currency Total: </span></td>
                                    <td><span class="fw-bold" style="color: #C15451;">{{ $currencyProfit }}</span></td>
                                </tr>
                                <tr>
                                    <td></td>
                                    <td></td>
                                    <td><span class="fw-bold" style="color: #09B05C;">Total User Balance: </span></td>
                                    <td><span class="fw-bold" style="color: #09B05C;">-{{ $totalUserBalance }}</span></td>
                                </tr>
                                <tr>
                                    <td></td>
                                    <td></td>
                                    <td><span class="fw-bold" style="color: #4634FF;">Final Profit: </span></td>
                                    <td><span class="fw-bold" style="color: #4634FF;">{{ $totalProfit }}</span></td>
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
@endsection

{{--  @push('breadcrumb-plugins')
    <button type="button" class="btn  btn-outline--warning h-45 exportBtn">
        <i class="las la-cloud-download-alt"></i> @lang('Export')
    </button>
@endpush  --}}

@push('script')
    <script>
        "use strict";

        function toggleOrderBy() {
            let checkbox = document.getElementById('orderByCheckbox');
            let orderByInput = document.querySelector('input[name="order_by"]');
            orderByInput.value = checkbox.checked ? 'asc' : 'desc';
        }
    </script>
@endpush
