Hi!

<br><br>

{{ $sender_name }} has invited you to join their team ({{ $merchant_name }}).

<br><br>

Since you already have an account, you may accept the invitation from your profile page.

<br><br>

See you soon!

<br>

@if (isset($custom_branding) && $custom_branding === false)
    <div>
        <p>
        --<br/>
        The Razorpay Team <br/>
        <a href="mailto:contact@razorpay.com">contact@razorpay.com</a>
        </p>
        <a href="https://razorpay.com" target="_blank">
            <img style="width:200px; height:auto;" src="<?php echo $message->embed(public_path().'/img/logo_black.png'); ?>">
        </a>
    </div>
@elseif (isset($custom_branding) && $custom_branding === true)
    <div>
        <p>
        --<br/>
        The {{$org_name}} Team <br/>
        </p>
        <img style="width:200px; height:auto;" src="{{ $email_logo }}">
    </div>
@endif
