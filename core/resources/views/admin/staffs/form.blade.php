@extends('admin.layouts.app')
@section('panel')
    <div class="row">
        <div class="col-12">

            <div class="card mt-30">
                <div class="card-header">
                    <h5 class="card-title mb-0">@lang('Change of') Role</h5>
                </div>
                <div class="card-body">
                    <form action="{{ isset($user)? route('admin.employee.staffs.update', [$user->id]): route('admin.employee.staffs.store') }}" method="POST"
                        enctype="multipart/form-data" class="disableSubmission">
                        @csrf
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>@lang('Staff Roles')</label>
                                    <select name="roles[]" class="form-control select2" aria-placeholder="Select a role" multiple="multiple" required>
                                            @foreach ($roles as $role)
                                            <option value="{{ $role->id }}" @selected(in_array($role->id, $currentRolesId))>
                                                {{ $role->name }}
                                            </option>
                                            @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>@lang('Name')</label>
                                    <input class="form-control" type="text" name="name" required
                                        value="{{ old('name', isset($user)? $user->name: '') }}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>@lang('Username - No Spacial Character, No Upppercase Character, NO Space')</label>
                                    <input class="form-control" type="text" name="username" required
                                        value="{{ old('username',isset($user)? $user->username: '')}}" placeholder="Username (No Spacial Character, No Upppercase Character, NO Space)">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>@lang('Email')</label>
                                    <input class="form-control" type="text" name="email" required
                                    value="{{ old('email',isset($user)? $user->email: '')}}">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>@lang('Image')</label>
                                    <x-image-uploader image="{{ isset($user)? $user->image: ''}}" class="w-100" type="adminProfile"
                                        :required=false />
                                </div>
                            </div>
                            <div class="col-md-12 mb-2">
                                <div class="row">
                                    <div class="col-md-2">
                                        <label class="form-control-label">@lang('Activate')</label>
                                        <input type="checkbox" data-width="100%" data-height="50" data-onstyle="-success"
                                        data-offstyle="-danger" data-bs-toggle="toggle" data-on="@lang('Activated')"
                                        data-off="@lang('Deactivated')" name="is_active" value="1" class="child-permission" @checked(isset($user)? $user->is_active: '')>
                                    </div>
                                </div>
                            </div>
                            @if(!isset($user))
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>@lang('New Password')</label>
                                    <input class="form-control" type="paaword" name="password" required
                                        >
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>@lang('Confirm Password')</label>
                                    <input class="form-control" type="paaword" name="password_confirmation" required
                                        >
                                </div>
                            </div>
                            @endif
                            <div class="col-md-12">
                                <button type="submit" class="btn btn--primary w-100 h-45">@lang('Submit')
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            @if(isset($user))
            <div class="card mt-30">
                <div class="card-header">
                    <h5 class="card-title mb-0">@lang('Change Staff Password')</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.employee.staffs.password', [$user->id]) }}" method="POST"
                        enctype="multipart/form-data" class="disableSubmission">
                        @csrf
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>@lang('New Password')</label>
                                    <input class="form-control" type="paaword" name="password" required
                                        >
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>@lang('Confirm Password')</label>
                                    <input class="form-control" type="paaword" name="password_confirmation" required
                                        >
                                </div>
                            </div>
                            <div class="col-md-12">
                                <button type="submit" class="btn btn--primary w-100 h-45">@lang('Submit')
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            @endif
        </div>
    </div>


<div id="delete_role" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><span class="type"></span> <span>@lang('Delete Role')</span></h5>
                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="las la-times"></i>
                </button>
            </div>
            <form action="" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <h4>@lang("Are You Sure You Want to Delete")</h4>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn--danger h-45 w-100">@lang('Submit')</button>
                    <button type="button" data-bs-dismiss="modal" aria-label="Close" class="btn btn--primary h-45 w-100 close">@lang('Cancel')</button>
                </div>
            </form>
        </div>
    </div>
</div>

<x-modals.permission />
@endsection
@push('breadcrumb-plugins')
    <button data-bs-toggle="modal" data-bs-target="#addPermission" class="btn btn-sm btn-outline--primary">
        <i class="las la-plus-circle"></i>@lang('Create New Permission')
    </button>
@endpush

@push('script')
    <script>
        (function($) {
            "use strict"

            let mobileElement = $('.mobile-code');
            $('select[name=country]').on('change', function() {
                mobileElement.text(`+${$('select[name=country] :selected').data('mobile_code')}`);
            });

            $('.bal-btn').on('click', function() {
                $('.balanceAddSub')[0].reset();
                var act = $(this).data('act');
                $('#addSubModal').find('input[name=act]').val(act);
                if (act == 'add') {
                    $('.type').text('Add');
                } else {
                    $('.type').text('Subtract');
                }
            });

            $('.parent-permission').on('change', function (event) {
               const current_permission = event.target 
               const parent_div = current_permission.parentElement.parentElement
               const child_permissions = parent_div.querySelectorAll('.child-permission');
               child_permissions.forEach(child_permission => {
                    const child_div = child_permission.parentElement;

                    if (current_permission.checked) {
                        child_permission.checked = true;
                        child_div.classList.remove('btn--danger', 'off');
                        child_div.classList.add('btn--success');
                    } else {
                        child_permission.checked = false;
                        child_div.classList.remove('btn--success');
                        child_div.classList.add('btn--danger', 'off');
                    }
               })
            });


        })(jQuery);
    </script>
@endpush
