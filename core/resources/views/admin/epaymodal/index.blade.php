@extends('admin.layouts.app')

@section('panel')
<div class="row">
    <div class="col-lg-12">
        <div class="card b-radius--10 shadow-sm border-0">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="mb-0 fw-bold">@lang('Epay Home Page Modals')</h6>
                    <a href="{{ route('admin.epaymodal.create') }}" class="btn btn--primary">
                        <i class="las la-plus"></i> @lang('Add New')
                    </a>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead >
                            <tr class="bg-primary">
                                <th>@lang('ID')</th>
                                <th>@lang('Title')</th>
                                <th>@lang('Description')</th>
                                <th>@lang('Button Name')</th>
                                <th>@lang('Image')</th>
                                <th>@lang('Status')</th>
                                <th>@lang('Created Date')</th>
                                <th class="text-center">@lang('Action')</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($items as $item)
                            <tr>
                                <td>{{ $item->id }}</td>
                                <td>{{ __($item->title) }}</td>
                                <td>{{ __($item->description) }}</td>
                                <td>{{ __($item->button_name) }}</td>
                                <td>
                                    @if ($item->image)
                                    <img src="{{ asset($item->image) }}" alt="Image" class="img-thumbnail" style="width: 80px; height: 80px; object-fit: cover;">
                                    @else
                                    <span class="text-muted">@lang('No Image')</span>
                                    @endif
                                </td>

                                <td>
                                    @if ($item->status)
                                    <span class="badge bg-success">@lang('Active')</span>
                                    @else
                                    <span class="badge bg-danger">@lang('Inactive')</span>
                                    @endif
                                </td>

                                <td>{{ $item->cd ? $item->cd->format('Y-m-d H:i') : '-' }}</td>
                                <td class="text-center">
                                    <a href="{{ route('admin.epaymodal.edit', $item->id) }}"
                                        class="btn btn-sm btn-outline-primary me-1" title="@lang('Edit')">
                                        <i class="la la-pencil"></i>
                                    </a>
                                    <a href="{{ route('admin.epaymodal.delete', $item->id) }}"
                                        class="btn btn-sm btn-outline-danger confirmationBtn"
                                        title="@lang('Delete')" onclick="return confirm('Are you sure?')">
                                        <i class="la la-trash"></i>
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="100%" class="text-center text-muted py-4">
                                    @lang('No data found')
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($items->hasPages())
                <div class="card-footer py-3">
                    {{ paginateLinks($items) }}
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

@endsection

@push('style')
<style>

       .table {
        border-radius: 8px;
        overflow: hidden;
    }

    .table thead tr {
        background-color: #213141ff;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 13px;
        letter-spacing: .5px;
    }

    .table tbody tr:hover {
        background-color: #f1f3f6;
        transition: 0.2s;
    }

    .btn-sm {
        padding: 4px 8px;
        border-radius: 6px;
    }
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
            url: "/admin/epay-modal/toggle-status",
            method: "POST",
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