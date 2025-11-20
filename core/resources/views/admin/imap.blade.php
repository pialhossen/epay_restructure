@extends('admin.layouts.app')

@section('panel')
    <div class="row mb-none-30">
        <div class="col-lg-12 col-md-12 mb-30">
            <div class="card">
                <div class="card-body">
                    <form method="POST" class="disableSubmission" action="{{ route('admin.imap.store') }}" enctype= multipart/form-data>
                        @csrf
                        <div class="row">
                            <div class="col-sm-6 col-md-4">
                                <div class="form-group ">
                                    <label> @lang('Imap Account')</label>
                                    <input class="form-control" type="text" name="imap_account" required value="{{ old('imap_account',$imap_account) }}">
                                </div>
                            </div>
                            <div class="col-sm-6 col-md-4">
                                <div class="form-group ">
                                    <label> @lang('Imap Host')</label>
                                    <input class="form-control" type="text" name="imap_host" required value="{{ old('imap_host',$imap_host) }}">
                                </div>
                            </div>
                            <div class="col-sm-6 col-md-4">
                                <div class="form-group ">
                                    <label> @lang('Imap Port')</label>
                                    <input class="form-control" type="text" name="imap_port" required value="{{ old('imap_port',$imap_port) }}">
                                </div>
                            </div>
                            <div class="col-sm-6 col-md-4">
                                <div class="form-group ">
                                    <label> @lang('Imap Encryption')</label>
                                    <input class="form-control" type="text" name="imap_encryption" required value="{{ old('imap_encryption',$imap_encryption) }}">
                                </div>
                            </div>
                            <div class="col-sm-6 col-md-4">
                                <div class="form-group ">
                                    <label> @lang('Imap Validate Cert')</label>
                                    <input class="form-control" type="text" name="imap_validate_cert" required value="{{ old('imap_validate_cert',$imap_validate_cert) }}">
                                </div>
                            </div>
                            <div class="col-sm-6 col-md-4">
                                <div class="form-group ">
                                    <label> @lang('Imap Username')</label>
                                    <input class="form-control" type="text" name="imap_username" required value="{{ old('imap_username',$imap_username) }}">
                                </div>
                            </div>
                            <div class="col-sm-6 col-md-4">
                                <div class="form-group ">
                                    <label> @lang('Imap Password')</label>
                                    <input class="form-control" type="text" name="imap_password" required value="{{ old('imap_password',$imap_password) }}">
                                </div>
                            </div>
                            <div class="col-sm-6 col-md-4">
                                <div class="form-group ">
                                    <label> @lang('Imap Protocol')</label>
                                    <input class="form-control" type="text" name="imap_protocol" required value="{{ old('imap_protocol',$imap_protocol) }}">
                                </div>
                            </div>
                            <div class="col-sm-6 col-md-4">
                                <div class="form-group ">
                                    <label> @lang('Imap Filter From')</label>
                                    <input class="form-control" type="text" name="imap_filter_from" required value="{{ old('imap_filter_from',$imap_filter_from) }}">
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn--primary w-100 h-45">@lang('Submit')</button>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
