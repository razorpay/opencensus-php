<h2>Comments</h2>
<table border='1' style='border-collapse:collapse'>
    <thead>
    <tr>
        <th>RZP Ref No</th>
        <th>MID</th>
        <th>Merchant Business Name</th>
        <th>Status</th>
        <th>SubStatus</th>
        <th>Comment</th>
        <th>Comment by (Team)</th>
        <th>Commented At</th>
        <th>More Details</th>
    </tr>
    </thead>
    <tbody>
    @foreach($comments as $comment)
        <tr>
            <td>{{{$comment['bank_reference_number']}}}</td>
            <td>{{{$comment['merchant_id']}}}</td>
            <td>{{{$comment['business_name']}}}</td>
            <td>{{{$comment['status']}}}</td>
            <td>{{{$comment['sub_status']}}}</td>
            <td>{{{$comment['comment']}}}</td>
            <td>{{{$comment['source_team']}}}</td>
            <td>{{{$comment['created_at']}}}</td>
            <td>
                <a href="{{{$comment['admin_dashboard_link']}}}">View More Info</a>
            </td>
        </tr>
    @endforeach
    </tbody>
</table>
