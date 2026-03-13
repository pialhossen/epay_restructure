@php
use Illuminate\Support\Str;
@endphp
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
        .email-body{
            width: 100%;
            display: flex;
            justify-content: end;
        }
    </style>
    @php
    $imap_config = json_decode(gs('imap_config'));
    @endphp
    <div class="row pl-2 pb-2 ml-2">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body p-0">
                    @php
                        $lastSegment = request()->segment(count(request()->segments()));
                    @endphp
                    <form class="m-2" action="{{ route('admin.forwardEmails.archive_index') }}" method="GET" autocomplete="off">
                        @if(request()->query('itemsPerPage'))
                            <input type="hidden" name="itemsPerPage" value="{{ request('itemsPerPage') }}">
                        @endif
                        <div class="row pb-2">
                            <div class="col-lg-3 col-md-6 col-12 advance-search">
                                <label for="exchange_id">ID</label>
                                <input @if($request->id) value="{{ $request->id }}" @endif type="text" name="id"
                                    class="form-control" autocomplete="off">
                            </div>
                            <div class="col-lg-3 col-md-6 col-12 advance-search">
                                <label for="from">From</label>
                                <input @if($request->from) value="{{ $request->from }}" @endif type="text" name="from"
                                    class="form-control" autocomplete="off">
                            </div>
                            <div class="col-lg-3 col-md-6 col-12 advance-search">
                                <label for="subject">Subject</label>
                                <input @if($request->subject) value="{{ $request->subject }}" @endif type="text"
                                    name="subject" class="form-control" autocomplete="off">
                            </div>
                            <div class="col-lg-3 col-md-6 col-12 advance-search">
                                <label for="body">Body</label>
                                <input @if($request->body) value="{{ $request->body }}" @endif type="text" name="body"
                                    class="form-control" autocomplete="off">
                            </div>
                            <div class="col-lg-3 col-md-6 col-12" data-advance-search-url="">
                                <label for="received_from">Received From</label>
                                <input @if($request->received_from) value="{{ $request->received_from }}" @endif type="date"
                                    name="received_from" class="form-control">
                            </div>
                            <div class="col-lg-3 col-md-6 col-12">
                                <label for="received_to">Received To</label>
                                <input @if($request->received_to) value="{{ $request->received_to }}" @endif type="date"
                                    name="received_to" class="form-control">
                            </div>
                            <div class="col-lg-3 col-md-6 col-12">
                                <label for="note">Note</label>
                                <input @if($request->note) value="{{ $request->note }}" @endif type="text"
                                    name="note" class="form-control">
                            </div>
                        </div>
                        <button type="Submit" class="btn btn-sm btn-primary">Search</button>
                        <a href="{{ route('admin.forwardEmails.index') }}" class="btn btn-sm btn-info">Reset</a>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <x-item-per-page />
    {{-- search end --}}

    <div class="row">
        <div class="col-lg-12">
            <div class="card b-radius--10 ">
                <div class="card-body p-0">
                   
                    <div class="table-responsive--md  table-responsive table-container">
                        <table class="table table--light style--two data-table">
                            <thead>
                                <tr>
                                    <th>@lang('ID')</th>
                                    <th style="cursor: pointer;" onclick="toggleSort(event, 'created_at')">
                                        <div class="sortable-header">
                                            <span class="sort-indicate">
                                                <span class="up" @if(str_contains(request()->query("sort"), 'created_at'))
                                                    style="visibility: {{ request()->query("sort") == "created_at:desc" ? "visible" : "hidden" }};"
                                                @endif><i class="fa-solid fa-arrow-up"></i></span>
                                                <span class="down" @if(str_contains(request()->query("sort"), 'created_at'))
                                                    style="visibility: {{ request()->query("sort") == "created_at:asc" ? "visible" : "hidden" }};"
                                                @endif><i class="fa-solid fa-arrow-down"></i></span>
                                            </span>
                                            <span class="text">
                                                @lang('Received Date Time')
                                            </span>
                                        </div>
                                    </th>
                                    <th>@lang('Send From')</th>
                                    <th>@lang('Subject')</th>
                                    <th>@lang('Body')</th>
                                    <th>@lang('Note')</th>
                                    <th>@lang('Checked By')</th>
                                    <th>@lang('Checked At')</th>
                                    <th class="sticky-col">@lang('Action')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    use App\Models\AdminUserModel;
                                @endphp
                                @forelse($emails as $email)
                                    <tr style="background: {{ $email->is_checked? 'rgb(171 255 189)': 'white' }};">
                                        <td>
                                            {{ $email->id }}
                                        </td>
                                        <td>
                                            <span class="fw-bold">{{ $email->created_at->format('d/m/Y h:i:s A') }}</span>
                                            <br>
                                        </td>
                                        <td>
                                            {{ $email->from }}
                                        </td>
                                        <td>{{ $email->subject }}</td>
                                        <td style="white-space: nowrap;">
                                            <div style="white-space: wrap;" class="email-body">
                                                <div style="width: 400px;">
                                                    {{ Str::limit($email->body, 400, '...') }}
                                                </div>
                                            </div>
                                        </td>
                                        <td style="width: 350px;">{{ $email->note? $email->note: ($email->is_checked?"[No Note Found]":"[Not Checked Yet]") }}</td>
                                        <td style="width: 350px;">{{ $email->checked_by_admin? $email->checked_by_admin->name: "[Not Checked Yet]" }}</td>
                                        <td style="width: 350px;">{{ $email->checked_by_admin? $email->updated_at->format('d/m/Y h:i:s A'): "[Not Checked Yet]" }}</td>
                                        <td class="sticky-col" style="background: {{ $email->is_checked? 'rgb(171 255 189)': 'white' }};">

                                            @if(
                                                $email->updated_at->gt(now()->subMinutes((int)$imap_config->timeout || 10)) || 
                                                !$email->is_checked ||
                                                auth('admin')->id() == 1
                                            )
                                            @if($email->is_checked)
                                            <form action="{{ route('admin.forwardEmails.uncheck', $email->id) }}" style="display: inline" method="post">
                                                @csrf
                                                <button class="btn btn-sm btn-outline--danger" @if(!checkSpecificPermission('Update - Exchange Emails')) disabled @endif>
                                                    <i class="las la-minus"></i>@lang('Uncheck')
                                                </button>
                                            </form>
                                            @else
                                            <button data-bs-toggle="modal" data-bs-target="#addNote_{{ $email->id }}"
                                                class="btn btn-sm btn-outline--success" @if(!checkSpecificPermission('Update - Exchange Emails')) disabled @endif>
                                                <i class="las la-check"></i>@lang('Check')
                                            </button>
                                            @endif
                                            @endif

                                            @if($email->is_archive)
                                                <form action="{{ route('admin.forwardEmails.unarchive', $email->id) }}" style="display: inline" method="post">
                                                    @csrf
                                                    <button class="btn btn-sm btn-outline--danger" @if(!checkSpecificPermission('Update - Exchange Archive')) disabled @endif>
                                                        <i class="las la-minus"></i>@lang('Unarchive')
                                                    </button>
                                                </form>
                                            @else
                                                <form action="{{ route('admin.forwardEmails.archive', $email->id) }}" style="display: inline" method="post">
                                                    @csrf
                                                    <button class="btn btn-sm btn-outline--danger" @if(!checkSpecificPermission('Update - Exchange Archive')) disabled @endif>
                                                        <i class="las la-plus"></i>@lang('Archive')
                                                    </button>
                                                </form>
                                            @endif
                                            
                                            @if(checkSpecificPermission('View - Exchange Emails Details'))
                                                <a href="{{ route('admin.forwardEmails.show', $email->id) }}"
                                                    class="btn btn-sm btn-outline--primary">
                                                    <i class="las la-desktop"></i>@lang('Details')
                                                </a>
                                            @else
                                                <button class="btn btn-sm btn-outline--primary" disabled>
                                                    <i class="las la-desktop"></i>@lang('Details')
                                                </button>
                                            @endif

                                        </td>
                                        <div id="addNote_{{ $email->id }}" class="modal fade" tabindex="-1" role="dialog">
                                                <div class="modal-dialog" role="document">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title"><span class="type"></span>
                                                                <span>@lang('Add Notes')</span></h5>
                                                            <button type="button" class="close" data-bs-dismiss="modal"
                                                                aria-label="Close">
                                                                <i class="las la-times"></i>
                                                            </button>
                                                        </div>
                                                        <form action="{{ route('admin.forwardEmails.check', $email->id) }}" method="POST">
                                                            @csrf
                                                            <div class="modal-body">
                                                                <div class="form-group">
                                                                    <label>@lang('Notes')</label>
                                                                    <div class="input-group">
                                                                        <textarea type="text" rows="10" name="note"
                                                                            class="form-control"
                                                                            placeholder="@lang('You Can Enter a Note')"></textarea>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="submit"
                                                                    class="btn btn--primary h-45 w-100">@lang('Submit')</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
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
                @if ($emails->hasPages())
                    <div class="card-footer py-4">
                        {{ paginateLinks($emails) }}
                    </div>
                @endif
            </div>
        </div>
    </div>
    {{-- export modal --}}
@endsection

@push('breadcrumb-plugins')
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

        (function ($) {
            $('.exportBtn').on('click', function () {
                $('#exportModal').modal('show');
            });
            $('#select_all').click(e => {
                $('input[name="exchnage_id[]"]').prop('checked', e.target.checked);
            })
            $('#bulk_update_button').click(e => {
                e.preventDefault();
                const exchnage_type = $('#bulk_update_exchange_type').val();
                // collect only the checked checkboxes and map to values
                const ids = $('input[name="exchnage_id[]"]:checked').map(function () {
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

                $(other).find('option').each(function () {
                    let val = $(this).val();
                    if (selected.includes(val)) {
                        $(this).prop('disabled', true);
                    } else {
                        $(this).prop('disabled', false);
                    }
                });
                $(other).trigger('change.select2');
            }


            $('#send_currency_id').on('change', (e) => {
                syncSelects('#send_currency_id', '#receive_currency_id');
            })
            $('#receive_currency_id').on('change', (e) => {
                syncSelects('#receive_currency_id', '#send_currency_id');
            })
            syncSelects('#receive_currency_id', '#send_currency_id');
            syncSelects('#send_currency_id', '#receive_currency_id');
        })(jQuery);
    </script>
@endpush