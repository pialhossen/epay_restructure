@extends('admin.layouts.app')
@php
$role_permissions =  $role->permissions()->pluck('id')->toArray();
@endphp
@section('panel')
    <div class="row">
        <div class="col-12">

            <div class="card mt-30">
                <div class="card-header">
                    <h5 class="card-title mb-0">@lang('Information of') {{ $role->name }} Role</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.employee.roles.update', $role->id) }}" method="POST"
                        enctype="multipart/form-data" class="disableSubmission">
                        @csrf
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label>@lang('Name')</label>
                                    <input class="form-control" type="text" name="name" required
                                        value="{{ $role->name }}">
                                </div>
                            </div>
                            <h3>All Permissions</h3>
                            @foreach ($permissions as $permission)
                            <div class="form-group">
                                <label class="form-control-label">@lang($permission->name)</label>
                                <input type="checkbox" data-width="100%" data-height="50" data-onstyle="-success"
                                data-offstyle="-danger" data-bs-toggle="toggle" data-on="@lang('True')"
                                data-off="@lang('False')" name="permissions[{{ $permission->id }}]" class="parent-permission" @if(in_array($permission->id, $role_permissions))checked @endif>

                                @foreach ($permission->childs as $child)
                                <div style="margin-left: 10%;">
                                    <label class="form-control-label">@lang($child->name)</label>
                                    <input type="checkbox" data-width="100%" data-height="50" data-onstyle="-success"
                                    data-offstyle="-danger" data-bs-toggle="toggle" data-on="@lang('True')"
                                    data-off="@lang('False')" name="permissions[{{ $child->id }}]" class="child-permission" @if(in_array($child->id, $role_permissions))checked @endif>
                                </div>
                                @endforeach
                            </div>
                            @endforeach
                            <div class="col-md-12">
                                <button type="submit" class="btn btn--primary w-100 h-45">@lang('Submit')
                                </button>
                            </div>
                        </div>
                    </form>
                    <div class="mt-3" >
                        <button type="submit" class="btn btn--danger h-45 w-100" data-bs-toggle="modal" data-bs-target="#delete_role">@lang('Delete')</button>
                    </div>
                </div>
            </div>
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
            <form action="{{ route('admin.employee.roles.delete', $role->id) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <h4>@lang("Are You Sure You Want to Delete ($role->name)")</h4>
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
