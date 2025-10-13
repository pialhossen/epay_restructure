@extends('admin.layouts.app')

@section('panel')
    <x-item-per-page/>
    <div class="row">
        <div class="col-lg-12">
            <div class="card b-radius--10">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="mb-0">@lang('Discount Charges')</h6>
                        <a href="{{ route('admin.discount.charge.create') }}" class="btn btn--primary">
                            <i class="las la-plus"></i> @lang('Add New')
                        </a>
                    </div>

                    <form method="GET" action="{{ route('admin.discount.charge.index') }}" class="mb-3">
                        <div class="row">
                            <div class="col-md-4">
                                <label>@lang('Filter by Currency')</label>
                                <select name="currency_id" class="form-control">
                                    <option value="">@lang('All Currencies')</option>
                                    @foreach ($currencies as $currency)
                                        <option value="{{ $currency->id }}"
                                            {{ request('currency_id') == $currency->id ? 'selected' : '' }}>
                                            {{ $currency->name }} ({{ $currency->cur_sym }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label>@lang('Filter by Type')</label>
                                <select name="rules_for" class="form-control">
                                    <option value="">@lang('All')</option>
                                    <option value="buy" {{ request('rules_for') == 'buy' ? 'selected' : '' }}>
                                        @lang('Customer Buy')</option>
                                    <option value="sell" {{ request('rules_for') == 'sell' ? 'selected' : '' }}>
                                        @lang('Customer Sell')</option>
                                </select>
                            </div>

                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn--primary w-100">@lang('Filter')</button>
                            </div>
                        </div>

                    </form>

                    <div class="table-responsive--md">
                        <table class="table table--light style--two">
                            <thead>
                                <tr>
                                    <th>@lang('Currency')</th>
                                    <th>@lang('Type')</th>
                                    <th>@lang('Title')</th>
                                    <th>@lang('Charge Percent (%)')</th>
                                    <th>@lang('Charge Fixed')</th>
                                    <th>@lang('From')</th>
                                    <th>@lang('To')</th>
                                    <th>@lang('Action')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($charges as $item)
                                    <tr>
                                        <td>{{ @$item->currency->name ?? 'N/A' }} ({{ @$item->currency->cur_sym ?? 'N/A' }})
                                        </td>
                                        <td>{{ $item->rules_for == 'buy' ? 'Customer Buy' : 'Customer Sell' }}</td>
                                        <td>{{ __($item->title) }}</td>
                                        <td>{{ number_format($item->charge_percent, 4) }}</td>
                                        <td>{{ number_format($item->charge_fixed, 4) }}</td>
                                        <td>{{ $item->from ?? '-' }}</td>
                                        <td>{{ $item->to ?? '-' }}</td>
                                        <td>
                                            <a href="{{ route('admin.discount.charge.edit', $item->id) }}"
                                                class="btn btn-sm btn-outline--primary" title="@lang('Edit')">
                                                <i class="la la-pencil"></i>
                                            </a>
                                            <a href="{{ route('admin.discount.charge.delete', $item->id) }}"
                                                class="btn btn-sm btn-outline--danger confirmationBtn"
                                                title="@lang('Delete')">
                                                <i class="la la-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="100%" class="text-center text-muted">@lang('No data found')</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if ($charges->hasPages())
                        <div class="card-footer py-4">
                            {{ paginateLinks($charges) }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
