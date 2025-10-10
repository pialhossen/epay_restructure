@extends('admin.layouts.app')
@push('breadcrumb-plugins')
    <a href="{{ route('admin.employee.staffs.create') }}" type="button" class="btn  btn-outline--primary h-45">
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
                
                <!-- <th style="cursor: pointer;" onclick="toggleSort(event, 'completed_orders')">
                    <div class="sortable-header">
                        <span class="sort-indicate"> 
                            <span class="up" @if(str_contains(request()->query("sort"),'completed_orders')) style="visibility: {{ request()->query("sort") == "completed_orders:desc"? "visible": "hidden" }};" @endif><i class="fa-solid fa-arrow-up"></i></span> 
                            <span class="down" @if(str_contains(request()->query("sort"),'completed_orders')) style="visibility: {{ request()->query("sort") == "completed_orders:asc"? "visible": "hidden" }};" @endif><i class="fa-solid fa-arrow-down"></i></span> 
                        </span> 
                        <span class="text">
                            @lang('Completed Orders')
                        </span>
                    </div>
                </th> -->

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
                            <a href="{{ route('admin.employee.staffs.edit', $staff->id) }}"><span>@</span>{{ $staff->username }}</a>
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
                            <a href="{{ route('admin.employee.staffs.edit', $staff->id) }}" class="btn btn-sm btn-outline--primary">
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