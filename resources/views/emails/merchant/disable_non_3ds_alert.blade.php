<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="viewport" content="width=device-width">
</head>

<body class="body" style="width: 100% !important; min-width: 100%; -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; padding: 0; margin: 0; text-align: left; font-size: 14px; line-height: 19px; background: #EBECEE;">
    <br />
    <img src="<?php echo $message->embed(public_path() . '/img/logo_black.png'); ?>" height=30 style="display: block; margin: auto;" />
    <br />
    <div class="container" style="background-color:#3495ff; height:7px; width: 80% !important; min-width:
            80%; -webkit-text-size-adjust: 80%; -ms-text-size-adjust: 80%; margin: 0 auto;padding: 0px 20px;"></div>
    <div class="container" style="border-spacing: 0; width: 80% !important; min-width: 80%;
            -webkit-text-size-adjust: 80%; -ms-text-size-adjust: 80%; margin: 0 auto; word-break: break-word;
            hyphens: auto; border-collapse: collapse !important; vertical-align: top; color: #7c839a; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; text-align: left; padding: 20px; font-size: 14px; line-height: 19px; background: #ffffff; background-color: #ffffff; padding: 20px; position: relative; border: 1px solid #e0e0e0; letter-spacing: 0.4px;">
        <br />
        <br />Dear Seller,
        <br />
        <br />
        As per your confirmation on the Razorpay dashboard, we have disabled all Non-3D Secure transactions for international payments on your Merchant ID.
        With this development, only 3D Secure International card transactions will be processed on your checkout.
        This significantly reduces fraud related risks and your exposure to chargeback liabilities.
        However, ~70% of all international transactions take place on Non 3D Secure enabled cards and you are thus likely to see an impact on your conversions with Non-3D Secure disabled.
        <br />
        <br />
        <b>How do I enable Non 3D Secure again?</b>
        <br />
        To Enable Non 3D Secure transactions again, simply Go to your Razorpay dashboard (payment method section) and enable Non 3D Secure transactions.
        Razorpay will process this request and will send an intimation once Non 3D Secure is enabled for you.
        <br />
        <br />
        <p>
            Regards,
            <br />
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
                <img src="<?php echo $message->embed(public_path() . '/img/facebook.png'); ?>" height=20 />
            </a>
            <a href="https://twitter.com/razorpay" style="margin:5px; text-decoration: none;">
                <img src="<?php echo $message->embed(public_path() . '/img/twitter.png'); ?>" height=20 />
            </a>
            <a href="https://github.com/razorpay" style="margin:5px; text-decoration: none;">
                <img src="<?php echo $message->embed(public_path() . '/img/github.png'); ?>" height=20 />
            </a>
        </div>
    </div>
    <table>
        <tr>
            <td style="height: 7px"></td>
        </tr>
    </table>
</body>

</html>