<!DOCTYPE html>
<html lang="en-US">
    <head>
        <meta charset="utf-8">
    </head>
    <body>


        @if($merchant_details['is_whitelisted_activation'] === false)
           <h2>Your {{ $org['business_name'] }} Account is pending approval</h2>

            <div>
                <p>Hi {{{$merchant_details['contact_name']}}},</p>

                <p>Your {{ $org['business_name'] }} activation form is complete and we have notified the admins to verify the details. We will communicate to you if anything else is required.</p>

                <p>Meanwhile, you can integrate with {{ $org['business_name'] }} in test mode and feel free to communicate with us <a href="https://dashboard.razorpay.com/#/app/dashboard#request">here</a> in case of any issues or queries.</p>
            </div>
        @else
            <h2>KYC form submitted for {{ $org['business_name'] }}</h2>

            <div>
                <p>Hi {{{$merchant_details['contact_name']}}},</p>

                <p>Hurray! Your activation form has been submitted</p>

                <p>Our team will reach back to you, if we need any clarification on your documents and reach you once your account gets activated.</p>
            </div>
        @endif

        <div>
            <p>
            --<br/>
            The {{ $org['display_name'] }} Team <br/>
            <a href="mailto: {{ $org['signature_email'] }}">{{ $org['signature_email'] }}</a>
            </p>

            @if ($org['custom_code'] === 'rzp')
                <a href="https://razorpay.com" target="_blank">
                    <img style="width:200px; height:auto;" src="<?php echo $message->embed(public_path().'/img/logo_black.png'); ?>">
                </a>
            @elseif ($org['login_logo_url'] !== '')
                <a href="{{ 'https://'.$org['hostname'] }}" target="_blank">
                    <img style="width:200px; height:auto;" src="{{ $org['login_logo_url'] }}">
                </a>
            @endif

        </div>
    </body>
</html>
