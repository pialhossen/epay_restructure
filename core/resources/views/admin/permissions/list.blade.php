@extends('admin.layouts.app')
@push('breadcrumb-plugins')
    <button data-bs-toggle="modal" data-bs-target="#addRole" type="button" class="btn  btn-outline--primary h-45">
        <i class="las la-plus-circle"></i> @lang('Add New Permission')
    </button>
@endpush
@section('panel')
<style>
    td{
        max-width: 200px;
    }
</style>
    <table class="table table--light style--two">
        <thead>
            <tr>
                <th>Permission</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody id="customTableBody">
            @forelse($permissions as $permission)
                <tr class="custom-row">
                    <td class="custom-fullname">
                        <span class="fw-bold">{{ $permission->name }}</span>
                    </td>
                    <td>
                        <div class="button--group">
                            <button data-bs-toggle="modal" data-bs-target="#edit_parmission_{{ $permission->id }}" class="btn btn-sm btn-outline--primary">
                                <i class="las la-edit"></i> @lang('Edit')
                            </button>
                            <button data-bs-toggle="modal" data-bs-target="#delete_parmission_{{ $permission->id }}" class="btn btn-sm btn-outline--danger">
                                <i class="las la-trash-alt"></i> @lang('Delete')
                            </button>
                        </div>
                    </td>
                </tr>
                <x-modals.permission :permission=$permission/>

                <div id="delete_parmission_{{ $permission->id }}" class="modal fade" tabindex="-1" role="dialog">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title"><span class="type"></span> <span>@lang('{{ $permission? "Edit": "Add" }} Permissions')</span></h5>
                                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                                    <i class="las la-times"></i>
                                </button>
                            </div>
                            <form action="{{ route('admin.employees.permissions.delete',[$permission->id]) }}" method="POST">
                                @csrf
                                <div class="modal-body">
                                    <div class="form-group">
                                        <h4>@lang("Are You Sure You Want to Delete ($permission->name)")</h4>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="submit" class="btn btn--danger h-45 w-100">@lang('Submit')</button>
                                    <button type="button" data-bs-dismiss="modal" aria-label="Close" class="btn btn--primary h-45 w-100 close">@lang('Cancel')</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                @empty
                <tr>
                    <td class="text-muted text-center" colspan="100%">{{ __($emptyMessage) }}</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    <x-modals.permission />
    
    @if ($permissions->hasPages())
        <div class="card-footer p-4">
            {{ paginateLinks($permissions) }}
        </div>
    @endif
@endsection