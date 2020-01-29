<!DOCTYPE html>
<html lang="en-US">
<head>
    <meta charset="utf-8">
    <style type="text/css">
    .img-container {
        height: 200px;
    }
    .merchant-details {
        padding: 20px;
    }
    </style>
</head>
<body>
<p>Hi,</p>

<div>
    <p>Merchant {{{$merchant_data['name']}}} has enabled ES Scheduled.</p>
    <p>Here are his details:</p>
    <ul>
        <li>Merchant Id:     {{{$merchant_data['id']}}}</li>
        <li>Merchant OrgId:  {{{$merchant_data['org_id']}}}</li>
    </ul>

</div>

<div>
    <p>
        Regards,<br>
        Team Razorpay
    </p>
</div>
</body>
</html>
