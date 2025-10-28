@extends($activeTemplate . 'layouts.master')
@section('content')
    <style>
        .table-container {
            overflow-x: auto;
            position: relative;
        }

        .data-table {
            border-collapse: collapse;
        }

        .data-table th,
        .data-table td {
            white-space: nowrap;
        }

        .sticky-col {
            position: sticky !important;
            right: 0;
            z-index: 2; /* higher than other cells */
        }
        .data-table th {
            position: sticky;
            top: 0;
            z-index: 3;
        }
    </style>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-12">
                <div class="card custom--card">
                    @if (!$exchanges->isEmpty())
                        <div class="card-body p-0 table-container">
                            <div class="card-header p-1 d-flex justify-content-end">
                                <a href="{{ route('user.exchange.download_report', $scope) }}" class="btn btn-sm btn-success">Download</a>
                            </div>
                            @php
                                $lastSegment = request()->segment(count(request()->segments()));
                            @endphp
                            <form class="m-2" action="{{ url()->current() }}" method="GET">
                                @if(request()->query('itemsPerPage'))
                                    <input type="hidden" name="itemsPerPage" value="{{ request('itemsPerPage') }}">
                                @endif
                                <div class="row pb-2">
                                    <div class="col-lg-3 col-md-6 col-12">
                                        <label for="exchange_id">Exchange ID</label>
                                        <input @if($request->exchange_id) value="{{ $request->exchange_id }}" @endif type="text" name="exchange_id" class="form-control">
                                    </div>
                                    <div class="col-lg-3 col-md-6 col-12">
                                        <label for="transaction_type">Transaction Type</label>
                                        <select name="transaction_type[]" id="transaction_type" class="form-control select2" multiple="multiple">
                                            <option value="EXCHANGE" @if($request->transaction_type == 'EXCHANGE') selected @endif>EXCHANGE</option>
                                            <option value="DEPOSIT" @if($request->transaction_type == 'DEPOSIT') selected @endif>DEPOSIT</option>
                                            <option value="WITHDRAW" @if($request->transaction_type == 'WITHDRAW') selected @endif>WITHDRAW</option>
                                        </select>
                                    </div>
                                    <div class="col-lg-3 col-md-6 col-12">
                                        @php
                                            $send_old = isset(request()->query()['send_currency_id'])? request()->query()['send_currency_id']: [];
                                        @endphp
                                        <label for="send_currency_id">Send Method</label>
                                        <select name="send_currency_id[]" id="send_currency_id" class="form-control select2" multiple="multiple">
                                            @foreach($currencies as $currency)
                                            <option value="{{ $currency->id }}" @selected(in_array($currency->id,$send_old ))>{{ $currency->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-lg-3 col-md-6 col-12">
                                        @php
                                            $receive_old = isset(request()->query()['receive_currency_id'])? request()->query()['receive_currency_id']: [];
                                        @endphp
                                        <label for="receive_currency_id">Receive Method</label>
                                        <select name="receive_currency_id[]" id="receive_currency_id" class="form-control select2" multiple="multiple">
                                            @foreach($currencies as $currency)
                                            <option value="{{ $currency->id }}" @selected(in_array($currency->id,$receive_old ))>{{ $currency->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-lg-3 col-md-6 col-12">
                                        <label for="created_from">Created From</label>
                                        <input @if($request->created_from) value="{{ $request->created_from }}" @endif type="date" name="created_from" class="form-control">
                                    </div>
                                    <div class="col-lg-3 col-md-6 col-12">
                                        <label for="created_to">Created To</label>
                                        <input @if($request->created_to) value="{{ $request->created_to }}" @endif type="date" name="created_to" class="form-control">
                                    </div>
                                </div>
                                <button type="Submit" class="btn btn-sm btn-primary">Search</button>
                                <a href="{{ url()->current() }}" class="btn btn-sm btn-info">Reset</a>
                            </form>
                            <x-item-per-page/>
                            <table class="table custom--table table-responsive--md data-table">
                                <thead>
                                    <tr>
                                        <th>@lang('Exchange ID')</th>
                                        <th>@lang('Send')</th>
                                        <th>@lang('Received')</th>
                                        <th style="cursor: pointer;" onclick="toggleSort(event, 'receiving_amount')">
                                            <div class="sortable-header">
                                            <span class="sort-indicate"> 
                                                <span class="up" @if(str_contains(request()->query("sort"),'receiving_amount')) style="visibility: {{ request()->query("sort") == "receiving_amount:desc"? "visible": "hidden" }};" @endif><i class="fa-solid fa-arrow-up"></i></span> 
                                                <span class="down" @if(str_contains(request()->query("sort"),'receiving_amount')) style="visibility: {{ request()->query("sort") == "receiving_amount:asc"? "visible": "hidden" }};" @endif><i class="fa-solid fa-arrow-down"></i></span> 
                                            </span> 
                                                <span class="text">
                                                    @lang('Amount')
                                                </span>
                                            </div>
                                        </th>
                                        <th style="cursor: pointer;" onclick="toggleSort(event, 'created_at')">
                                            <div class="sortable-header">
                                            <span class="sort-indicate"> 
                                                <span class="up" @if(str_contains(request()->query("sort"),'created_at')) style="visibility: {{ request()->query("sort") == "created_at:desc"? "visible": "hidden" }};" @endif><i class="fa-solid fa-arrow-up"></i></span> 
                                                <span class="down" @if(str_contains(request()->query("sort"),'created_at')) style="visibility: {{ request()->query("sort") == "created_at:asc"? "visible": "hidden" }};" @endif><i class="fa-solid fa-arrow-down"></i></span> 
                                            </span> 
                                                <span class="text">
                                                    @lang('Date')
                                                </span>
                                            </div>
                                        </th>
                                        <th>@lang('Status')</th>
                                        <th class="sticky-col">@lang('Action')</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($exchanges as $exchange)
                                        <tr>
                                            <td>{{ $exchange->exchange_id }}</td>
                                            <td>
                                                <div class="thumb">
                                                    <img class="table-currency-img" src="{{ getImage(getFilePath('currency') . '/' . @$exchange->sendCurrency->image, getFileSize('currency')) }}">
                                                </div>
                                                {{ __(@$exchange->sendCurrency->name) }}
                                            </td>
                                            <td>
                                                <div class="thumb">
                                                    <img src="{{ getImage(getFilePath('currency') . '/' . @$exchange->receivedCurrency->image, getFileSize('currency')) }}" class="table-currency-img">
                                                </div>
                                                {{ __(@$exchange->receivedCurrency->name) }}
                                            </td>
                                            <td>
                                                {{ number_format($exchange->sending_amount + $exchange->sending_charge, $exchange->sendCurrency->show_number_after_decimal) }}
                                                {{ __(@$exchange->sendCurrency->cur_sym) }}
                                                <i class="las la-arrow-right text--base"></i>
                                                {{ number_format($exchange->receiving_amount - $exchange->receiving_charge, $exchange->receivedCurrency->show_number_after_decimal) }}
                                                {{ __(@$exchange->receivedCurrency->cur_sym) }}
                                            </td>
                                            <td>
                                                <div class="d-flex flex-column justify-content-end">
                                                    <span>{{ showDateTime(@$exchange->created_at) }}</span>
                                                    <span class="text--base">{{ diffForHumans(@$exchange->created_at) }}</span>
                                                </div>
                                            </td>
                                            <td>{!! $exchange->badgeData() !!}</td>
                                            <td class="sticky-col" style="background-color: white;">
                                                <a href="{{ route('user.exchange.details', $exchange->exchange_id) }}" class="btn btn--base-outline btn-sm" data-reason="{{ $exchange->cancle_reason }}">
                                                    <i class="fa fa-desktop"></i> @lang('Details')
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        @include($activeTemplate . 'partials.empty', [
                            'message' => 'No exchange found',
                        ])
                    @endif
                </div>
                @if ($exchanges->hasPages())
                    <div class="py-3 custom__paginate">
                        {{ paginateLinks($exchanges) }}
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
