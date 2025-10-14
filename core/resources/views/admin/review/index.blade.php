@extends('admin.layouts.app')

@php
    $site_path = '/'.APP_PUBLIC_FOLDER;
@endphp

@section('panel')
    <div class="row">
        <div class="col-lg-12">
            <div class="card b-radius--10">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="mb-0">@lang('Customer Reviews')</h6>
                    </div>

                    <div class="table-responsive--md">
                        <table class="table table--light style--two">
                            <thead>
                                <tr>
                                    <th>@lang('ID')</th>
                                    <th>@lang('Name')</th>
                                    <th>@lang('Email')</th>
                                    <th>@lang('Rating')</th>
                                    <th>@lang('Comment')</th>
                                    <th>@lang('Status')</th>
                                    <th>@lang('Created At')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($reviews as $key => $review)
                                    <tr>
                                        <td>{{ ++$key }}</td>
                                        <td>{{ __($review->name) }}</td>
                                        <td>{{ __($review->email) }}</td>
                                        <td>{{ $review->rating }}</td>
                                        <td>{{ \Illuminate\Support\Str::limit($review->content, 30) }}</td>
                                        <td>
                                            @if ($review->status)
                                                <span id="status-{{ $review->id }}" class="badge badge--success status-toggle" data-id="{{ $review->id }}" data-status="{{ $review->status }}">@lang('Active')</span>
                                            @else
                                                <span id="status-{{ $review->id }}" class="badge badge--danger status-toggle" data-id="{{ $review->id }}" data-status="{{ $review->status }}">@lang('Inactive')</span>
                                            @endif
                                        </td>

                                        <td>{{ $review->created_at ? $review->created_at->format('d/m/y h:i:s A'): '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="100%" class="text-center text-muted">@lang('No data found')</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if ($reviews->hasPages())
                        <div class="card-footer py-4">
                            {{ paginateLinks($reviews) }}
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
    <!-- In your Blade file or layout (only once) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootbox.js/5.5.2/bootbox.min.js"></script>
    <script>
        $(document).on('click', '.status-toggle', function () {
            const button = $(this); // store clicked element
            const id = button.data('id');
            const currentStatus = button.data('status');

            bootbox.confirm({
                title: "Are you sure?",
                message: `Do you want to ${currentStatus == 1 ? 'deactivate' : 'activate'} this review?`,
                buttons: {
                    confirm: {
                        label: 'Yes',
                        className: 'btn-success'
                    },
                    cancel: {
                        label: 'No',
                        className: 'btn-danger'
                    }
                },
                callback: function (result) {
                    if (result) {
                        $.ajax({
                            url: "{{ $site_path }}/admin/review/toggle-status",
                            method: "POST",
                            data: {
                                _token: '{{ csrf_token() }}',
                                id: id,
                                status: currentStatus
                            },
                            success: function (res) {
                                if (res.review_status == 1) {
                                    button
                                        .text('Active')
                                        .removeClass('badge--danger')
                                        .addClass('badge--success')
                                        .data('status', 1);
                                } else {
                                    button
                                        .text('Inactive')
                                        .removeClass('badge--success')
                                        .addClass('badge--danger')
                                        .data('status', 0);
                                }

                                notify('success', 'Status updated successfully');
                            },
                            error: function () {
                                notify('error', 'Something went wrong');
                            }
                        });
                    }
                }
            });
        });
    </script>
@endpush
