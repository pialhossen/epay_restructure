@extends('admin.layouts.app')
@section('panel')
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card b-radius--10">
                <div class="card-body">
                    <form action="{{ route('admin.block.user.save', @$user->id) }}" method="POST">
                        @csrf
                        <div class="form-group">
                            <label>@lang('Phone Number')</label>
                            <input type="text" name="phone_number" class="form-control"
                                value="{{ old('phone_number', @$user->phone_number) }}">
                        </div>
                        <div class="form-group">
                            <label>@lang('Email')</label>
                            <input type="email" name="email" class="form-control"
                                value="{{ old('email', @$user->email) }}">
                        </div>
                        <div class="form-group mt-3">
                            <button type="submit" class="btn btn--primary w-100 h-45">@lang('Submit')</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
