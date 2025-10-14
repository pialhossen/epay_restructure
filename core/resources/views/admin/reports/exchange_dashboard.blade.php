@extends('admin.layouts.app')

@section('panel')
    <div class="row">
        <div class="col-lg-12">
            <div class="show-filter mb-3 d-flex justify-content-between align-items-center">
                <button type="button" class="btn btn-outline--primary showFilterBtn btn-sm">
                    <i class="las la-filter"></i> @lang('Filter')
                </button>

                {{-- <a href="{{ url()->current() }}?{{ http_build_query(request()->except('page') + ['export' => 1]) }}"
                    class="btn btn-sm btn-success">
                    <i class="fas fa-file-export"></i> @lang('Export Excel')
                </a> --}}
            </div>

            <div class="card responsive-filter-card mb-4">
                <div class="card-body">
                    <form method="GET">
                        <div class="d-flex flex-wrap gap-4">
                            <div class="flex-grow-1">
                                <label>@lang('Date')</label>
                                <input name="date" type="search"
                                    class="datepicker-here form-control bg--white pe-2 date-range"
                                    placeholder="@lang('Start Date - End Date')" autocomplete="off" value="{{ request()->date }}">
                            </div>

                            <div class="flex-grow-1">
                                <label>@lang('Currency')</label>
                                <select name="currency_id" class="form-control">
                                    @foreach ($currencies as $currency)
                                        <option value="{{ $currency->id }}"
                                            {{ $currencyId == $currency->id ? 'selected' : '' }}>
                                            {{ $currency->name }} ({{ $currency->cur_sym }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="flex-grow-1 align-self-end">
                                <button class="btn btn--primary w-100 h-45">
                                    <i class="fas fa-filter"></i> @lang('Filter')
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive--sm table-responsive">
                        <table class="table table--light style--two">
                            <thead>
                                <tr>
                                    <th>@lang('User')</th>
                                    <th>@lang('Email')</th>
                                    <th>@lang('Mobile')</th>
                                    <th>@lang('Currency')</th>
                                    <th>@lang('Sending Amount')</th>
                                    <th>@lang('Date')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($exchanges as $exchange)
                                    <tr>
                                        <td>
                                            <span class="fw-bold">{{ $exchange->user->fullname }}</span><br>
                                            <span class="small"><a
                                                    href="{{ appendQuery('search', $exchange->user->username) }}">@
                                                    {{ $exchange->user->username }}</a></span>
                                        </td>
                                        <td>{{ $exchange->user->email }}</td>
                                        <td>{{ $exchange->user->mobile }}</td>
                                        <td>{{ $exchange->sendCurrency->name }} {{ $exchange->sendCurrency->cur_sym }}
                                        </td>
                                        <td>{{ showAmount($exchange->sending_amount) }}</td>
                                        <td>{{ $exchange->created_at->format('d/m/y h:i:s A') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="100%" class="text-center text-muted">@lang('No exchange data found')</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                @if ($exchanges->hasPages())
                    <div class="card-footer py-4">
                        {{ paginateLinks($exchanges) }}
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

@push('style-lib')
    <link rel="stylesheet" href="{{ asset('assets/admin/css/daterangepicker.css') }}">
@endpush

@push('script-lib')
    <script src="{{ asset('assets/admin/js/moment.min.js') }}"></script>
    <script src="{{ asset('assets/admin/js/daterangepicker.min.js') }}"></script>
@endpush

@push('script')
    <script>
        (function($) {
            "use strict";

            const datePicker = $('.date-range').daterangepicker({
                autoUpdateInput: false,
                locale: {
                    cancelLabel: 'Clear'
                },
                showDropdowns: true,
                ranges: {
                    'Today': [moment(), moment()],
                    'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                    'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                    'Last 15 Days': [moment().subtract(14, 'days'), moment()],
                    'Last 30 Days': [moment().subtract(30, 'days'), moment()],
                    'This Month': [moment().startOf('month'), moment().endOf('month')],
                    'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month')
                        .endOf('month')
                    ],
                    'This Year': [moment().startOf('year'), moment().endOf('year')],
                },
                maxDate: moment()
            });

            $('.date-range').on('apply.daterangepicker', function(ev, picker) {
                $(this).val(picker.startDate.format('MMMM DD, YYYY') + ' - ' + picker.endDate.format(
                    'MMMM DD, YYYY'));
            });

            if ($('.date-range').val()) {
                let dateRange = $('.date-range').val().split(' - ');
                $('.date-range').data('daterangepicker').setStartDate(new Date(dateRange[0]));
                $('.date-range').data('daterangepicker').setEndDate(new Date(dateRange[1]));
            }
        })(jQuery);
    </script>
@endpush
