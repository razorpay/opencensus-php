<!DOCTYPE html>
<html lang="en-US">
<head>
    <meta charset="utf-8">
</head>
<body>

<div>
        We have received a request to update the registered email of Razorpay account to {{$email}} from {{$current_owner_email}}.
        To successfully update your registered email id, please <a href="{{'https://' . $org['hostname'] . '/#/access/emailupdate?email=' . urlencode($email) .'&token='.$token . '&mid=' . $merchant_id }}" target="_blank">click here</a>. <br/>
        and set the login password for {{$email}}.
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
