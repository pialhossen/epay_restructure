@extends('admin.layouts.master')
@section('content')
<div class="login-main"
    style="background-image: url('{{ asset('assets/admin/images/login.jpg') }}')">
    <div class="container custom-container">
        <div class="row justify-content-center">
            <div class="col-xxl-5 col-xl-5 col-lg-6 col-md-8 col-sm-11">
                <div class="login-area">
                    <div class="login-wrapper">
                        <div class="login-wrapper__top">
                            <h3 class="title text-white">@lang('Welcome to') <strong>{{ __(gs('site_name')) }}</strong></h3>
                            <p class="text-white">{{ __($pageTitle) }} @lang('to') {{ __(gs('site_name')) }}
                                @lang('Dashboard')</p>
                        </div>
                        <div class="login-wrapper__body">
                            <form action="{{ route('admin.login') }}" method="POST"
                                class="cmn-form mt-30 verify-gcaptcha login-form disableSubmission">
                                @csrf
                                <div class="form-group">
                                    <label>@lang('Username')</label>
                                    <input type="text" class="form-control" value="{{ old('username') }}" name="username" required>
                                </div>
                                <div class="form-group" style="position: relative;">
                                    <div class="d-flex justify-content-between">
                                        <label>@lang('Password')</label>
                                        <a href="{{ route('admin.password.reset') }}" class="forget-text">@lang('Forgot Password?')</a>
                                    </div>
                                    <style>
                                        .eye{
                                            font-size: 20px;
                                            position: absolute;
                                            top: 37px;
                                            right: 15px;
                                            padding: 5px;
                                            border-radius: 50%;
                                            cursor: pointer;
                                            width: 35px;
                                            height: 35px;
                                            display: flex;
                                            align-items: center;
                                            justify-content: center;
                                            color: #3D2BFB;
                                        }
                                        .eye:hover{
                                            background-color: black;
                                        }
                                    </style>
                                    <script>
                                        function togglePasswordVisibility(element, inputId){
                                            const passwordField = document.querySelector(inputId)
                                            if(passwordField.type == "password"){
                                                passwordField.type = "text"
                                                element.innerHTML = `<i class="fa-solid fa-eye-slash"></i>`
                                            }
                                            else if(passwordField.type == "text"){
                                                passwordField.type = "password"
                                                element.innerHTML = `<i class="fa-solid fa-eye"></i>`
                                            }
                                        }
                                    </script>
                                    <input type="password" class="form-control" name="password" required>
                                    <span onclick="togglePasswordVisibility(this,'#password')" class="eye"><i class="fa-solid fa-eye"></i></span>
                                </div>
                                <x-captcha />
                                <button type="submit" class="btn cmn-btn w-100">@lang('LOGIN')</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
