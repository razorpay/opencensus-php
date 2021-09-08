<!DOCTYPE html>
<html lang="en-US">
<head>
    <meta charset="utf-8">
</head>
<body>
<div>
    <div>
        Hey {{{$merchant['name']}}},
    </div>

    <br />

    <div>
        We have received your activation details but there are some clarifications we need regarding your submission. Please visit your dashboard and make the necessary changes.
    </div>

    <br />

    <div>
        <strong
        >Please fix these issues as soon as possible to complete your activation
            process:</strong
        >
    </div>

    <br />

    <table style="width:100%;border:1px solid black;border-collapse:collapse;" cellpadding="10">
        <thead>
        <tr>
            <th
                class="issue"
                style="border:1px solid black;border-collapse:collapse;padding:10px;width:40%;text-align: left;" width="30%"
            >
                Document or Issue
            </th>
            <th
                class="fix"
                style="border:1px solid black;border-collapse:collapse;padding:10px;width:60%; text-align: left;" width="70%"
            >
                How can you fix it?
            </th>
        </tr>
        </thead>
        <tbody>
        @if(array_key_exists('fields', $clarification_reason))
            @foreach($clarification_reason['fields'] as $fields)
                @foreach($fields as $meta_data)
                    <tr>
                        <td style="border:1px solid black;border-collapse:collapse;padding:15px;" width="30%" valign="top">
                            {{{$meta_data['display_name']}}}
                        </td>
                        <td style="border:1px solid black;border-collapse:collapse;pading:10px;" width="70%">
                            {{{$meta_data['reason_description']}}}
                        </td>
                    </tr>
                @endforeach
            @endforeach
        @endif
        @if(array_key_exists('documents', $clarification_reason))
            @foreach($clarification_reason['documents'] as $documents)
                @foreach($documents as $meta_data)
                    <tr>
                        <td style="border:1px solid black;border-collapse:collapse;padding:15px;" width="30%" valign="top">
                            {{{$meta_data['display_name']}}}
                        </td>
                        <td style="border:1px solid black;border-collapse:collapse;padding:10px;" width="70%">
                            {{{$meta_data['reason_description']}}}
                        </td>
                    </tr>
                @endforeach
            @endforeach
        @endif
        </tbody>
    </table>

    <br />

    <div>
        Best regards,
        <br />
        Razorpay Partnerships Team
    </div>
</div>

<footer style="text-align:center; margin-top: 10px; font-size: 12px;">For more information <a href="{{ 'https://' . $merchant['org']['hostname'] . '/knowledgebase' }}">click here</a>.
    If you still have queries you can raise a support ticket <a href="{{ 'https://' . $merchant['org']['hostname'] . '/support/#request' }}">here</a>.</footer>
</body>
</html>
