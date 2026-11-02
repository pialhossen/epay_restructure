<table>
    <thead>
        <tr>
            <th>Exchange ID</th>
            <th>Transaction Type</th>
            <th>Sent</th>
            <th>Received</th>
            <th>Sending Amount</th>
            <th>Sending currency symbol</th>
            <th>Receiving Amount</th>
            <th>Receiving currency symbol</th>
            <th>Date</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        @foreach($transactions as $transaction)
            <tr>
                <td>{{ $transaction->exchange_id }}</td>
                <td>{{ $transaction->transaction_type }}</td>
                <td>{{ @$transaction->sendCurrency->name }}</td>
                <td>{{ @$transaction->receivedCurrency->name }}</td>
                <td>
                    {{ $transaction->sending_amount }}
                </td>
                <td>
                    {{ @$transaction->sendCurrency->cur_sym }}
                </td>
                <td>
                    {{ $transaction->receiving_amount }}
                </td>
                <td>
                    {{ @$transaction->receivedCurrency->cur_sym }}
                </td>
                <td>
                    <span>{{ showDateTime(@$transaction->created_at) }}</span>
                    <span class="text--base">{{ diffForHumans(@$transaction->created_at) }}</span>
                </td>
                <td>
                    @if($transaction->status == 1)
                        APPROVED
                    @elseif($transaction->status == 2)
                        PENDING
                    @elseif($transaction->status == 3)
                        REFUNDED
                    @elseif($transaction->status == 4)
                        HOLD
                    @elseif($transaction->status == 5)
                        PROCESSING
                    @elseif($transaction->status == 9)
                        CANCELLED
                    @elseif($transaction->status == 0)
                        INITIATED
                    @else
                        {{ $transaction->status }}
                    @endif
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
