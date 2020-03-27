<!DOCTYPE html>
<html lang="en-US">
<head>
    <meta charset="utf-8">
</head>
<body>
<p>Hi {{{$merchant['name']}}},</p>

<div>
    <dl>
        <dd>
            Two step authentication is now {{{$merchant['second_factor_auth'] ? "enabled" : "disabled"}}} for your account -
            {{{$merchant['name']}}} with account ID {{{$merchant['id']}}} using mobile number
            {{{$user['contact_mobile']}}}.
            This will ensure that all the users accessing this account validates their identity by providing the OTP sent to their registered mobile number.
        </dd><br>
        <dd>
            You can {{{$merchant['second_factor_auth'] ? "disable" : "enable"}}} this anytime by navigating to the ‘My Account’ -> ‘Manage team’ section within the Razorpay dashboard.
        </dd><br>
        <dd>
            You can find more details about two step authentication <here>
        </dd>
    </dl>

    <dd>For any further queries or clarifications, feel free to reach out to us by visiting - </dd>
    <dd><a href="https://razorpay.com/support" target="_blank">https://razorpay.com/support</a></dd>
</div>

<div>
    <p>
        Regards,<br>
        Team Razorpay
    </p>
</div>
</body>
</html>
