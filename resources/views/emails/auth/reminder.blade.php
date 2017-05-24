<!DOCTYPE html>
<html lang="en-US">
	<head>
		<meta charset="utf-8">
	</head>
	<body>
		<h2>Password Reset</h2>

		<div>
			To reset your password, <a href="{{ URL::to('/#/access/resetpwd/'.$token.'/expiry_time/'.$expiryTime) }}" target="_blank">click here</a>. <br/>

			Or you may open the following link in your browser: <br/>
			<a href="{{ URL::to('/#/access/resetpwd/'.$token.'/expiry_time/'.$expiryTime) }}" target="_blank">{{ URL::to('/#/access/resetpwd/'.$token.'/expiry_time/'.$expiryTime) }}</a>

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
