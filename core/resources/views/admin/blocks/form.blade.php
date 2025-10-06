
@extends('admin.layouts.app')

@section('panel')
<div class="container">
    <h2>{{ isset($block) ? 'Edit Block' : 'Create Block' }}</h2>

    <form method="POST" action="{{ isset($block) ? route('admin.blocks.update', $block) : route('admin.blocks.store') }}">
        @csrf
        @if(isset($block))
            @method('PUT')
        @endif

        <div class="mb-3">
            <label for="name">Block Name</label>
            <input type="text" name="name" class="form-control" value="{{ old('name', $block->name ?? '') }}" required />
        </div>

        <div class="mb-3">
            <label>Block Lines</label> <i style="font-size: smaller">Here you can put email,mobile,name,bank account, anything that you want.</i>
            <div id="block-lines">
                @if(isset($block))
                    @foreach($block->blockLines as $line)
                        <div class="d-flex mb-2">
                            <input type="hidden" name="line_ids[]" value="{{ $line->id }}">
                            <input type="text" name="block_lines[]" class="form-control me-2" value="{{ $line->data }}" required>
                            <button type="button" class="btn btn-danger" onclick="removeLine(this)">×</button>
                        </div>
                    @endforeach
                @else
                    <div class="d-flex mb-2">
                        <input type="hidden" name="line_ids[]" value="">
                        <input type="text" name="block_lines[]" class="form-control me-2" required>
                        <button type="button" class="btn btn-danger" onclick="removeLine(this)">×</button>
                    </div>
                @endif
            </div>

            <button type="button" class="btn btn-sm btn-secondary mt-2" onclick="addLine()">Add Line</button>
        </div>

        <button class="btn btn-primary">{{ isset($block) ? 'Update' : 'Save' }}</button>
    </form>
</div>

<script>
    function addLine() {
        const container = document.getElementById('block-lines');
        const div = document.createElement('div');
        div.className = 'd-flex mb-2';
        div.innerHTML = `
            <input type="hidden" name="line_ids[]" value="">
            <input type="text" name="block_lines[]" class="form-control me-2" required>
            <button type="button" class="btn btn-danger" onclick="removeLine(this)">×</button>
        `;
        container.appendChild(div);
    }

    function removeLine(button) {
        button.parentElement.remove();
    }
</script>
@endsection
