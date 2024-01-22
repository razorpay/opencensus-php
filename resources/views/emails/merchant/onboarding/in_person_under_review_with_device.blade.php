<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Funds on hold</title>
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
            <img src="https://cdn.razorpay.com/under-review.png" alt="under review" style="width: 100%;">
        </div>
        <div class="header">
            <h2 color: #0D2366;>Application Under Review!</h2>
            <div class="underline" style="width: 24px;height: 4px;background: #2DD589; margin:-5px auto 15px"></div>
        </div>
        <div class="email-content" style="color: #7B8199; line-height: 26px; margin-bottom: 20px">
            Dear <span style="color: #5B6583; font-weight: bold">{{{$merchant['name']}}}</span>,
            <br/><br/>
            Congratulations! We are delighted to offer the best payment solution for your business with Razorpay POS.
            <br/>
            We want to inform you that we are reviewing your POS KYC details, which should take approximately 3-4
            business days to complete. Once your KYC is approved, we will deliver your POS device to your address
            in 2-3 business days.
            <br/><br/>
            Should we require any further information or clarifications, we will contact you via email in the coming
            days.
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
