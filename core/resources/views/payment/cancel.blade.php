@extends($activeTemplate . 'layouts.frontend')

@section('content')
<div class="container" style="margin-top: 15px">
    <div class="alert alert-danger">
        <h4>Payment Canceled</h4>
        <p>{{ $message }}</p>
    </div>
</div>
@endsection
