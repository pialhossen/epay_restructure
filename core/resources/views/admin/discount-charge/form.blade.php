@extends('admin.layouts.app')


@section('panel')
    <div class="row">
        <div class="col-lg-12">
            <div class="card b-radius--10">
                <div class="card-body">
                    <form method="POST"
                        action="{{ isset($charge) ? route('admin.discount.charge.update', $charge->id) : route('admin.discount.charge.store') }}">
                        @csrf
                        @if (isset($charge))
                            @method('PUT')
                        @endif

                        <div class="form-group">
                            <label>@lang('Currency')</label>
                            <select name="currency_id" class="form-control" required>
                                <option value="">@lang('Select')</option>
                                @foreach ($currencies as $currency)
                                    <option value="{{ $currency->id }}"
                                        {{ (isset($charge) && $charge->currency_id == $currency->id) || (!isset($charge) && request('currency_id') == $currency->id) ? 'selected' : '' }}>
                                        {{ $currency->name }} ({{ $currency->cur_sym }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label>@lang('Rules For')</label>
                            <select name="rules_for" class="form-control" required>
                                <option value="">@lang('Select')</option>
                                <option value="buy"
                                    {{ isset($charge) && $charge->rules_for == 'buy' ? 'selected' : '' }}>
                                    @lang('Customer Buy')</option>
                                <option value="sell"
                                    {{ isset($charge) && $charge->rules_for == 'sell' ? 'selected' : '' }}>
                                    @lang('Customer Sell')</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>@lang('Apply For')</label>
                            <select name="apply_for[]" class="form-control select2" multiple="multiple" required>
                                @if(isset($charge))
                                    <option value="exchange"
                                        {{ in_array("exchange", $charge->apply_for_array)? 'selected' : '' }}>
                                        Exchange
                                    </option>
                                    <option value="deposit"
                                        {{ in_array("deposit", $charge->apply_for_array)? 'selected' : ''}}>
                                        Deposit
                                    </option>
                                    <option value="withdraw"
                                        {{ in_array("withdraw", $charge->apply_for_array)? 'selected' : ''}}>
                                        Withdraw
                                    </option>
                                @else
                                    <option value="exchange">
                                        Exchange
                                    </option>
                                    <option value="deposit">
                                        Deposit
                                    </option>
                                    <option value="withdraw">
                                        Withdraw
                                    </option>
                                @endif
                            </select>
                        </div>

                        <div class="form-group">
                            <label>@lang('Title')</label>
                            <input type="text" name="title" class="form-control" value="{{ isset($charge)? $charge->title : '' }}"
                                required>
                        </div>

                        <div class="form-group">
                            <label>@lang('Description')</label>
                            <textarea name="description" class="form-control">{{ isset($charge)? $charge->description: '' }}</textarea>
                        </div>

                        <div class="form-group">
                            <label>@lang('Charge/Discount (%)')</label>
                            <input type="number" step="any" name="charge_percent" class="form-control"
                                value="{{ isset($charge)? $charge->charge_percent: '' }}">
                        </div>
                        <div class="form-group">
                            <label>@lang('Fixed Charge/Discount')</label>
                            <input type="number" step="any" name="charge_fixed" class="form-control"
                                value="{{ isset($charge)? $charge->charge_fixed: '' }}">
                        </div>

                        <div class="form-group">
                            <label>@lang('From')</label>
                            <input type="number" name="from" class="form-control" value="{{ isset($charge)? $charge->from: '' }}"
                                required step="0.0001" min="0" pattern="^\d+(\.\d{1,4})?$">
                        </div>

                        <div class="form-group">
                            <label>@lang('To')</label>
                            <input type="number" name="to" class="form-control" value="{{ isset($charge)? $charge->to: '' }}"
                                required step="0.0001" min="0" pattern="^\d+(\.\d{1,4})?$">
                        </div>

                        <div class="form-check mt-3">
                            <input class="form-check-input" type="checkbox" name="continue" id="continue" value="1">
                            <label class="form-check-label" for="continue">@lang('Continue Creating')</label>
                        </div>
                        <button type="submit" class="btn btn--primary mt-3">@lang('Submit')</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
