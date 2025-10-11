@extends('admin.layouts.app')
@push('breadcrumb-plugins')
    <button data-bs-toggle="modal" data-bs-target="#addRole" type="button" class="btn  btn-outline--primary h-45">
        <i class="las la-plus-circle"></i> @lang('Add New Role')
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
                <th>User</th>
                <th>Permissions</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody id="customTableBody">
            @forelse($roles as $role)
                <tr class="custom-row">
                    <td class="custom-fullname">
                        <span class="fw-bold">{{ $role->name }}</span>
                    </td>
                    <td class="custom-email-mobile">
                        All Permission
                    </td>
                    <td>
                        <div class="button--group">
                            <a href="{{ route('admin.employees.roles.edit', $role->id) }}" class="btn btn-sm btn-outline--primary">
                                <i class="las la-desktop"></i> @lang('Details')
                            </a>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td class="text-muted text-center" colspan="100%">{{ __($emptyMessage) }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>
    {{-- @if ($staffs->hasPages())
        <div class="card-footer py-4">
            {{ paginateLinks($staffs) }}
        </div>
    @endif --}} 

    <x-modals.add_role />
@endsection