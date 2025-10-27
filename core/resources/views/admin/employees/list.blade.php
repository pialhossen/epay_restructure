@extends('admin.layouts.app')
@push('breadcrumb-plugins')
    @if(checkSpecificPermission('Create - Employees'))
    <a href="{{ route('admin.employees.create') }}" type="button" class="btn  btn-outline--primary h-45">
        <i class="las la-plus-circle"></i> @lang('Add New Staff')
    </a>
    @else
    <button type="button" class="btn  btn-outline--primary h-45" disabled>
        <i class="las la-plus-circle"></i> @lang('Add New Staff')
    </button>
    @endif
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
                <th>Email</th>
                <th>Roles</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody id="customTableBody">
            @forelse($employees as $employee)
                <tr class="custom-row">
                    <td class="custom-fullname">
                        <span class="fw-bold">{{$employee->name}}</span>
                        <br>
                        <span class="small">
                            <a href="{{ route('admin.employees.edit', $employee->id) }}"><span>@</span>{{ $employee->username }}</a>
                        </span>
                    </td>
                    <td class="custom-email-mobile">
                        {{ $employee->email }}<br>
                    </td>
                    <td>
                        @foreach ($employee->roles as $index => $role)
                        {{ $role->name }}{{ ($index != ($employee->roles->count()-1))? ',' : '' }}
                        @endforeach
                    </td>
                    <td>
                        <div class="button--group">
                            @if(checkSpecificPermission('Update - Employees'))
                            <a href="{{ route('admin.employees.edit', $employee->id) }}" class="btn btn-sm btn-outline--primary">
                                <i class="las la-desktop"></i> @lang('Details')
                            </a>
                            @else
                            <button class="btn btn-sm btn-outline--primary" disabled>
                                <i class="las la-desktop"></i> @lang('Details')
                            </button>
                            @endif
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
    @if ($employees->hasPages())
        <div class="card-footer py-4">
            {{ paginateLinks($employees) }}
        </div>
    @endif
@endsection