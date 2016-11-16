Hi!

<br><br>

{{ $admin['name'] }} has invited you to sign up for online transactions ({{$invitation_array['admin_id']}}). <!-- TODO Parse invitation array and fill correct data -->

<br><br>

<a href="{{ url('/#/access/signup?merchant_invitation='.$invitation_array['token']) }}">{{ URL::to('/#/access/signup?merchant_invitation='.$invitation_array['token']) }}</a>

<br><br>

See you soon!

<br>

<div>
	<p>
	--<br/>
	The Razorpay Team <br/>
	 <!--TODO Add respective org name here and in following details -->
	<a href="mailto:contact@razorpay.com">contact@razorpay.com</a>
	</p>
	<a href="https://razorpay.com" target="_blank">
		<img style="width:200px; height:auto;" src="<?php echo $message->embed(public_path().'/img/logo_black.png'); ?>">
	</a>
</div>
