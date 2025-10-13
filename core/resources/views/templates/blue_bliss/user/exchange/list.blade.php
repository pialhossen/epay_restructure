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
            position: sticky;
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
                                        <th>@lang('Action')</th>
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
                                            <td class="sticky-col">
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
