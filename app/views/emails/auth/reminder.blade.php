<!DOCTYPE html>
<html lang="en-US">
	<head>
		<meta charset="utf-8">
	</head>
	<body>
		<h2>Password Reset</h2>

		<div>
			To reset your password, <a href="{{ URL::to('password/reset', array($token)) }}" trget="_blank">click here</a>. <br/>

			Or you may open the following link in your browser: <br/>
			{{ URL::to('password/reset', array($token)) }}
		</div>
	</body>
</html>
