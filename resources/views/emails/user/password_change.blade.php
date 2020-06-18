<!DOCTYPE html>
<html lang="en-US">
<head>
    <meta charset="utf-8">
</head>
<body>

<div>
    <p>Your password has been changed successfully for your {{$org['display_name']}} account associated with this
        email address at {{$changed_at}} IST.</p>

    <p>If this was not done by you, please reset your password immediately.<a href="{{'https://' . $org['hostname'] .
     '/#/access/forgotpwd'}}">here</a></p>
</div>

<div>
    <p>
        --<br/>
        {{$org['display_name']}} <br/>
        For queries, contact us <a href="https://dashboard.razorpay.com/#/app/dashboard#request">here</a>
    </p>
    <div>
        <img style="width:200px; height:auto;" src="{{$org['login_logo_url']}}">
    </div>
</div>
</body>
</html>
