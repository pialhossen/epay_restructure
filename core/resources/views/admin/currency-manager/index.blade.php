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
                    <form method="GET" action="{{ route('admin.manage.currency.index') }}">
                        <div class="row mb-3">
                            <div class="col-md-5">
                                <label>@lang('Currency From')</label>
                                <select name="currency_form" class="form-control">
                                    <option value="">@lang('Select Currency')</option>
                                    @foreach ($currencies as $currency)
                                        <option value="{{ $currency->id }}"
                                            {{ request('currency_form') == $currency->id ? 'selected' : '' }}>
                                            {{ $currency->name }} ({{ $currency->cur_sym }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-5">
                                <label>@lang('Currency To')</label>
                                <select name="currency_to" class="form-control">
                                    <option value="">@lang('Select Currency')</option>
                                    @foreach ($currencies as $currency)
                                        <option value="{{ $currency->id }}"
                                            {{ request('currency_to') == $currency->id ? 'selected' : '' }}>
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
                        <table class="table table--light">
                            <thead>
                                <tr>
                                    <th>@lang('Currency From')</th>
                                    <th>@lang('Currency To')</th>
                                    <th>@lang('Status')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($users as $user)
                                    <tr>
                                        <td>{{ $user->currencyFrom->name ?? 'N/A' }}
                                            ({{ $user->currencyFrom->cur_sym ?? '' }})
                                        </td>
                                        <td>{{ $user->currencyTo->name ?? 'N/A' }}
                                            ({{ $user->currencyTo->cur_sym ?? '' }})</td>
                                        <td>
                                            <label class="switch">
                                                <input type="checkbox" data-id="{{ $user->id }}" class="toggle-status"
                                                    {{ $user->status ? 'checked' : '' }}>
                                                <span class="slider round"></span>
                                            </label>
                                            <span
                                                class="ml-2 status-text">{{ $user->status ? 'Active' : 'Inactive' }}</span>
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

                    @if ($users->hasPages())
                        <div class="card-footer py-4">
                            {{ paginateLinks($users) }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Status Confirmation Modal -->
    <div class="modal fade" id="statusToggleModal" tabindex="-1" role="dialog" aria-labelledby="statusModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">@lang('Confirm Status Change')</h5>
                    <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                        <i class="las la-times"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <p>@lang('Are you sure you want to change the status?')</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn--secondary" data-bs-dismiss="modal">@lang('Cancel')</button>
                    <button type="button" class="btn btn--primary" id="confirmToggleBtn">@lang('Confirm')</button>
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
        const toggleStatusUrl = "{{ url('admin/manage-currency/toggle-status') }}";

        let pendingToggle = {
            checkbox: null,
            id: null,
            newStatus: null,
            $text: null
        };

        $(document).on('change', '.toggle-status', function(e) {
            e.preventDefault();
            const checkbox = this;
            const id = $(this).data('id');
            const newStatus = checkbox.checked ? 1 : 0;
            const $text = $(this).closest('td').find('.status-text');

            // Revert the checkbox visually
            checkbox.checked = !checkbox.checked;

            // Save state for confirmation
            pendingToggle = {
                checkbox,
                id,
                newStatus,
                $text
            };

            // Show confirmation modal
            $('#statusToggleModal').modal('show');
        });

        $('#confirmToggleBtn').on('click', function() {
            const {
                checkbox,
                id,
                newStatus,
                $text
            } = pendingToggle;

            $.ajax({
                url: toggleStatusUrl,
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    id: id,
                    status: newStatus
                },
                success: function(res) {
                    checkbox.checked = newStatus === 1;
                    $text.text(newStatus ? 'Active' : 'Inactive');
                    $('#statusToggleModal').modal('hide');
                    notify('success', 'Status updated successfully');
                },
                error: function() {
                    notify('error', 'Something went wrong');
                }
            });
        });
    </script>
@endpush
