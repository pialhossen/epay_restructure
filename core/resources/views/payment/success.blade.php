@extends($activeTemplate . 'layouts.frontend')

@section('content')
<div class="container" style="margin-top: 15px">
    <div class="alert alert-success">
        <h4>Payment Successful</h4>
        <p>{{ $message }}</p>
    </div>
</div>
@endsection
