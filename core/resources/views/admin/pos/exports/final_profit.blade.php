<table>
    <thead>
        <tr>
            <th>Currency</th>
            <th>CS Sent Avg Rate (CSAR)</th>
            <th>Currency Reserved (CR)</th>
            <th>Currency Total (CR * CSAR)</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($transactions as $currency => $data)
            <tr>
                <td>
                    <span class="fw-bold">{{ $currency }}</span>
                </td>
                <td>
                    <span class="fw-bold">{{ $data['customer_avg_sent_rate'] }}</span>
                </td>
                <td>
                    <span class="fw-bold">{{ $data['currency_reserved'] }}</span>
                </td>
                <td>
                    <span class="fw-bold">{{ $data['currency_total'] }}</span>
                </td>
            </tr>
        @endforeach
        <tr>
            <td></td>
            <td></td>
            <td><span class="fw-bold">Currency Total: </span></td>
            <td><span class="fw-bold">{{ $currencyProfit }}</span></td>
        </tr>
        <tr>
            <td></td>
            <td></td>
            <td><span class="fw-bold">Total User Balance: </span></td>
            <td><span class="fw-bold">-{{ $totalUserBalance }}</span></td>
        </tr>
        <tr>
            <td></td>
            <td></td>
            <td><span class="fw-bold">Final Profit: </span></td>
            <td><span class="fw-bold">{{ $totalProfit }}</span></td>
        </tr>
    </tbody>
</table>