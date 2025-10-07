@extends('admin.layouts.app')
@section('panel')
    <form action="{{ route('admin.users.all') }}" style="margin-bottom: 10px;" method="GET">
        @php
            $query = request()->query();
        @endphp
        <div class="row pb-2">
            <div class="col-3">
                <label for="first_name">First Name</label>
                <input type="text" name="first_name" class="form-control" value="{{ $query['first_name'] ?? '' }}">
            </div>
            <div class="col-3">
                <label for="last_name">Last Name</label>
                <input type="text" name="last_name" class="form-control" value="{{ $query['last_name'] ?? '' }}">
            </div>
            <div class="col-3">
                <label for="phone_no">Phone No</label>
                <input type="text" name="phone_no" class="form-control" value="{{ $query['phone_no'] ?? '' }}">
            </div>
            <div class="col-3">
                <label for="email">Email</label>
                <input type="text" name="email" class="form-control" value="{{ $query['email'] ?? '' }}">
            </div>
            <div class="col-3">
                <label for="username">Username</label>
                <input type="text" name="username" class="form-control" value="{{ $query['username'] ?? '' }}">
            </div>
            <div class="col-3">
                <label for="username">Address</label>
                <input type="text" name="address" class="form-control" value="{{ $query['address'] ?? '' }}">
            </div>
        </div>
        <button type="submit" class="btn btn-sm btn-primary">Search</button>
        <a href="{{ route('admin.users.active') }}" class="btn btn-sm btn-info">Reset</a>
    </form>

    <div class="row pl-2 pb-2 ml-2">
        <div class="col-lg-12">
            <div class="">
                <div class="p-0">
                    @php
                        $lastSegment = request()->segment(count(request()->segments()));
                    @endphp
                    <form class="m-2" action="{{ url()->full() }}" method="GET">
                        <div class="row pb-2">
                            <div class="col-1">
                                <label for="exchange_id">Items Per Page</label>
                                <input
                                    value="{{ getPaginate(isset(request()->query()['itemsPerPage']) ? request()->query()['itemsPerPage'] : null) }}"
                                    type="text" name="itemsPerPage" class="form-control">
                                <button type="Submit" class="btn btn-sm btn-primary mt-2">Apply</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

<style>
    td{
        max-width: 200px;
    }
</style>

    <table class="table table--light style--two">
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
                <th>Action</th>
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
                    <td>
                        {{ $user->address }}
                    </td>
                    <td>
                        {{ showDateTime($user->created_at) }} <br> {{ diffForHumans($user->created_at) }}
                    </td>
                    <td>
                        <span class="fw-bold">{{ showAmount($user->balance) }}</span>
                    </td>
                    <td>
                        <span class="fw-bold">{{ $user->approved_exchanges_count }}</span>
                    </td>
                    <td>
                        <div class="button--group">
                            <a href="{{ route('admin.users.detail', $user->id) }}" class="btn btn-sm btn-outline--primary">
                                <i class="las la-desktop"></i> @lang('Details')
                            </a>
                            @if (request()->routeIs('admin.users.kyc.pending'))
                                <a href="{{ route('admin.users.kyc.details', $user->id) }}" target="_blank"
                                    class="btn btn-sm btn-outline--dark">
                                    <i class="las la-user-check"></i>@lang('KYC Data')
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