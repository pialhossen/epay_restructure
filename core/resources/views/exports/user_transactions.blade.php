<table>
    <thead>
        <tr>
            <th>Exchange ID</th>
            <th>Sent</th>
            <th>Receied</th>
            <th>Amount</th>
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
                    {{ number_format($transaction->sending_amount, $transaction->sendCurrency->show_number_after_decimal) }}
                    &nbsp;&nbsp;
                    {{ __(@$transaction->sendCurrency->cur_sym) }}
                    &nbsp;&nbsp;
                    -
                    &nbsp;&nbsp;
                    {{ number_format($transaction->receiving_amount, $transaction->receivedCurrency->show_number_after_decimal) }}
                    &nbsp;&nbsp;
                    {{ __(@$transaction->receivedCurrency->cur_sym) }}
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
