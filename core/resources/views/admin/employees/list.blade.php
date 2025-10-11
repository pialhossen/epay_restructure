@extends('admin.layouts.app')
@push('breadcrumb-plugins')
    <a href="{{ route('admin.employees.create') }}" type="button" class="btn  btn-outline--primary h-45">
        <i class="las la-plus-circle"></i> @lang('Add New Staff')
    </a>
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
            @forelse($staffs as $staff)
                <tr class="custom-row">
                    <td class="custom-fullname">
                        <span class="fw-bold">{{$staff->name}}</span>
                        <br>
                        <span class="small">
                            <a href="{{ route('admin.employees.edit', $staff->id) }}"><span>@</span>{{ $staff->username }}</a>
                        </span>
                    </td>
                    <td class="custom-email-mobile">
                        {{ $staff->email }}<br>
                    </td>
                    <td>
                        address
                    </td>
                    <td>
                        <div class="button--group">
                            <a href="{{ route('admin.employees.edit', $staff->id) }}" class="btn btn-sm btn-outline--primary">
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
@endsection