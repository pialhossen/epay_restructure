@extends('admin.layouts.app')

@section('panel')
    <div class="row">
        <div class="col-lg-12">
            <div class="show-filter mb-3 d-flex justify-content-between align-items-center">
                <button type="button" class="btn btn-outline--primary showFilterBtn btn-sm">
                    <i class="las la-filter"></i> @lang('Filter')
                </button>
            </div>

            <div class="card responsive-filter-card mb-4">
                <div class="card-body">
                    <form method="GET" action="">
                        <div class="d-flex flex-wrap gap-4">
                            <div class="flex-grow-1">
                                <label>@lang('Full Name')</label>
                                <input type="text" name="name" class="form-control" value="{{ request()->name }}"
                                    placeholder="@lang('Full Name')">
                            </div>

                            <div class="flex-grow-1">
                                <label>@lang('Username')</label>
                                <input type="text" name="username" class="form-control" value="{{ request()->username }}"
                                    placeholder="@lang('Username')">
                            </div>

                            <div class="flex-grow-1">
                                <label>@lang('Email')</label>
                                <input type="text" name="email" class="form-control" value="{{ request()->email }}"
                                    placeholder="@lang('Email')">
                            </div>

                            <div class="flex-grow-1">
                                <label>@lang('Mobile')</label>
                                <input type="text" name="mobile" class="form-control" value="{{ request()->mobile }}"
                                    placeholder="@lang('Mobile')">
                            </div>

                            <div class="flex-grow-1">
                                <label>@lang('Exchange Count')</label>
                                <input type="number" name="success_order" class="form-control"
                                    value="{{ request()->success_order }}" placeholder="@lang('Exchange Count')">
                            </div>

                            <div class="flex-grow-1 align-self-end">
                                <button class="btn btn--primary w-100 h-45">
                                    <i class="fas fa-filter"></i> @lang('Filter')
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive--sm table-responsive">
                        <table class="table table--light style--two mb-0">
                            <thead>
                                <tr>
                                    <th>@lang('Full Name')</th>
                                    <th>@lang('Email')</th>
                                    <th>@lang('Mobile')</th>
                                    <th>@lang('Total Exchanges')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($exchanges as $user)
                                    <tr>
                                        <td>
                                            <span class="fw-bold">{{ $user['full_name'] ?? $user->firstname . ' ' . $user->lastname }}</span><br>

                                            <small>
                                                <a href="{{ appendQuery('search', $user->username) }}">
                                                    @
                                                    {{ $user->username }}
                                                </a>
                                            </small>
                                        </td>
                                        <td>{{ $user->email }}</td>
                                        <td>{{ $user->mobile }}</td>
                                        <td>{{ $user->success_order }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">
                                            @lang('No user data found')
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
@endsection
