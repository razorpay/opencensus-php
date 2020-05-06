<!DOCTYPE html>
<html lang="en-US">
<head>
    <meta charset="utf-8">
</head>
<body>

<div>
    Greetings! <br/><br/>

    {{$routeMerchantName}} has added you as their associate account on {{$org['business_name']}}.
    <br/>
    You can set your password <a href="{{ 'https://' . $org['hostname'] . '/#/access/resetpassword?email='.$email.'&token='.$token }}" target="_blank"> here</a> and proceed to view your transactions.<br/><br/>

    You can also open the following link in your browser: <br/>
    <a href="{{ 'https://' . $org['hostname'] . '/#/access/resetpassword?email='.$email.'&token='.$token }}" target="_blank">
        {{ 'https://' . $org['hostname'] . '/#/access/resetpassword?email='.$email.'&token='.$token }}
    </a>

</div>

<div>
    <p>
        --<br/>
        {{$org['display_name']}} <br/>
        For queries, contact <a href="https://dashboard.razorpay.com/#/app/dashboard#request">here</a>
    </p>
    <div>
        <img style="width:200px; height:auto;" src="{{$org['login_logo_url']}}">
    </div>
</div>
</body>
</html>
