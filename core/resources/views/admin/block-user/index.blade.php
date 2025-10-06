@extends('admin.layouts.app')

@section('panel')
    <div class="row">
        <div class="col-lg-12">
            <div class="card b-radius--10">
                <div class="card-body p-0">
                    <div class="table-responsive--md">
                        <table class="table table--light">
                            <thead>
                                <tr>
                                    <th>@lang('Phone Number')</th>
                                    <th>@lang('Email')</th>
                                    <th>@lang('Action')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($users as $user)
                                    <tr>
                                        <td>{{ $user->phone_number ?? 'N/A' }}</td>
                                        <td>{{ $user->email ?? 'N/A' }}</td>
                                        <td>
                                            <a href="{{ route('admin.block.user.edit', $user->id) }}"
                                                class="btn btn-sm btn-outline--primary">
                                                <i class="la la-edit"></i> @lang('Edit')
                                            </a>

                                            <button type="button" class="btn btn-sm btn-outline--danger btn-delete"
                                                data-id="{{ $user->id }}">
                                                <i class="la la-trash"></i> @lang('Delete')
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="100%" class="text-muted text-center">@lang('No blocked users found')</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                @if ($users->hasPages())
                    <div class="card-footer py-4">
                        {{ paginateLinks($users) }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Hidden form for delete -->
    <form id="delete-form" method="POST" class="d-none">
        @csrf
    </form>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteConfirmModal" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">@lang('Delete Confirmation')</h5>
                    <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                        <i class="las la-times"></i>
                    </button>
                </div>
                <div class="modal-body text-center">
                    <p>@lang('Are you sure you want to delete this blocked user?')</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn--secondary" data-bs-dismiss="modal">@lang('Not Now')</button>
                    <button type="button" class="btn btn--danger" id="confirmDelete">@lang('Confirm')</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('breadcrumb-plugins')
    <a href="{{ route('admin.block.user.create') }}" class="btn btn-outline--primary">
        <i class="la la-plus"></i> @lang('Block New User')
    </a>
@endpush

@push('script')
    <script>
        "use strict";
        (function($) {
            let deleteForm = $('#delete-form');
            let deleteModal = $('#deleteConfirmModal');
            let deleteRoute = '';

            // On click delete button
            $('.btn-delete').on('click', function() {
                const id = $(this).data('id');
                deleteRoute = `{{ route('admin.block.user.delete', ':id') }}`.replace(':id', id);
                deleteModal.modal('show');
            });

            // On confirm delete
            $('#confirmDelete').on('click', function() {
                deleteForm.attr('action', deleteRoute).submit();
            });
        })(jQuery);
    </script>
@endpush
