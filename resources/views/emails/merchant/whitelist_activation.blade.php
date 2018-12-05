<!DOCTYPE html>
<html lang="en-US">
<head>
    <meta charset="utf-8">
</head>
<body>
    <p>Hi {{{$merchant['name']}}},</p>

    <h2>KYC verification for {{{$merchant['org']['business_name']}}} is complete</h2>

    <div>
        <p>Settlements are enabled for you</p>

        <p>You will now start receiving settlements for the payments made to your business. Your settlement schedule is (T+3) working days.
            The balance eligible for settlements will be settled in the next cycle.</p>
        <p><a href="https://dashboard.razorpay.com" target="_blank">Go to dashboard</a></p>
    </div>

<div>
    <p>
        Regards,<br>
        Team {{{$merchant['org']['business_name']}}}
    </p>

</div>
</body>
</html>
