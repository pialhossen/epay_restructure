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
    .data-table td {
        background-color: #F3F3F9 !important;
    }

    .sticky-col {
        position: sticky;
        right: 0;
        z-index: 2; /* higher than other cells */
    }
    .data-table th {
        position: sticky;
        top: 0;
        z-index: 3;
    }
</style>
    <form action="{{ route('admin.users.all') }}" style="margin-bottom: 10px;" method="GET">
        @php
            $query = request()->query();
        @endphp
        @if(request()->query('itemsPerPage'))
            <input type="hidden" name="itemsPerPage" value="{{ request('itemsPerPage') }}">
        @endif
        <div class="row pb-2">
            <div class="col-lg-3 col-md-6 col-12">
                <label for="first_name">First Name</label>
                <input type="text" name="first_name" class="form-control" value="{{ $query['first_name'] ?? '' }}">
            </div>
            <div class="col-lg-3 col-md-6 col-12">
                <label for="last_name">Last Name</label>
                <input type="text" name="last_name" class="form-control" value="{{ $query['last_name'] ?? '' }}">
            </div>
            <div class="col-lg-3 col-md-6 col-12">
                <label for="phone_no">Phone No</label>
                <input type="text" name="phone_no" class="form-control" value="{{ $query['phone_no'] ?? '' }}">
            </div>
            <div class="col-lg-3 col-md-6 col-12">
                <label for="email">Email</label>
                <input type="text" name="email" class="form-control" value="{{ $query['email'] ?? '' }}">
            </div>
            <div class="col-lg-3 col-md-6 col-12">
                <label for="username">Username</label>
                <input type="text" name="username" class="form-control" value="{{ $query['username'] ?? '' }}">
            </div>
            <div class="col-lg-3 col-md-6 col-12">
                <label for="username">Address</label>
                <input type="text" name="address" class="form-control" value="{{ $query['address'] ?? '' }}">
            </div>
        </div>
        <button type="submit" class="btn btn-sm btn-primary">Search</button>
        <a href="{{ route('admin.users.active') }}" class="btn btn-sm btn-info">Reset</a>
    </form>

    <x-item-per-page/>

<style>
    td{
        max-width: 200px;
    }
</style>
    <div class="table-responsive--md  table-responsive table-container">
        <table class="table table--light style--two data-table">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Email / Mobile</th>
                    <th>Country</th>
                    <th>Address</th>
                    <th style="cursor: pointer;" onclick="toggleSort(event, 'created_at')">
                        <div class="sortable-header">
                            <span class="sort-indicate"> 
                                <span class="up" @if(str_contains(request()->query("sort"),'created_at')) style="visibility: {{ request()->query("sort") == "created_at:desc"? "visible": "hidden" }};" @endif><i class="fa-solid fa-arrow-up"></i></span> 
                                <span class="down" @if(str_contains(request()->query("sort"),'created_at')) style="visibility: {{ request()->query("sort") == "created_at:asc"? "visible": "hidden" }};" @endif><i class="fa-solid fa-arrow-down"></i></span> 
                            </span> 
                            <span class="text">
                                @lang('Joined At')
                            </span>
                        </div>
                    </th>
                    <th style="cursor: pointer;" onclick="toggleSort(event, 'balance')">
                        <div class="sortable-header">
                            <span class="sort-indicate"> 
                                <span class="up" @if(str_contains(request()->query("sort"),'balance')) style="visibility: {{ request()->query("sort") == "balance:desc"? "visible": "hidden" }};" @endif><i class="fa-solid fa-arrow-up"></i></span> 
                                <span class="down" @if(str_contains(request()->query("sort"),'balance')) style="visibility: {{ request()->query("sort") == "balance:asc"? "visible": "hidden" }};" @endif><i class="fa-solid fa-arrow-down"></i></span> 
                            </span> 
                            <span class="text">
                                @lang('Balance')
                            </span>
                        </div>
                    </th>
                    <th style="cursor: pointer;" onclick="toggleSort(event, 'completed_orders')">
                        <div class="sortable-header">
                            <span class="sort-indicate"> 
                                <span class="up" @if(str_contains(request()->query("sort"),'completed_orders')) style="visibility: {{ request()->query("sort") == "completed_orders:desc"? "visible": "hidden" }};" @endif><i class="fa-solid fa-arrow-up"></i></span> 
                                <span class="down" @if(str_contains(request()->query("sort"),'completed_orders')) style="visibility: {{ request()->query("sort") == "completed_orders:asc"? "visible": "hidden" }};" @endif><i class="fa-solid fa-arrow-down"></i></span> 
                            </span> 
                            <span class="text">
                                @lang('Completed Orders')
                            </span>
                        </div>
                    </th>
                    <th class="sticky-col">Action</th>
                </tr>
            </thead>
            <tbody id="customTableBody">
                @forelse($users as $user)
                    <tr class="custom-row">
                        <td class="custom-fullname">
                            <span class="fw-bold">{{$user->fullname}}</span>
                            <br>
                            <span class="small">
                                <a href="{{ route('admin.users.detail', $user->id) }}"><span>@</span>{{ $user->username }}</a>
                            </span>
                        </td>
                        <td class="custom-email-mobile">
                            {{ $user->email }}<br>{{ $user->mobileNumber }}
                        </td>
                        <td>
                            <span class="fw-bold" title="{{ @$user->country_name }}">{{ $user->country_code }}</span>
                        </td>
                        <td style="white-space: wrap;">
                            {{ "$user->address, $user->city, $user->state, $user->state, $user->zip" }}
                        <td>
                            {{ $user->created_at->format('d/m/y h:i:s A') }} <br> {{ diffForHumans($user->created_at) }}
                        </td>
                        <td>
                            <span class="fw-bold">{{ showAmount($user->balance) }}</span>
                        </td>
                        <td>
                            <span class="fw-bold">{{ $user->approved_exchanges_count }}</span>
                        </td>
                        <td class="sticky-col">
                            <div class="button--group">
                                @if(checkSpecificPermission('View - Users Details'))
                                <a href="{{ route('admin.users.detail', $user->id) }}" class="btn btn-sm btn-outline--primary">
                                    <i class="las la-desktop"></i> @lang('Details')
                                </a>
                                @else
                                <button class="btn btn-sm btn-outline--primary" disabled>
                                    <i class="las la-desktop"></i> @lang('Details')
                                </button>
                                @endif
                                @if (request()->routeIs('admin.users.kyc.pending'))
                                    <a href="{{ route('admin.users.kyc.details', $user->id) }}" target="_blank"
                                        class="btn btn-sm btn-outline--dark">
                                        <i class="las la-user-check"></i>@lang('KYC Data')
                                    </a>
                                @endif
                                
                                @if(checkSpecificPermission('View - Login As User'))
                                <a href="{{ route('admin.users.login', $user->id) }}" target="_blank" class="btn btn-sm btn-outline--primary">
                                    <i class="las la-sign-in-alt"></i>@lang('Login as User')
                                </a>
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
    </div>
    @if ($users->hasPages())
        <div class="card-footer py-4">
            {{ paginateLinks($users) }}
        </div>
    @endif
@endsection


{{--
@push('breadcrumb-plugins')
<x-search-form placeholder="Username / Email" />
<x-search-form placeholder="Mobile No" />
<x-search-form placeholder="Name" />
@endpush --}}