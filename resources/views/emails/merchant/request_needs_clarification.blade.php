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
                <br/>Thank you for submitting your request for {{$feature}}. We need a few more details from you, before we can enable {{$feature}} on your account.
                <br/>
                <br/>
                {!! $needs_clarification_text !!}.
                <br/>
                <p>
                    Regards,
                    <br/>
                    Team Razorpay
                </p>
                <p>
                    **P.S: We would need 24-48 working hours to validate your responses. Also, kindly avoid in-line responses.**
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
