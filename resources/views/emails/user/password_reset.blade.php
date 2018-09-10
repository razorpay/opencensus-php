<!DOCTYPE html>
<html lang="en-US">
<head>
    <meta charset="utf-8">
</head>
<body>
<h2>Password Reset</h2>

<div>
    To reset your password, <a href=" {{'https://' . $org['hostname'] . '/#/access/resetpwd/'.$token.'?email='.$email }}" target="_blank">click here</a>. <br/>

    Or you may open the following link in your browser: <br/>
    <a href="{{ 'https://' . $org['hostname'] . '/#/access/resetpwd/'.$token.'?email='.$email }}" target="_blank">
        {{ 'https://' . $org['hostname'] . '/#/access/resetpwd/'.$token.'?email='.$email }}
    </a>

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
