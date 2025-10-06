
@extends('admin.layouts.app')

@section('panel')
<div class="container">
    <h2>{{ $block->name }}</h2>
    <ul class="list-group">
        @foreach($block->blockLines as $line)
            <li class="list-group-item">{{ $line->data }}</li>
        @endforeach
    </ul>
    <a href="{{ route('admin.blocks.index') }}" class="btn btn-secondary mt-3">Back</a>
</div>
@endsection
