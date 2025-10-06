@extends($activeTemplate . 'layouts.master')
@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card custom--card">
                    <div class="card-body">
                        <form method="post" class="disableSubmission">
                            @csrf
                            {{-- Current Password --}}
                            <div class="form-group">
                                <label class="form-label">@lang('Current Password')</label>
                                <input type="password" id="current_password" class="form-control form--control"
                                    name="current_password" required autocomplete="current-password">
                            </div>
                            <div class="col-md-12 mt-2">
                                <div class="form-group form--check">
                                    <input type="checkbox" id="toggleCurrentPassword"
                                        onclick="toggleCurrentPasswordVisibility()" class="form-check-input">
                                    <label for="toggleCurrentPassword" id="toggleCurrentLabel">Show Current Password</label>
                                </div>
                            </div>

                            {{-- New Password --}}
                            <div class="form-group">
                                <label class="form-label">@lang('Password')</label>
                                <input type="password"
                                    class="form-control form--control @if (gs('secure_password')) secure-password @endif"
                                    name="password" id="password" required autocomplete="new-password">
                            </div>

                            {{-- Confirm Password --}}
                            <div class="form-group">
                                <label class="form-label">@lang('Confirm Password')</label>
                                <input type="password" class="form-control form--control" name="password_confirmation"
                                    id="password_confirmation" required autocomplete="new-password">
                            </div>

                            <div class="col-md-12 mt-2">
                                <div class="form-group form--check">
                                    <input type="checkbox" id="toggleNewPasswords" onclick="toggleNewPasswordsVisibility()"
                                        class="form-check-input">
                                    <label for="toggleNewPasswords" id="toggleNewLabel">Show New Passwords</label>
                                </div>
                            </div>

                            {{-- Submit --}}
                            <button type="submit" class="btn btn--base w-100">@lang('Submit')</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- JavaScript --}}
    <script>
        function toggleCurrentPasswordVisibility() {
            const currentPassword = document.getElementById("current_password");
            const label = document.getElementById("toggleCurrentLabel");
            const checkbox = document.getElementById("toggleCurrentPassword");

            currentPassword.type = checkbox.checked ? "text" : "password";
            label.textContent = checkbox.checked ? "Hide Current Password" : "Show Current Password";
        }

        function toggleNewPasswordsVisibility() {
            const password = document.getElementById("password");
            const confirmPassword = document.getElementById("password_confirmation");
            const label = document.getElementById("toggleNewLabel");
            const checkbox = document.getElementById("toggleNewPasswords");

            const show = checkbox.checked;
            password.type = show ? "text" : "password";
            confirmPassword.type = show ? "text" : "password";
            label.textContent = show ? "Hide New Passwords" : "Show New Passwords";
        }
    </script>
@endsection

@if (gs('secure_password'))
    @push('script-lib')
        <script src="{{ asset('assets/global/js/secure_password.js') }}"></script>
    @endpush
@endif
