@extends('admin.layouts.app')
@section('panel')
    <div class="row mb-none-30">
        <div class="col-lg-3 col-md-3 mb-30">
            <div class="card b-radius--5 overflow-hidden">
                <div class="card-body p-0">
                    <div class="d-flex p-3 bg--primary align-items-center">
                        <div class="avatar avatar--lg">
                            <img src="{{ getImage(getFilePath('adminProfile') . '/' . $admin->image, getFileSize('adminProfile')) }}"
                                alt="Image">
                        </div>
                        <div class="ps-3">
                            <h4 class="text--white">{{ $admin->name ?? '' }}</h4>
                        </div>
                    </div>
                    <ul class="list-group">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            @lang('Name')
                            <span class="fw-bold">{{ $admin->name ?? '' }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            @lang('Username')
                            <span class="fw-bold">{{ $admin->username ?? '' }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            @lang('Email')
                            <span class="fw-bold">{{ $admin->email ?? '' }}</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-lg-9 col-md-9 mb-30">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-4 border-bottom pb-2">@lang('Change Password')</h5>
                    <form action="{{ route('admin.password.update') }}" method="POST" class="disableSubmission"
                        enctype="multipart/form-data">
                        @csrf
                        <div class="form-group" style="position: relative;">
                            <label>@lang('Password')</label>
                            <input class="form-control" type="password" name="old_password" required>
                            <span onclick="togglePasswordVisibility(this,'#password')" class="eye"><i
                                    class="fa-solid fa-eye"></i></span>
                        </div>
                        <div class="form-group" style="position: relative;">
                            <label>@lang('New Password')</label>
                            <input class="form-control" type="password" name="password" required>
                            <span onclick="togglePasswordVisibility(this,'#password')" class="eye"><i
                                    class="fa-solid fa-eye"></i></span>
                        </div>
                        <div class="form-group" style="position: relative;">
                            <label>@lang('Confirm Password')</label>

                            <style>
                                .eye {
                                    font-size: 18px;
                                    position: absolute;
                                    top: 33px;
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

                            <input class="form-control" type="password" name="password_confirmation" required>
                            <span onclick="togglePasswordVisibility(this,'#password')" class="eye"><i
                                    class="fa-solid fa-eye"></i></span>
                        </div>
                        <button type="submit" class="btn btn--primary w-100 btn-lg h-45">@lang('Submit')</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('breadcrumb-plugins')
    <a href="{{ route('admin.profile') }}" class="btn btn-sm btn-outline--primary">
        <i class="las la-user"></i> @lang('Profile Setting')
    </a>
@endpush

@push('style')
    <style>
        .list-group-item:first-child {
            border-top-left-radius: unset;
            border-top-right-radius: unset;
        }
    </style>
@endpush