@extends('admin.layouts.app')
@section('panel')
    {{--  Search start  --}}
    <form action="{{ route('admin.users.all') }}" style="margin-bottom: 10px;" method="GET">
    <div class="row pb-2">
        <div class="col-3">
            <label for="first_name">First Name</label>
            <input type="text" name="first_name" class="form-control" value="{{ $request->full_name ?? '' }}">
        </div>
        <div class="col-3">
            <label for="last_name">Last Name</label>
            <input type="text" name="last_name" class="form-control" value="{{ $request->full_name ?? '' }}">
        </div>
        <div class="col-3">
            <label for="phone_no">Phone No</label>
            <input type="text" name="phone_no" class="form-control" value="{{ $request->phone_no ?? '' }}">
        </div>
        <div class="col-3">
            <label for="email">Email</label>
            <input type="text" name="email" class="form-control" value="{{ $request->email ?? '' }}">
        </div>
        <div class="col-3">
            <label for="username">Username</label>
            <input type="text" name="username" class="form-control" value="{{ $request->username ?? '' }}">
        </div>
    </div>
    <button type="submit" class="btn btn-sm btn-primary">Search</button>
    <a href="{{ route('admin.users.active') }}" class="btn btn-sm btn-info">Reset</a>
</form>



    {{--  search end  --}}

    <table class="table table--light style--two">
    <thead>
        <tr>
            <th>User</th>
            <th>Email / Mobile</th>
            <th>Country</th>
            <th>Joined At</th>
            <th>Balance</th>
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
                {{ showDateTime($user->created_at) }} <br> {{ diffForHumans($user->created_at) }}
            </td>
            <td>
                <span class="fw-bold">{{ showAmount($user->balance) }}</span>
            </td>
            <td>
                <div class="button--group">
                    <a href="{{ route('admin.users.detail', $user->id) }}" class="btn btn-sm btn-outline--primary">
                        <i class="las la-desktop"></i> @lang('Details')
                    </a>
                    @if (request()->routeIs('admin.users.kyc.pending'))
                    <a href="{{ route('admin.users.kyc.details', $user->id) }}" target="_blank" class="btn btn-sm btn-outline--dark">
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
@endpush  --}}

