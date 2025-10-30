@extends($activeTemplate . 'layouts.master')
@section('content')
    <style>
        .table-container {
            overflow-x: auto;
            position: relative;
        }

        .data-table {
            border-collapse: collapse;
        }

        .data-table th,
        .data-table td {
            white-space: nowrap;
        }

        .sticky-col {
            position: sticky !important;
            right: 0;
            z-index: 2; /* higher than other cells */
        }
        .data-table th {
            position: sticky;
            top: 0;
            z-index: 3;
        }
    </style>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-12">
                <div class="card custom--card">
                    @if (!$statements->isEmpty())
                        <div class="card-body p-0 table-container">
                            <div class="card-header p-2 d-flex justify-content-between">
                                <h5>{{ auth()->user()->username }}'s Current balance = {{ number_format(auth()->user()->balance,2) }}</h5>
                                <a href="{{ route('user.statement.balance.download') }}" class="btn btn-sm btn-success">Download</a>
                            </div>
                            @php
                                $lastSegment = request()->segment(count(request()->segments()));
                            @endphp
                            <table class="table custom--table table-responsive--md data-table">
                                <thead>
                                    <tr>
                                        <th>@lang('Date And Time')</th>
                                        <th>@lang('Amount')</th>
                                        <th>@lang('Cause')</th>
                                        <th>@lang('By')</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($statements as $statement)
                                        <tr>
                                            <td>{{ $statement->created_at->format('d/m/y h:i:s A') }}</td>
                                            <td>{{ $statement->amount }}</td>
                                            <td>{{ $statement->via }}</td>
                                            <td>{{ $statement->admin->name ?? '-' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        @include($activeTemplate . 'partials.empty', [
                            'message' => 'No exchange found',
                        ])
                    @endif
                </div>    
                {{ $paginator->links() }}
            </div>
        </div>
    </div>
@endsection