<table>
    <thead>
        <tr>
            <th>Exchange ID</th>
            <th>Description</th>
            <th>Send</th>
            <th>Received</th>
            <th>Amount</th>
            <th>Date</th>
            <th>Balance</th>
        </tr>
    </thead>
    <tbody>
        @foreach($statements as $statement)
            <tr>
                <td>{{ $statement->exchange->exchange_id }}</td>
                <td>{{ $statement->via }}</td>
                <td>
                    {{ $statement->exchange->sendCurrency->name }}
                </td>
                <td>
                    {{ $statement->exchange->receivedCurrency->name }}
                </td>
                <td>
                    {{ number_format($statement->exchange->sending_amount + $statement->exchange->sending_charge, $statement->exchange->sendCurrency->show_number_after_decimal) }}
                    {{ __(@$statement->exchange->sendCurrency->cur_sym) }}
                    ->
                    {{ number_format($statement->exchange->receiving_amount - $statement->exchange->receiving_charge, $statement->exchange->receivedCurrency->show_number_after_decimal) }}
                    {{ __(@$statement->exchange->receivedCurrency->cur_sym) }}
                </td>
                <td>
                    <div class="d-flex flex-column justify-content-end">
                        <span>{{ showDateTime(@$statement->created_at) }}</span>
                        <span class="text--base">{{ diffForHumans(@$statement->created_at) }}</span>
                    </div>
                </td>
                <td>
                    {{ number_format($statement->before, $statement->exchange->receivedCurrency->show_number_after_decimal) }}
                    {{ __(@$statement->exchange->receivedCurrency->cur_sym) }}
                    ->
                    {{ number_format($statement->after, $statement->exchange->receivedCurrency->show_number_after_decimal) }}
                    {{ __(@$statement->exchange->receivedCurrency->cur_sym) }}
                </td>
            </tr>
        @endforeach
    </tbody>
</table>