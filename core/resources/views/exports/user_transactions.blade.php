<table>
    <thead>
        <tr>
            <th>Exchange ID</th>
            <th>Sent</th>
            <th>Receied</th>
            <th>Sending Amount</th>
            <th>Sending currency symbol</th>
            <th>Receiving Amount</th>
            <th>Receiving currency symbol </th>
            <th>Date</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        @foreach($transactions as $transaction)
            <tr>
                <td>{{ $transaction->exchange_id }}</td>
                <td>{{ $transaction->sendCurrency->name }}</td>
                <td>{{ $transaction->receivedCurrency->name }}</td>
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
                <td>{{ $transaction->status == 1 ? 'APPROVED' : ($transaction->status == 9 ? 'CANCELLED' : 'PENDING') }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
