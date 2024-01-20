<!DOCTYPE html>
<html lang="en-US">
<head>
    <meta charset="utf-8">
</head>
<body>
<h2>Password Reset</h2>

<div>
    @if ($unified_hostname != null)
        To reset your password, <a href="{{ $unified_hostname
            .'/forgotpwd/#token='. $token . '&email=' . $email}}" target="_blank">click here</a>. <br/>

        Or you may open the following link in your browser: <br/>
        <a href="{{$unified_hostname .'/forgotpwd/#token='. $token . '&email=' . $email}}" target="_blank">
            {{ $unified_hostname
            .'/forgotpwd/#token='. $token . '&email=' . $email}}
        </a>

    @elseif ($product === 'banking')
        To reset your password, <a href="{{'https://' . parse_url(config('applications.banking_service_url'), PHP_URL_HOST)
            .'/forgot-password#token='. $token . '&email=' . $email}}" target="_blank">click here</a>. <br/>

        Or you may open the following link in your browser: <br/>
        <a href="{{'https://' . parse_url(config('applications.banking_service_url'), PHP_URL_HOST).'/forgot-password#token='. $token . '&email=' . $email}}" target="_blank">
            {{'https://' . parse_url(config('applications.banking_service_url'), PHP_URL_HOST)
            .'/forgot-password#token='. $token . '&email=' . $email}}
        </a>
    @else
        To reset your password, <a href=" {{'https://' . $org['hostname'] . '/#/access/resetpassword?email='
        .$email.'&token='.$token }}" target="_blank">click here</a>. <br/>

        Or you may open the following link in your browser: <br/>
        <a href="{{ 'https://' . $org['hostname'] . '/#/access/resetpassword?email='.$email.'&token='.$token }}" target="_blank">
            {{ 'https://' . $org['hostname'] . '/#/access/resetpassword?email='.$email.'&token='.$token }}
        </a>
    @endif

</div>

<div>
    <p>
        --<br/>
        {{$org['display_name']}} <br/>
        @if ($org['showAxisSupportUrl'] !== true)
             For queries, contact us <a href="https://dashboard.razorpay.com/#/app/dashboard#request">here</a>
        @endif
    </p>
    <div>
        <img style="width:200px; height:auto;" src="{{$org['login_logo_url']}}">
    </div>
</div>
</body>
</html>
