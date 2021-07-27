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
        Your Acknowledgement Report for Today!
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
                MERCHANT NAME
            </th>
            <th
                class="fix"
                style="border:1px solid black;border-collapse:collapse;padding:10px;width:60%; text-align: left;" width="70%"
            >
                ACTIVATION STATUS
            </th>
            <th
                class="fix"
                style="border:1px solid black;border-collapse:collapse;padding:10px;width:60%; text-align: left;" width="70%"
            >
                CREATED AT
            </th>
            <th
                class="fix"
                style="border:1px solid black;border-collapse:collapse;padding:10px;width:60%; text-align: left;" width="70%"
            >
                ACTIVATED AT
            </th>
        </tr>
        </thead>
        <tbody>
            @foreach($merchants as $merchant)
                    <tr>
                        <td style="border:1px solid black;border-collapse:collapse;padding:15px;" width="30%" valign="top">
                            {{{$merchant['merchant_id']}}}
                        </td>
                        <td style="border:1px solid black;border-collapse:collapse;padding:15px;" width="30%" valign="top">
                            {{{$merchant['contact_name']}}}
                        </td>
                        <td style="border:1px solid black;border-collapse:collapse;padding:15px;" width="30%" valign="top">
                            {{{$merchant['activation_status']}}}
                        </td>
                        <td style="border:1px solid black;border-collapse:collapse;padding:15px;" width="30%" valign="top">
                            {{{$merchant['created_at']}}}
                        </td>
                        <td style="border:1px solid black;border-collapse:collapse;padding:15px;" width="30%" valign="top">
                            {{{$merchant['activated_at']}}}
                        </td>
                    </tr>
            @endforeach
        </tbody>
    </table>
    <br />
</div>

</body>

</html>
