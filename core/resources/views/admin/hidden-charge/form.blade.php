@extends('admin.layouts.app')
@section('panel')
    <div class="row">
        <div class="col-lg-12">
            <div class="card b-radius--10">
                <div class="card-body">
                    <form method="POST"
                        action="{{ isset($charge) ? route('admin.hidden.charge.update', $charge->id) : route('admin.hidden.charge.store') }}">
                        @csrf
                        @if (isset($charge))
                            @method('PUT')
                        @endif

                        <div class="form-group">
                            <label>@lang('Currency')</label>
                            <select name="currency_id" class="form-control" required>
                                <option value="">@lang('Select')</option>
                                @foreach ($currencies as $currency)
                                    <option value="{{ $currency->id }}" @if (
                                        (isset($charge) && $charge->currency_id == $currency->id) ||
                                            (!isset($charge) && request('currency_id') == $currency->id)) selected @endif>
                                        {{ $currency->name }} ({{ $currency->cur_sym }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label>@lang('Title')</label>
                            <input type="text" name="title" class="form-control" value="{{ $charge->title ?? '' }}"
                                required>
                        </div>

                        <div class="form-group">
                            <label>@lang('Description')</label>
                            <textarea name="description" class="form-control">{{ $charge->description ?? '' }}</textarea>
                        </div>

                        <div class="form-group">
                            <label>@lang('Charge Percent(%)')</label>
                            <input type="text" name="charge_percent" class="form-control"
                                value="{{ $charge->charge_percent ?? '' }}">
                        </div>

                        <div class="form-group">
                            <label>@lang('Charge Fixed')</label>
                            <input type="text" name="charge_fixed" class="form-control"
                                value="{{ $charge->charge_fixed ?? '' }}">
                        </div>

                        {{-- <div class="form-group d-flex align-items-center">
                            <label class="mr-3">@lang('Status')</label>
                            <label class="switch">
                                <input type="checkbox" name="status"
                                    {{ isset($charge) && $charge->status ? 'checked' : '' }}>
                                <span class="slider round"></span>
                            </label>
                        </div> --}}

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
