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
                  COUPON CODE: {{$code}} is expiring on {{$end_at}}
                </span>
    <br/>
    <br/>Hi,
    <br/>
    <br/>
    COUPON CODE: {{$code}} is expiring on {{$end_at}}, Please take an appropriate action on this.
    </p>
    <br/>
    <br/>
    <p>
        Regards,
        <br/>
        Team Razorpay
    </p>
</div>
<br/>
<table>
    <tr><td style="height: 7px"></td></tr>
</table>
</body>
</html>
