<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
        <meta name="viewport" content="width=device-width">
    </head>
    <body class="body" style="width: 100% !important; min-width: 100%; -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; padding: 0; margin: 0; text-align: left; font-size: 14px; line-height: 19px; background: #EBECEE;">
            <br/>
            <img src="<?php echo $message->embed(public_path().'/img/logo_black.png'); ?>" height=30 style="display: block; margin: auto;" />
            <br/>
            <div class="container" style="background-color:#3495ff; height:7px; width: 80% !important; min-width:
            80%; -webkit-text-size-adjust: 80%; -ms-text-size-adjust: 80%; margin: 0 auto;padding: 0px 20px;"></div>
            <div class="container" style="border-spacing: 0; width: 80% !important; min-width: 80%;
            -webkit-text-size-adjust: 80%; -ms-text-size-adjust: 80%; margin: 0 auto; word-break: break-word;
            hyphens: auto; border-collapse: collapse !important; vertical-align: top; color: #7c839a; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; text-align: left; padding: 20px; font-size: 14px; line-height: 19px; background: #ffffff; background-color: #ffffff; padding: 20px; position: relative; border: 1px solid #e0e0e0; letter-spacing: 0.4px;">
                <br/>
                <br/>Hey,
                <br/>
                <br/>Thank you for your patience.
                <br/>
                <br/>
                @if ($reason_category === 'unsupported_business_model')
                We have reviewed your application for subscription activation for your account {{$merchant_id}}.
                Unfortunately, your current business model is not supported for {{$feature}} and hence, we would not be
                able to enable/activate the feature at the moment.
                @elseif ($reason_category === 'invalid_use_case')
                We have reviewed your application for {{$feature}} activation for your account {{$merchant_id}}.
                Unfortunately, your current use-case is not in accordance with our product and hence, we will not be
                able to approve this request at the moment.
                @else
                We have reviewed your application for {{$feature}} activation for your account {{$merchant_id}}.
                Unfortunately, we would not be able to enable/activate the feature at the moment.
                <br/>
                We will reach out to you once we can support {{$feature}} for your business.
                @endif
                <br/>
                <p>
                    Regards,
                    <br/>
                    Team Razorpay
                </p>
            </div>
            <div class="container" style="border-spacing: 0; width: 80% !important; min-width:
            80%; -webkit-text-size-adjust: 80%; -ms-text-size-adjust: 80%; margin: 0 auto; word-break: break-word;
            hyphens: auto; border-collapse: collapse !important; vertical-align: top; color: #7c839a; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; text-align: center; padding: 20px; padding-bottom: 5px; font-size: 14px; line-height: 19px; background: #ffffff; background-color: #ffffff; padding: 20px; position: relative; border: 1px solid #e0e0e0; letter-spacing: 0.4px; margin-bottom: 20px">
                <p>If you have any queries, please reach out to us <a href="https://dashboard.razorpay.com/#/app/dashboard#request" style="color:#3495ff">here</a>
                </p>
                <div>
                    <a href="https://facebook.com/razorpay" style="margin:5px; text-decoration: none;">
                        <img src="<?php echo $message->embed(public_path().'/img/facebook.png'); ?>" height=20/>
                    </a>
                    <a href="https://twitter.com/razorpay"style="margin:5px; text-decoration: none;">
                        <img src="<?php echo $message->embed(public_path().'/img/twitter.png'); ?>" height=20/>
                    </a>
                    <a href="https://github.com/razorpay" style="margin:5px; text-decoration: none;">
                        <img src="<?php echo $message->embed(public_path().'/img/github.png'); ?>" height=20/>
                    </a>
                </div>
            </div>
            <table>
                <tr><td style="height: 7px"></td></tr>
            </table>
    </body>
</html>
