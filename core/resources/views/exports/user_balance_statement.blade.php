<table>
    <thead>
        <tr>
            <th>Date And Time</th>
            <th>Amount</th>
            <th>Cause</th>
            <th>By</th>
        </tr>
    </thead>
    <tbody>
        @foreach($statements as $statement)
            <tr>
                <td>{{ $statement->created_at->format('d/m/y h:i:s A') }}</td>
                <td>{{ $statement->amount }}</td>
                <td>{{ $statement->via }}</td>
                <td>{{ $statement->admin->name ?? '-' }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
