Hi,

<br><br>

You are invited to sign up to {{ $org['business_name'] }} Merchant Services. <!-- TODO Parse invitation array and fill correct data -->

<br><br>

<a href="{{ 'https://' .$hostname .'/#/access/signup?merchant_invitation=' . $invitation['token'] }}">
    {{ 'https://' .$hostname .'/#/access/signup?merchant_invitation='. $invitation['token'] }}
</a>

<br><br>

See you soon!

<br>

<div>
	<p>
	--<br/>
	The {{ $org['business_name'] }} Team <br/>
	 <!--TODO Add respective org name here and in following details -->
	<a href="mailto: {{ $org['signature_email'] }}">{{ $org['signature_email'] }}</a>
	</p>

    @if ($org['custom_code'] === 'rzp')
        <a href="https://razorpay.com" target="_blank">
            <img style="width:200px; height:auto;" src="<?php echo $message->embed(public_path().'/img/logo_black.png'); ?>">
        </a>
    @elseif ($org['login_logo_url'] !== '')
        <a href="{{ 'https://' .$hostname }}" target="_blank">
            <img style="width:200px; height:auto;" src="{{ $org['login_logo_url'] }}">
        </a>
    @endif

</div>
