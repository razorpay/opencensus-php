<!DOCTYPE html>
<html lang="en-US">
<head>
    <meta charset="utf-8">
</head>
<body>
<h2>Password Reset</h2>

<div>
    To reset your password, <a href="{{ $org['hostname'] . '/#/access/resetpwd/'.$token.'?expiry_time='.$expiryTime.'&email='.$email }}" target="_blank">click here</a>. <br/>

    Or you may open the following link in your browser: <br/>
    <a href="{{ 'https://' . $org['hostname'] . '/#/access/resetpwd/'.$token.'?expiry_time='.$expiryTime.'&email='.$email }}" target="_blank">
        {{ 'https://' . $org['hostname'] . '/#/access/resetpwd/'.$token.'?expiry_time='.$expiryTime.'&email='.$email }}
    </a>

</div>

<div>
    <p>
        --<br/>
        {{$org['display_name']}} <br/>
        <a href="mailto:contact@razorpay.com">contact@razorpay.com</a>
    </p>
    <div>
        <img style="width:200px; height:auto;" src="{{$org['login_logo_url']}}">
    </div>
</div>
</body>
</html>
