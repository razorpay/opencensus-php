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
            <div class="container" style="background-color:#3495ff; height:7px; width: 580px; margin: 0 auto;padding: 0px 20px;"></div>
            <div class="container" style="border-spacing: 0; width: 580px; margin: 0 auto; word-break: break-word; hyphens: auto; border-collapse: collapse !important; vertical-align: top; color: #7c839a; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; text-align: left; padding: 20px; font-size: 14px; line-height: 19px; background: #ffffff; background-color: #ffffff; padding: 20px; position: relative; border: 1px solid #e0e0e0; letter-spacing: 0.4px;">
                <span style="color: #3495ff; font-size: 17px">
                  {{$feature}} has been enabled
                </span>
                <br/>
                <br/>Hi,
                <br/>
                <br/>
                    {{$feature}} has been enabled on your Razorpay account. You can now start using {{$feature}} for live transactions.
                </p>
                <br/>
                <br/>
                <div style="padding: 0; vertical-align: top; text-align: left;">
                    <div style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; font-size: 14px; line-height: 19px; display: block; width: 135px; text-align: center; padding: 5px 0 4px; background: #3495ff; border: 1px solid #3495ff; color: #3495ff; border-bottom: 2px solid #3495ff; margin-bottom: 1em; float:left; margin-right: 10px">
                        <a href="https://dashboard.razorpay.com" style="text-decoration: none; font-size: 12px; font-weight: normal; font-family: 'Lucida Sans', Helvetica, Arial, sans-serif !important; color: #f2f2f2 !important;">
                            View on Dashboard
                        </a>
                    </div>
                    <div style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; font-size: 14px; line-height: 19px; display: block; width: 100px; text-align: center; padding: 5px 0 4px;  margin-bottom: 1em; float:left">
                        <a href="https://razorpay.com/docs/{{$documentation}}" style="text-decoration: none; font-size: 14px; font-weight: normal; font-family: 'Lucida Sans', Helvetica, Arial, sans-serif !important; color: #3495ff;">
                            View Docs
                        </a>
                    </div>
                </div>
                <p style="clear: left" >Thanks for choosing Razorpay and welcome to the Future of Payments!</p>
                <p>
                    Regards,
                    <br/>
                    Team Razorpay
                </p>
            </div>
            <br/>
            <div class="container" style="border-spacing: 0; width: 580px; margin: 0 auto; word-break: break-word; hyphens: auto; border-collapse: collapse !important; vertical-align: top; color: #7c839a; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; text-align: center; padding: 20px; padding-bottom: 5px; font-size: 14px; line-height: 19px; background: #ffffff; background-color: #ffffff; padding: 20px; position: relative; border: 1px solid #e0e0e0; letter-spacing: 0.4px; margin-bottom: 20px">
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