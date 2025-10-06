@extends('admin.layouts.app')

@section('panel')
    <div class="container">
        <h2>Block List</h2>

        <form method="GET" class="row mb-3">
            <div class="col-md-8">
                <input type="text" name="q" class="form-control" value="{{ request('q') }}"
                       placeholder="Search by block name or line data...">
            </div>
            <div class="col-md-4">
                <button class="btn btn-primary">Search</button>
                <a href="{{ route('admin.blocks.index') }}" class="btn btn-secondary">Clear</a>
            </div>
        </form>

        <a href="{{ route('admin.blocks.create') }}" class="btn btn-success mb-3">Add New Block</a>

        @forelse($blocks as $block)
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between">
                    <strong>{{ $block->name }}</strong>
                    <div>
                        <a href="{{ route('admin.blocks.show', $block) }}" class="btn btn-info btn-sm">Show</a>
                        <a href="{{ route('admin.blocks.edit', $block) }}" class="btn btn-warning btn-sm">Edit</a>
                        <form action="{{ route('admin.blocks.destroy', $block) }}" method="POST" style="display:inline">
                            @csrf @method('DELETE')
                            <button class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?')">Delete
                            </button>
                        </form>
                    </div>
                </div>
                <ul class="list-group list-group-flush">
                    @foreach($block->blockLines as $line)
                        <li class="list-group-item">{{ $line->data }}</li>
                    @endforeach
                </ul>
            </div>
        @empty
            <div class="alert alert-warning">No blocks found.</div>
        @endforelse
        <div class="d-flex justify-content-center mt-4">
            {{ $blocks->links() }}
        </div>

    </div>
@endsection
