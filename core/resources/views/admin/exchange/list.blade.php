@extends('admin.layouts.app')
@section('panel')
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
        z-index: 2;
    }
    .data-table th {
        position: sticky;
        top: 0;
        z-index: 3;
    }
</style>
    <div class="row pl-2 pb-2 ml-2">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body p-0">
                    @php
                    $lastSegment = request()->segment(count(request()->segments()));
                    @endphp
                    <form class="m-2" action="{{ route('admin.exchange.list', $lastSegment) }}" method="GET">
                        @if(request()->query('itemsPerPage'))
                            <input type="hidden" name="itemsPerPage" value="{{ request('itemsPerPage') }}">
                        @endif
                        <div class="row pb-2">
                            <div class="col-lg-3 col-md-6 col-12">
                                <label for="exchange_id">Exchange ID</label>
                                <input @if($request->exchange_id) value="{{ $request->exchange_id }}" @endif type="text" name="exchange_id" class="form-control">
                            </div>
                            <div class="col-lg-3 col-md-6 col-12">
                                <label for="email">Email / Username</label>
                                <input @if($request->email) value="{{ $request->email }}" @endif type="text" name="email" class="form-control">
                            </div>
                            <div class="col-lg-3 col-md-6 col-12">
                                <label for="transaction_type">Transaction Type</label>
                                <select name="transaction_type[]" id="transaction_type" class="form-control select2" multiple="multiple">
                                    <option value="EXCHANGE" @if(is_array($request->transaction_type) && in_array('EXCHANGE',$request->transaction_type)) selected @endif>EXCHANGE</option>
                                    <option value="DEPOSIT" @if(is_array($request->transaction_type) && in_array('DEPOSIT',$request->transaction_type)) selected @endif>DEPOSIT</option>
                                    <option value="WITHDRAW" @if(is_array($request->transaction_type) && in_array('WITHDRAW',$request->transaction_type)) selected @endif>WITHDRAW</option>
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
                        <a href="{{ route('admin.exchange.list', $lastSegment) }}" class="btn btn-sm btn-info">Reset</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <x-item-per-page/>
    {{--  search end  --}}

    <div class="row">
        <div class="col-lg-12">
            <div class="card b-radius--10 ">
                <div class="card-body p-0">
                    <div class="table-responsive--md  table-responsive table-container">
                        <table class="table table--light style--two data-table">
                            <thead>
                                <tr>
                                    @if(auth()->id() == 1) <th><input type="checkbox" name="" id="select_all" style="width: 25px; height: 25px; cursor: pointer;"></th>@endif
                                    <th>@lang('Exchange ID')</th>
                                    <th>@lang('User')</th>
                                    <th>@lang('Transaction Type')</th>
                                    <th>@lang('Send Method')</th>
                                    <th style="cursor: pointer;" onclick="toggleSort(event, 'sending_amount')"> 
                                        <div class="sortable-header">
                                            <span class="sort-indicate"> 
                                                <span class="up" @if(str_contains(request()->query("sort"),'sending_amount')) style="visibility: {{ request()->query("sort") == "sending_amount:desc"? "visible": "hidden" }};" @endif><i class="fa-solid fa-arrow-up"></i></span> 
                                                <span class="down" @if(str_contains(request()->query("sort"),'sending_amount')) style="visibility: {{ request()->query("sort") == "sending_amount:asc"? "visible": "hidden" }};" @endif><i class="fa-solid fa-arrow-down"></i></span> 
                                            </span> 
                                            <span class="text">
                                                @lang('Send Amount')
                                            </span>
                                        </div>
                                    </th>
                                    <th>@lang('Received Method')</th>
                                    <th style="cursor: pointer;" onclick="toggleSort(event, 'receiving_amount')">
                                        <div class="sortable-header">
                                            <span class="sort-indicate"> 
                                                <span class="up" @if(str_contains(request()->query("sort"),'receiving_amount')) style="visibility: {{ request()->query("sort") == "receiving_amount:desc"? "visible": "hidden" }};" @endif><i class="fa-solid fa-arrow-up"></i></span> 
                                                <span class="down" @if(str_contains(request()->query("sort"),'receiving_amount')) style="visibility: {{ request()->query("sort") == "receiving_amount:asc"? "visible": "hidden" }};" @endif><i class="fa-solid fa-arrow-down"></i></span> 
                                            </span> 
                                            <span class="text">
                                                @lang('Received Amount')
                                            </span>
                                        </div>
                                    </th>
                                    <th>@lang('Status')</th>
                                    <th >@lang('Updated By')</th>
                                    <th >@lang('Placed By')</th>
                                    <th style="cursor: pointer;" onclick="toggleSort(event, 'created_at')">
                                        <div class="sortable-header">
                                            <span class="sort-indicate"> 
                                                <span class="up" @if(str_contains(request()->query("sort"),'created_at')) style="visibility: {{ request()->query("sort") == "created_at:desc"? "visible": "hidden" }};" @endif><i class="fa-solid fa-arrow-up"></i></span> 
                                                <span class="down" @if(str_contains(request()->query("sort"),'created_at')) style="visibility: {{ request()->query("sort") == "created_at:asc"? "visible": "hidden" }};" @endif><i class="fa-solid fa-arrow-down"></i></span> 
                                            </span> 
                                            <span class="text">
                                                @lang('Placed At')
                                            </span>
                                        </div>                                  
                                    </th>
                                    <th style="cursor: pointer;" onclick="toggleSort(event, 'updated_at')">
                                        <div class="sortable-header">
                                            <span class="sort-indicate"> 
                                                <span class="up" @if(str_contains(request()->query("sort"),'updated_at')) style="visibility: {{ request()->query("sort") == "updated_at:desc"? "visible": "hidden" }};" @endif><i class="fa-solid fa-arrow-up"></i></span> 
                                                <span class="down" @if(str_contains(request()->query("sort"),'updated_at')) style="visibility: {{ request()->query("sort") == "updated_at:asc"? "visible": "hidden" }};" @endif><i class="fa-solid fa-arrow-down"></i></span> 
                                            </span>
                                            <span class="text">
                                                @lang('Updated At')
                                            </span>
                                        </div>
                                    </th>
                                    <th class="sticky-col">@lang('Action')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    use App\Models\AdminUserModel;
                                @endphp
                                @forelse($exchanges as $exchange)
                                    <tr>
                                        @if(auth()->id() == 1)
                                        <td>
                                            <input type="checkbox" name="exchnage_id[]" id="" style="width: 25px; height: 25px; cursor: pointer;" value="{{ $exchange->id }}">
                                        </td>
                                        @endif
                                        <td>
                                            <span class="fw-bold">{{ $exchange->exchange_id }}</span>
                                            <br>
                                            <small class="text-muted">{{ $exchange->created_at->format('d/m/y h:i:s A') }}</small>
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
                                            {{ $exchange->sending_charge >= 0? '+' : '-' }}
                                            <span class="text--danger">
                                                {{ number_format(abs($exchange->sending_charge), $exchange->sendCurrency->show_number_after_decimal) }}
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
                                            {{ $exchange->receiving_charge < 0? '+' : '-' }}
                                            <span class="text--danger">
                                                {{ number_format(abs($exchange->receiving_charge), $exchange->receivedCurrency ? $exchange->receivedCurrency->show_number_after_decimal : 2) }}
                                            </span>
                                            =
                                            <span>
                                                {{ number_format($exchange->receiving_amount - $exchange->receiving_charge, $exchange->receivedCurrency ? $exchange->receivedCurrency->show_number_after_decimal : 2) }}
                                                {{ __(@$exchange->receivedCurrency->cur_sym) }}
                                            </span>
                                        </td>
                                        <td> @php echo $exchange->badgeData() @endphp </td>
                                        <td> {{ @$exchange->updatedBy->name }} </td>
                                        <td> {{ isset($exchange->orderPlaceAdmin->name)? $exchange->orderPlaceAdmin->name: "User"}} </td>
                                        <td> {{ $exchange->created_at->format('d/m/y h:i:s A') }} </td>
                                        <td> {{ $exchange->updated_at->format('d/m/y h:i:s A') }} </td>
                                        <td class="sticky-col" style="background: white;">
                                            @if(checkSpecificPermission('View - Exchange'))
                                            <a href="{{ route('admin.exchange.details', $exchange->id) }}"
                                               class="btn btn-sm btn-outline--primary">
                                                <i class="las la-desktop"></i>@lang('Details')
                                            </a>
                                            @else
                                            <button class="btn btn-sm btn-outline--primary" disabled>
                                                <i class="las la-desktop"></i>@lang('Details')
                                            </button>
                                            @endif
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
                @if(auth()->id() == 1)
                <div class="card-footer py-4" style="display: flex; place-items: center; gap: 5px;">
                    <select name="" id="bulk_update_exchange_type">
                        <option value=1>Approve</option>
                        <option value=2>Pending</option>
                        <option value=3>Refund</option>
                        <option value=4>Hold</option>
                        <option value=5>Proccessing</option>
                        <option value=9>Cancel</option>
                    </select>
                    <button class="btn btn-sm btn-primary" style="height: 36px;" id="bulk_update_button">Bulk Update</button>
                </div>
                @endif
                @if ($exchanges->hasPages())
                    <div class="card-footer py-4">
                        {{ paginateLinks($exchanges) }}
                    </div>
                @endif
            </div>
        </div>
    </div>
    {{-- export modal --}}
    <div class="modal fade" id="exportModal">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">@lang('Export Filter')</h4>
                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                        <i class="la la-close" aria-hidden="true"></i>
                    </button>
                </div>
                <form method="post" action="{{ route('admin.exchange.export') }}" id="exportForm">
                    @csrf
                    @foreach(request()->all() as $key => $value)
                        @if(is_array($value))
                            @foreach($value as $subKey => $subValue)
                                <input type="hidden" name="{{ $key }}[{{ $subKey }}]" value="{{ $subValue }}">
                            @endforeach
                        @else
                            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                        @endif
                    @endforeach
                    <div class="modal-body">

                        <!-- Standard Export Columns -->
                        <div class="form-group">
                            <label class="fw-bold">@lang('Export Columns')</label>
                            <div class="row">
                                <div class="col-lg-6 mb-3">
                                    <label>@lang('Exchange ID')</label>
                                    <input type="checkbox" name="columns[]" value="exchange_id" checked data-width="100%"
                                           data-size="large" data-onstyle="-success" data-offstyle="-danger"
                                           data-bs-toggle="toggle" data-height="50" data-on="@lang('Yes')"
                                           data-off="@lang('No')">
                                </div>
                                <div class="col-lg-6 mb-3">
                                    <label>@lang('User')</label>
                                    <input type="checkbox" name="columns[]" value="user_id" checked data-width="100%"
                                           data-size="large" data-onstyle="-success" data-offstyle="-danger"
                                           data-bs-toggle="toggle" data-height="50" data-on="@lang('Yes')"
                                           data-off="@lang('No')">
                                </div>
                                <div class="col-lg-6 mb-3">
                                    <label>@lang('Transaction Type')</label>
                                    <input type="checkbox" name="columns[]" value="transaction_type" checked
                                           data-width="100%" data-size="large" data-onstyle="-success" data-offstyle="-danger"
                                           data-bs-toggle="toggle" data-height="50" data-on="@lang('Yes')"
                                           data-off="@lang('No')">
                                </div>
                                <div class="col-lg-6 mb-3">
                                    <label>@lang('Received Method')</label>
                                    <input type="checkbox" name="columns[]" value="receive_currency_id" checked
                                           data-width="100%" data-size="large" data-onstyle="-success" data-offstyle="-danger"
                                           data-bs-toggle="toggle" data-height="50" data-on="@lang('Yes')"
                                           data-off="@lang('No')">
                                </div>
                                <div class="col-lg-6 mb-3">
                                    <label>@lang('Received Amount')</label>
                                    <input type="checkbox" name="columns[]" value="receiving_amount" checked
                                           data-width="100%" data-size="large" data-onstyle="-success" data-offstyle="-danger"
                                           data-bs-toggle="toggle" data-height="50" data-on="@lang('Yes')"
                                           data-off="@lang('No')">
                                </div>
                                <div class="col-lg-6 mb-3">
                                    <label>@lang('Send Method')</label>
                                    <input type="checkbox" name="columns[]" value="send_currency_id" checked
                                           data-width="100%" data-size="large" data-onstyle="-success" data-offstyle="-danger"
                                           data-bs-toggle="toggle" data-height="50" data-on="@lang('Yes')"
                                           data-off="@lang('No')">
                                </div>
                                <div class="col-lg-6 mb-3">
                                    <label>@lang('Send Amount')</label>
                                    <input type="checkbox" name="columns[]" value="sending_amount" checked
                                           data-width="100%" data-size="large" data-onstyle="-success"
                                           data-offstyle="-danger" data-bs-toggle="toggle" data-height="50"
                                           data-on="@lang('Yes')" data-off="@lang('No')">
                                </div>
                                <div class="col-lg-6 mb-3">
                                    <label>@lang('Status')</label>
                                    <input type="checkbox" name="columns[]" value="status" checked data-width="100%"
                                           data-size="large" data-onstyle="-success" data-offstyle="-danger"
                                           data-bs-toggle="toggle" data-height="50" data-on="@lang('Yes')"
                                           data-off="@lang('No')">
                                </div>
                                <div class="col-lg-6 mb-3">
                                    <label>@lang('Updated By')</label>
                                    <input type="checkbox" name="columns[]" value="updated_by" checked data-width="100%"
                                           data-size="large" data-onstyle="-success" data-offstyle="-danger"
                                           data-bs-toggle="toggle" data-height="50" data-on="@lang('Yes')"
                                           data-off="@lang('No')">
                                </div>
                                <div class="col-lg-6 mb-3">
                                    <label>@lang('Placed At')</label>
                                    <input type="checkbox" name="columns[]" value="placed_at" checked data-width="100%"
                                           data-size="large" data-onstyle="-success" data-offstyle="-danger"
                                           data-bs-toggle="toggle" data-height="50" data-on="@lang('Yes')"
                                           data-off="@lang('No')">
                                </div>
                                <div class="col-lg-6 mb-3">
                                    <label>@lang('Updated At')</label>
                                    <input type="checkbox" name="columns[]" value="updated_at" checked data-width="100%"
                                           data-size="large" data-onstyle="-success" data-offstyle="-danger"
                                           data-bs-toggle="toggle" data-height="50" data-on="@lang('Yes')"
                                           data-off="@lang('No')">
                                </div>
                            </div>
                        </div>

                        <!-- Order By -->
                        <div class="form-group">
                            <label class="fw-bold">@lang('Order By')</label>
                            <input type="hidden" name="order_by" value="desc"> <!-- Default value -->
                            <input type="checkbox" id="orderByCheckbox" data-width="100%" data-size="large"
                                   data-onstyle="-success" data-offstyle="-danger" data-bs-toggle="toggle" data-height="50"
                                   data-on="@lang('ASC')" data-off="@lang('DESC')" onchange="toggleOrderBy()">
                        </div>

                        <!-- Export Item Selection -->
                        <div class="form-group">
                            <label class="fw-bold">@lang('Export Item')</label>
                            <select class="form-control form-control-lg" name="export_item">
                                <option value="10">10</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                                <option value="500">500</option>
                                <option value="1000">1000</option>
                                <option value="{{ $exchanges->total() }}">{{ $exchanges->total() }} @lang('Exchanges')</option>
                            </select>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <input type="hidden" name="scope" value="{{ $scope }}">
                        <button type="submit" class="btn btn--primary w-100 h-45">@lang('Export')</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('breadcrumb-plugins')
    {{--  <x-search-form placeholder="Exchange ID, username" dateSearch='yes' />  --}}
    <button type="button" class="btn  btn-outline--warning h-45 exportBtn">
        <i class="las la-cloud-download-alt"></i> @lang('Export')
    </button>
