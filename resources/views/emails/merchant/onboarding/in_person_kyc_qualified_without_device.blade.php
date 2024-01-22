<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activated MCC Pending State 2</title>
</head>
<body style="padding: 0;margin: 0;background: #FAFAFA;font-family: Trebuchet MS">
<div class="email-wrapper">
    <div class="head-banner" style="width: 100%;height:120px;background:#1C3EA6">
        <div class="head-brand" style="width: 100px; margin-top: 20px; margin:0 auto;">
            <img src="https://razorpay.com/assets/razorpay-logo-white.svg" alt="Logo"
                 style="width: 100%; margin-top: 17px">
        </div>
    </div>
    <div class="email-info-card"
         style="width: 500px; background: #FFFFFF; margin: 0 auto; text-align: center; padding: 26px 20px; margin-top: -65px; border-radius: 2px">
        <div class="info-icon" style="width: 45px;margin: 0 auto;">
            <img src="https://cdn.razorpay.com/process-under-review.png" alt="process under review"
                 style="width: 100%;">
        </div>
        <div class="header">
            <h2 color: #0D2366;>KYC Approved</h2>
            <div class="underline" style="width: 24px;height: 4px;background: #2DD589; margin:-5px auto 15px"></div>
        </div>
        <div class="email-content" style="color: #7B8199; line-height: 26px">
            Dear <span style="color: #5B6583; font-weight: bold">{{{$merchant['name']}}}</span>,
            <br/><br/>
            We're excited to share that your POS KYC has successfully cleared the initial checks. To expedite the
            process, we kindly request you to place your order now. Our team will conduct the final checks only after
            you place your order.

            <br/><br/>
            If you have any questions or need assistance, please feel free to reach out to us.

        </div>
    </div>

    <div class="email-footer"
         style="width: 500px; background: #FFFFFF; margin: 10px auto; padding:20px; color: #7B8199;text-align: center; line-height: 26px">
        Thank you for choosing Razorpay POS.
        <br/><br/>
        Best Regards,<br/>
        <span style="color: #5B6583;"><b>Team Razorpay POS</b></span>
    </div>
</div>
</body>
</html>
