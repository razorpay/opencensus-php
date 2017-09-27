<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
        <meta name="viewport" content="width=device-width">
    </head>
    <body class="body" style="width: 100% !important; min-width: 100%; -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; padding: 0; margin: 0; text-align: left; font-size: 14px; line-height: 19px; background: #EBECEE;">
        <br/>
        <center>
            <img src="<?php echo $message->embed(public_path().'/img/logo_black.png'); ?> height=50/>
            <br/>
            <div class="container" style="border-spacing: 0; width: 580px; margin: 0 auto; word-break: break-word; hyphens: auto; border-collapse: collapse !important; vertical-align: top; color: #7c839a; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; text-align: left; padding: 20px; font-size: 14px; line-height: 19px; background: #ffffff; background-color: #ffffff; padding: 20px; position: relative; border: 1px solid #e0e0e0; border-top: #3495ff; border-top-width: 7px; border-top-style: outset; letter-spacing: 0.4px;">
                <span style="color: #3495ff; font-size: 17px">
                  {{$feature}} has been enabled
                </span>
                <br/>
                <p>Hi,</p>
                <p>
                    {{$feature}} has been enabled on your account for live mode. You can access the product on your Razorpay dashboard from the left side menu.
                </p>
                <br/>
                <div style="padding: 0; vertical-align: top; text-align: left;">
                    <div style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; font-size: 14px; line-height: 19px; display: block; width: 135px; text-align: center; padding: 5px 0 4px; background: #3495ff; border: 1px solid #3495ff; color: #3495ff; border-bottom: 2px solid #3495ff; margin-bottom: 1em; float:left; margin-right: 10px">
                        <a href="https://dashboard.razorpay.com" style="text-decoration: none; font-size: 12px; font-weight: normal; font-family: 'Lucida Sans', Helvetica, Arial, sans-serif !important; color: #f2f2f2 !important;">
                            View on Dashboard
                        </a>
                    </div>
                    <div style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; font-size: 14px; line-height: 19px; display: block; width: 100px; text-align: center; padding: 5px 0 4px;  margin-bottom: 1em; float:left">
                        <a href="https://dashboard.razorpay.com" style="text-decoration: none; font-size: 14px; font-weight: normal; font-family: 'Lucida Sans', Helvetica, Arial, sans-serif !important; color: #3495ff;">
                            View Docs
                        </a>
                    </div>
                </div>
                <br style="clear: left" />
                <p>In case you did not request this or would like to revoke the access, please visit your dashboard.</p>
                <p>
                    Regards,
                    </br>
                    Team Razorpay
                </p>
            </div>
            <br/>
            <div class="container" style="border-spacing: 0; width: 580px; margin: 0 auto; word-break: break-word; hyphens: auto; border-collapse: collapse !important; vertical-align: top; color: #7c839a; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; text-align: center; padding: 20px; padding-bottom: 5px; font-size: 14px; line-height: 19px; background: #ffffff; background-color: #ffffff; padding: 20px; position: relative; border: 1px solid #e0e0e0; letter-spacing: 0.4px;">
                <p>If you have any queries, please reach out to
                    </br>
                    <a href="mailto:support@razorpay.com" style="color:#3495ff">support@razorpay.com</a>
                </p>
                <div>
                    <a href="https://facebook.com/razorpay" style="margin:5px;">
                        <img src="<?php echo $message->embed(public_path().'/img/facebook.png'); ?>" height=20/>
                    </a>
                    <a href="https://twitter.com/razorpay"style="margin:5px;">
                        <img src="<?php echo $message->embed(public_path().'/img/twitter.png'); ?>" height=20/>
                    </a>
                    <a href="https://github.com/razorpay" style="margin:5px;">
                        <img src="<?php echo $message->embed(public_path().'/img/github.png'); ?>" height=20/>
                    </a>
                </div>
            </div>
        </center>
    </body>
</html>