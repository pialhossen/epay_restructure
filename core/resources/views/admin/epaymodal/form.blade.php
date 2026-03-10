@extends('admin.layouts.app')

@section('panel')
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card b-radius--10">
                <div class="card-body">
                    <h5 class="mb-4">{{ $pageTitle }}</h5>

                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @php
                        $isEdit = isset($item);
                    @endphp

                    <form
                        action="{{ $isEdit ? route('admin.epaymodal.update', $item->id) : route('admin.epaymodal.store') }}"
                        method="POST" enctype="multipart/form-data">
                        @csrf
                        @if ($isEdit)
                            @method('PUT')
                        @endif

                        <div class="form-group mb-3">
                            <label>@lang('Title')</label>
                            <input type="text" name="title" class="form-control"
                                value="{{ old('title', $item->title ?? '') }}" maxlength="255" />
                        </div>

                        <div class="form-group mb-3">
                            <label>@lang('Description')</label>
                            <textarea name="description" class="form-control" rows="4">{{ old('description', $item->description ?? '') }}</textarea>
                        </div>

                        <div class="form-group mb-3">
                            <label>@lang('Button Name')</label>
                            <input type="text" name="button_name" class="form-control"
                                value="{{ old('button_name', $item->button_name ?? '') }}" maxlength="255" />
                        </div>

                        <div class="form-group mb-3">
                            <label>@lang('Button Link')</label>
                            <input type="text" name="button_link" class="form-control" value="{{ old('button_link', $item->button_link ?? '') }}" maxlength="255" />
                        </div>

                        <div class="form-group mb-3">
                            <label>@lang('Image')</label><br>
                            @if ($isEdit && $item->image_link)
                                <img src="{{ asset('storage/' . $item->image_link) }}" alt="Current Image" width="100"
                                    height="100" style="object-fit: cover; margin-bottom:10px;">
                                <br>
                            @endif
                            <input type="file" name="image_link" class="form-control" accept="image/*" />
                        </div>

                        <div class="form-group mb-3">
                            <label>@lang('Status')</label>
                            <select name="status" class="form-control" required>
                                <option value="1" {{ old('status', $item->status ?? 1) == 1 ? 'selected' : '' }}>
                                    @lang('Active')</option>
                                <option value="0" {{ old('status', $item->status ?? 1) == 0 ? 'selected' : '' }}>
                                    @lang('Inactive')</option>
                            </select>
                        </div>

                        <div class="form-group mb-3">
                            <label>@lang('Remarks')</label>
                            <textarea name="remarks" class="form-control" rows="3">{{ old('remarks', $item->remarks ?? '') }}</textarea>
                        </div>

                        <button type="submit" class="btn btn--primary">@lang($isEdit ? 'Update' : 'Create')</button>
                        <a href="{{ route('admin.epaymodal.index') }}" class="btn btn--dark">@lang('Cancel')</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