@endpush

@push('script')
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
            $('#select_all').click(e => {
                $('input[name="exchnage_id[]"]').prop('checked', e.target.checked);
            })
            $('#bulk_update_button').click(e => {
                e.preventDefault();
                const exchnage_type = $('#bulk_update_exchange_type').val();
                // collect only the checked checkboxes and map to values
                const ids = $('input[name="exchnage_id[]"]:checked').map(function() {
                    return $(this).val();
                }).get();

                if (!ids.length) {
                    alert('Please select at least one exchange to update.');
                    return;
                }

                const form = document.createElement('form');
                // set your bulk update endpoint here or leave empty and submit via AJAX
                form.action = "{{ route('admin.exchange.bulk.update') }}";
                form.method = 'POST';
                form.style.display = 'none';

                // CSRF token (Blade will render the token server-side)
                const csrfInput = document.createElement('input');
                csrfInput.type = 'hidden';
                csrfInput.name = '_token';
                csrfInput.value = '{{ csrf_token() }}';

                // Exchange action/type
                const exchnage_type_input = document.createElement('input');
                exchnage_type_input.type = 'hidden';
                exchnage_type_input.name = 'status';
                exchnage_type_input.value = exchnage_type;

                // Add one hidden input per selected id: ids[]
                ids.forEach(id => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'ids[]';
                    input.value = id;
                    form.appendChild(input);
                });

                // Append CSRF and action inputs then attach form to DOM (not submitted automatically)
                form.appendChild(csrfInput);
                form.appendChild(exchnage_type_input);
                document.body.appendChild(form);
                form.submit();
            })


            function syncSelects(changed, other) {
                let selected = $(changed).val() || [];

                $(other).find('option').each(function() {
                    let val = $(this).val();
                    if (selected.includes(val)) {
                        $(this).prop('disabled', true);
                    } else {
                        $(this).prop('disabled', false);
                    }
                });
                $(other).trigger('change.select2');
            }


            $('#send_currency_id').on('change',(e) => {
                syncSelects('#send_currency_id', '#receive_currency_id');
            })
            $('#receive_currency_id').on('change',(e) => {
                syncSelects('#receive_currency_id', '#send_currency_id');
            })
            syncSelects('#receive_currency_id', '#send_currency_id');
            syncSelects('#send_currency_id', '#receive_currency_id');
        })(jQuery);
    </script>
@endpush
