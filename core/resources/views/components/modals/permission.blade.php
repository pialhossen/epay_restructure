@props(['permission' => null])
@php
    use App\Models\Permission;
    $permissions = Permission::with('childs')->whereNull('parent_id')->get();
@endphp
<div id="{{ $permission? "edit_parmission_".$permission->id: "addRole" }}" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><span class="type"></span> <span>@lang('{{ $permission? "Edit": "Add" }} Permissions')</span></h5>
                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="las la-times"></i>
                </button>
            </div>
            <form action="{{ $permission? route('admin.employees.permissions.update',[$permission->id]): route('admin.employees.permissions.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label>@lang('Name')</label>
                        <div class="input-group mb-2">
                            <input type="text" name="name" value="{{ old('name',$permission? $permission->name: '') }}" class="form-control" placeholder="@lang('Please enter the permission name')" required>
                        </div>
                        @if(count($permissions))
                        <div class="input-group">
                            <select name="parent_id" id="" class="form-control">
                                <option value="">No Parent Permission</option>
                                @foreach ($permissions as $parent_permission)
                                <option value="{{ $parent_permission->id }}"@if($permission) @selected($parent_permission->id == $permission->parent_id) @endif>{{ $parent_permission->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        @endif
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn--primary h-45 w-100">@lang('Submit')</button>
                </div>
            </form>
        </div>
    </div>
</div>