Hi!

<br><br>

{{ $sender_name }} has invited you to join their team ({{ $merchant_name }}).

If you do not already have an account, you may click the following link to get started:

<br><br>

    @if ($product === 'banking')
        <a href="{{ 'https://x.razorpay.com/auth?invitation=' .$token }}">{{ 'https://x.razorpay.com/auth?invitation=' .$token }}</a>
    @else
        <a href="{{  'https://' .$hostname . '/#/access/signup?invitation=' .$token }}">{{ 'https://' .$hostname . '/#/access/signup?invitation=' .$token }}</a>
    @endif

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
