@extends('admin.layouts.app')

@section('panel')
    <div class="row pl-2 pb-2 ml-2">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body p-0">
                    @php
                    $lastSegment = request()->segment(count(request()->segments()));
                    @endphp
                    <form class="m-2" action="{{ url()->full() }}" method="GET">
                        <div class="row pb-2">
                            <div class="col-1">
                                <label for="exchange_id">Items Per Page</label>
                                <input value="{{ getPaginate( isset(request()->query()['itemsPerPage'])? request()->query()['itemsPerPage']: null ) }}" type="text" name="itemsPerPage" class="form-control">
                                <button type="Submit" class="btn btn-sm btn-primary mt-2">Apply</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-lg-12">
            <div class="card b-radius--10">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="mb-0">@lang('Hidden Charges')</h6>
                        <a href="{{ route('admin.hidden.charge.create') }}" class="btn btn--primary">
                            <i class="las la-plus"></i> @lang('Add New')
                        </a>
                    </div>
                    <form method="GET" action="{{ route('admin.hidden.charge.index') }}" class="mb-3">
                        <div class="row">
                            <div class="col-md-4">
                                <label>@lang('Filter by Currency')</label>
                                <select name="currency_id" class="form-control">
                                    <option value="">@lang('All Currencies')</option>
                                    @foreach ($currencies as $currency)
                                        <option value="{{ $currency->id }}"
                                            {{ request('currency_id') == $currency->id ? 'selected' : '' }}>
                                            {{ $currency->name }} ({{ $currency->cur_sym }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn--primary w-100">@lang('Filter')</button>
                            </div>
                        </div>
                    </form>

                    <div class="table-responsive--md">
                        <table class="table table--light style--two">
                            <thead>
                                <tr>
                                    <th>@lang('Currency')</th>
                                    <th>@lang('Title')</th>
                                    <th>@lang('Description')</th>
                                    <th>@lang('Charge Percent (%)')</th>
                                    <th>@lang('Charge Fixed')</th>
                                    <th>@lang('Action')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($hiddenCharges as $item)
                                    <tr>
                                        <td>{{ @$item->currency->name ?? 'N/A' }} ({{ @$item->currency->cur_sym ?? 'N/A' }})
                                        </td>
                                        <td>{{ __($item->title) }}</td>
                                        <td>{{ __($item->description) }}</td>
                                        <td>{{ number_format($item->charge_percent, 4) }}</td>
                                        <td>{{ number_format($item->charge_fixed, 4) }}</td>
                                        <td>
                                            <a href="{{ route('admin.hidden.charge.edit', $item->id) }}"
                                                class="btn btn-sm btn-outline--primary" title="@lang('Edit')">
                                                <i class="la la-pencil"></i>
                                            </a>
                                            <a href="{{ route('admin.hidden.charge.delete', $item->id) }}"
                                                class="btn btn-sm btn-outline--danger confirmationBtn"
                                                title="@lang('Delete')">
                                                <i class="la la-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="100%" class="text-center text-muted">@lang('No data found')</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if ($hiddenCharges->hasPages())
                        <div class="card-footer py-4">
                            {{ paginateLinks($hiddenCharges) }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@push('style')
    <style>
        .switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 24px;
        }

        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 24px;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }

        input:checked+.slider {
            background-color: #28a745;
        }

        input:checked+.slider:before {
            transform: translateX(26px);
        }
    </style>
@endpush

@push('script')
    <script>
        $(document).on('change', '.status-toggle', function() {
            let id = $(this).data('id');
            let status = $(this).is(':checked') ? 1 : 0;

            $.ajax({
                url: "{{ route('admin.hidden.charge.toggle.status') }}",
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    id: id,
                    status: status
                },
                success: function(res) {
                    notify('success', 'Status updated successfully');
                },
                error: function() {
                    notify('error', 'Something went wrong');
                }
            });
        });
    </script>
@endpush
