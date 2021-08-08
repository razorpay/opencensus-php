<!-- With Website -->
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
            Hope you are doing well and keeping safe.
            <br/><br/>
            We see that you are very close to completing your Razorpay onboarding process. However, because of certain
            clarifications being pending from your end, we had to pause settlements for your account for the time being.
            <br/><br/>
            Please share the clarifications/ business details asked on your dashboard, so our team can complete the
            review
            process,
            activate your account, and settle any pending funds as quickly as possible.

        </div>
        <div class="cta"
             style="padding:10px 18px;text-align: center;background: #528FF0;display: inline-block; border-radius:2px">
            <a href="https://dashboard.razorpay.com/" style="color: #FFF; text-decoration:none">Share details for the
                clarifications</a>
        </div>
    </div>
    <div class="email-content"
         style="width: 500px; background: #FFFFFF; margin: 10px auto; padding:20px 20px 22px; border-top: 2px solid #528FF0; color: #5B6583;line-height: 26px;">
        Meanwhile, please rest assured that you can continue to accept payments from your customers. Your funds are safe
        with us
        and will be settled as soon as we receive and review the required clarifications.
    </div>
    <div class="note-content"
         style="width: 500px; background: #FFFFFF; margin: 10px auto; padding:20px 20px 145px; border-top: 2px solid #528FF0; color: #5B6583; font-weight:bold; line-height: 26px;">
        You are just a simple step away from completing your Razorpay onboarding:
        <div class="steps" style="display: flex; justify-content:center;padding-top: 26px">
            <div class="step" style="display: inline-block">
                <div class="check-point"
                     style="width: 13px; height: 13px; background: #2DD589; border-radius: 50%;position: relative;left: 36px;">
                </div>
                <div class="process-path"
                     style="width: 102px; height:5px; border-top: 1px dashed #2DD589; position: relative; top: -7px;left: 36px;">
                </div>
                <div class="check-point-desc"
                     style="width: 84px; text-align: center;font-size: 9px; line-height: 15px; font-weight: normal">You
                    can
                    start accepting
                    payments!
                </div>
            </div>
            <div class="step" style="display: inline-block; position:relative;">
                <div class="check-point"
                     style="width: 13px; height: 13px; background: #2DD589; border-radius: 50%; z-index: 999;position: relative;left: 36px;">
                    <div class="active-point"
                         style="position: absolute; width: 7px; height: 7px; background: #FFF;border-radius: 50%; top: 3px; left: 3px;">
                    </div>
                </div>
                <div class="process-path"
                     style="width: 102px; height:5px; border-top: 1px dashed #FF636E; position: relative; top: -7px;left: 36px;">
                </div>
                <div class="check-point-desc"
                     style="width: 90px; text-align: center;font-size: 9px; line-height: 15px; font-weight: normal">
                    You can start receiving
                    payments in your bank account!
                </div>
            </div>
            <div class="step" style="display: inline-block; position:relative;">
                <div class="note" style="position: absolute;font-size: 9px;top: -25px;left: 16px;">You are here!</div>
                <div class="check-point"
                     style="width: 11px; height: 11px; background: #FFF; border: 1px solid #FF636E; border-radius: 50%; z-index: 999;position: relative;left: 36px;">
                </div>
                <div class="process-path"
                     style="width: 102px; height:5px; border-top: 1px dashed #528FF0; position: relative; top: -7px;left: 36px;">
                </div>
                <div class="check-point-desc"
                     style="width: 84px; text-align: center;font-size: 9px; line-height: 15px; font-weight:bold;">
                    Your business details are getting reviewed
                </div>
                <div class="tooltip"
                     style="font-size: 9px; line-height: 14px; position: absolute; width: 160px; text-align: center; background: #FFEAEB; padding: 10px; border-radius: 2px; top: 83px; left: -59px;">
                    <div class="arc"
                         style="width: 0; height: 0; border-left: 10px solid transparent; border-right: 10px solid transparent; border-bottom: 10px solid #FFEAEB; position: absolute; top: -10px;left: 93px;border-radius: 4px;">
                    </div>
                    <strong>Important:</strong> <br/>
                    <span style="font-weight: normal">Your funds have been held till the review is complete.</span><br/><br/>
                    <span style="font-weight: normal">Please respond to the queries raised on the dashboard to continue with a
              seamless payment experience.</span>

                </div>
            </div>
            <div class="step" style="display: inline-block">
                <div class="check-point"
                     style="width: 11px; height: 11px; border: 1px solid #528FF0; border-radius: 50%; z-index: 999;position: relative;left: 36px;">
                </div>
                <div class="check-point-desc"
                     style="width: 84px; text-align: center;font-size: 9px; line-height: 15px; font-weight: normal;margin-top: 6px">
                    Congratulations!
                    Your account is fully activated!
                </div>
            </div>
        </div>

    </div>

    <div class="email-footer"
         style="width: 500px; background: #FFFFFF; margin: 10px auto; padding:20px; color: #7B8199;text-align: center; line-height: 26px">
        We look forward to partnering with you on your growth journey.
        <br/><br/>
        Stay safe.<br/>
        Thanks & Regards,<br/>
        <span style="color: #5B6583;"><b>Razorpay Team</b></span>
    </div>
</div>
</body>

</html>
