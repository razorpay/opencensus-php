<!DOCTYPE html>
<html lang="en-US">
<head>
    <meta charset="utf-8">
</head>

<body>

<div>
    <div>
        Hey,
    </div>

    <br />

    <div>
        You have Escalations!
    </div>

    <table style="width:100%;border:1px solid black;border-collapse:collapse;" cellpadding="10">
        <thead>
        <tr>
            <th
                class="issue"
                style="border:1px solid black;border-collapse:collapse;padding:10px;width:40%;text-align: left;" width="30%"
            >
                Merchant ID
            </th>
            <th
                class="issue"
                style="border:1px solid black;border-collapse:collapse;padding:10px;width:40%;text-align: left;" width="30%"
            >
                ACTIVATION STATUS
            </th>
            <th
                class="fix"
                style="border:1px solid black;border-collapse:collapse;padding:10px;width:60%; text-align: left;" width="70%"
            >
                Workflow URL
            </th>
            <th
                class="fix"
                style="border:1px solid black;border-collapse:collapse;padding:10px;width:60%; text-align: left;" width="70%"
            >
                Business Type
            </th>
        </tr>
        </thead>
        <tbody>
            @foreach($merchants as $merchant)
                    <tr>
                        <td style="border:1px solid black;border-collapse:collapse;padding:15px;" width="30%" valign="top">
                            {{{$merchant['merchantId']}}}
                        </td>
                        <td style="border:1px solid black;border-collapse:collapse;padding:15px;" width="30%" valign="top">
                            {{{$merchant['activationStatus']}}}
                        </td>
                        <td style="border:1px solid black;border-collapse:collapse;pading:10px;" width="70%">
                            @if(empty($merchant['workflowUrl']) === false)
                            <a href="{{$merchant['workflowUrl']}}">Workflow</a>
                            @endif
                        </td>
                        <td style="border:1px solid black;border-collapse:collapse;padding:15px;" width="30%" valign="top">
                            {{{$merchant['businessType']}}}
                        </td>
                    </tr>
            @endforeach
        </tbody>
    </table>
    <br />
</div>

</body>

</html>
