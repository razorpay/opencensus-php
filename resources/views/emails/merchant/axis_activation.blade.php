<!DOCTYPE html>
<html lang="en-US">
<head>
    <meta charset="utf-8">
</head>
<body>
<p>Hi {{{$merchant['name']}}},</p>

<h2>Your Axis Rupay account has been created.</h2>

<div>
    <p>Please note you will have received another email to create your password.</p>

    <p>To login, visit <a href="{{$merchant['dashboard_url']}}">{{{$merchant['dashboard_url']}}}</a> and use your email id and created password.</p>

    <p>You can generate api keys from your dashboard.</p>
</div>

<div>
    <p>
        Regards,<br>
        Axis Rupay Team
    </p>

</div>
</body>
</html>
