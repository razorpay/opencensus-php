<!-- With Website -->
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activated MCC Pending State 1</title>
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
            <img src="https://cdn.razorpay.com/congratulations.png" alt="congratulations" style="width: 100%;">
        </div>
        <div class="header">
            <h2 color: #0D2366;>Congratulations!</h2>
            <div class="underline" style="width: 24px;height: 4px;background: #2DD589; margin:-5px auto 15px"></div>
        </div>
        <div class="email-content" style="color: #7B8199; padding-bottom: 20px; line-height: 26px">
            Dear <span style="color: #5B6583; font-weight: bold">{{{$merchant['name']}}}</span>,
            <br/><br/>
            Bank Settlements have been enabled on your Razorpay account. You will now start receiving payments from your
            customers in your bank account. The payments will be settled into your account as per your custom settlement
            schedule.
        </div>

        <div class="cta"
             style="padding:10px 18px;text-align: center;background: #528FF0;display: inline-block; border-radius:2px">
            <a href="https://dashboard.razorpay.com/" style="color: #FFF; text-decoration:none">Start accepting
                payments</a>
        </div>
    </div>
    @isset($merchant['business_website'])
        <div class="note-content"
             style="width: 500px; background: #FFFFFF; margin: 10px auto; padding:20px; border-top: 2px solid #528FF0; color: #7B8199; line-height: 26px;text-align: center;">
            Please keep an eye out for another email from our team regarding the next steps of the onboarding process.

            <br/><br/>We hope you are enjoying your experience with Razorpay and look forward to partnering with you on
            your
            growth journey.
        </div>
        <div class="email-footer"
             style="width: 500px; background: #FFFFFF; margin: 10px auto; padding:20px; color: #7B8199;text-align: center; line-height: 26px">
            Thanks & Regards,<br/>
            <span style="color: #5B6583;"><b>Razorpay Team</b></span>

        </div>
    @endisset


    @empty($merchant['business_website'])
        <div class="note-content"
             style="width: 500px; background: #FFFFFF; margin: 10px auto; padding:20px; border-top: 2px solid #528FF0; color: #7B8199; line-height: 26px;">
            Please keep an eye out for another email from our team regarding the next steps of the onboarding process.
            Till
            then, we
            hope you enjoy your experience with Razorpay.
        </div>

        <div class="email-footer"
             style="width: 500px; background: #FFFFFF; margin: 10px auto; padding:20px; color: #7B8199;text-align: center; line-height: 26px">
            We look forward to partnering with you on your growth journey.
            <br/><br/>
            Stay safe.<br/>
            Thanks & Regards,<br/>
            <span style="color: #5B6583;"><b>Razorpay Team</b></span>
        </div>
    @endif

</div>
</body>

</html>
