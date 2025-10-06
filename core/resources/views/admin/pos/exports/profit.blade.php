<table>
    <thead>
        <tr>
            <th>Currency</th>
            <th>Previous Currency Reserved (CR) + Customer Sent (CS)</th>
            <th>Previous Currency Total (CR * CSAR) + Receied Any (CR)</th>
            <th>Customer Sent Avg (CR / CS = CSA)</th>
            <th>Customer Received (R_CR) + Hidden charge (HC) = R_CRHC</th>
            <th>Sent Any (R_CS)</th>
            <th>Customer Received Avg (R_CS / R_CR = CRA)</th>
            <th>Avg Profit Rate (CRA - CSA = APR)</th>
            <th>Total Profit (R_CR * APR)</th>
        </tr>
    </thead>
    <tbody>
        @foreach($transactions as $currency => $row)
            <tr>
                <td>{{ $currency }}</td>
                <td>{{ $row['last_day_reserved'] }} + {{ $row['customer_sent_amount_by_this_currency'] }} = {{ $row['sent_profit'] }}</td>
                <td>{{ $row['last_day_currency_total'] }} + {{ $row['customer_received_amount_by_any_currency'] }} = {{ $row['received_profit'] }}</td>
                <td>{{ $row['customer_avg_sent_rate'] }}</td>
                <td>{{ $row['customer_received_amount_by_this_currency'] }} + {{ $row['hidden_charge_amount'] }} = {{ $row['customer_received_amount_by_this_currency'] + $row['hidden_charge_amount'] }}</td>
                <td>{{ $row['customer_sent_amount_by_any_currency'] }}</td>
                <td>{{ $row['customer_avg_received_rate'] }}</td>
                <td>{{ $row['avg_profit_rate'] }}</td>
                <td>{{ $row['total_profit'] }}</td>
            </tr>
        @endforeach
        <tr>
            <td colspan="8" style="font-weight: bold; text-align: right;">Total</td>
            <td style="font-weight: bold;">{{ $totalProfitAll }}</td>
        </tr>
    </tbody>
</table>
