@extends($activeTemplate . 'layouts.master')
@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card custom--card">
                    <div class="card-body">
                        <form method="post" class="disableSubmission">
                            @csrf
                            <style>
                                .eye {
                                    font-size: 18px;
                                    position: absolute;
                                    top: 42px;
                                    right: 15px;
                                    padding: 5px;
                                    border-radius: 50%;
                                    cursor: pointer;
                                    width: 35px;
                                    height: 35px;
                                    display: flex;
                                    align-items: center;
                                    justify-content: center;
                                    color: darkslategray;
                                }

                                .eye:hover {
                                    background-color: rgb(235 235 235);
                                    color: black;
                                }
                            </style>
                            <script>
                                function togglePasswordVisibility(element, inputId) {
                                    let passwordFields = document.querySelectorAll("input[type='password']")
                                    if(passwordFields.length === 0){
                                        passwordFields = document.querySelectorAll("input[type='text']")
                                    }
                                    const eyes = document.querySelectorAll(".eye")
                                    passwordFields.forEach(passwordField => {
                                        if (passwordField.type == "password") {
                                            passwordField.type = "text"
                                            eyes.forEach(eye => {
                                                eye.innerHTML = `<i class="fa-solid fa-eye-slash"></i>`
                                            })
                                        }
                                        else if (passwordField.type == "text") {
                                            passwordField.type = "password"
                                            eyes.forEach(eye => {
                                                eye.innerHTML = `<i class="fa-solid fa-eye"></i>`
                                            })
                                        }
                                    })
                                }
                            </script>
                            {{-- Current Password --}}
                            <div class="form-group" style="position: relative;">
                                <label class="form-label">@lang('Current Password')</label>
                                <input type="password" id="current_password" class="form-control form--control"
                                    name="current_password" required autocomplete="current-password">
                                <span onclick="togglePasswordVisibility(this,'#password')" class="eye"><i
                                    class="fa-solid fa-eye"></i></span>
                            </div>

                            {{-- New Password --}}
                            <div class="form-group" style="position: relative;">
                                <label class="form-label">@lang('Password')</label>
                                <input type="password"
                                    class="form-control form--control @if (gs('secure_password')) secure-password @endif"
                                    name="password" id="password" required autocomplete="new-password">
                                <span onclick="togglePasswordVisibility(this,'#password')" class="eye"><i
                                    class="fa-solid fa-eye"></i></span>
                            </div>

                            {{-- Confirm Password --}}
                            <div class="form-group" style="position: relative;">
                                <label class="form-label">@lang('Confirm Password')</label>
                                <input type="password" class="form-control form--control" name="password_confirmation"
                                    id="password_confirmation" required autocomplete="new-password">
                                <span onclick="togglePasswordVisibility(this,'#password')" class="eye"><i
                                    class="fa-solid fa-eye"></i></span>
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
