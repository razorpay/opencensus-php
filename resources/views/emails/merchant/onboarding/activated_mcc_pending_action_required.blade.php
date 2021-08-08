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
    @isset($merchant['business_website'])
        <div class="email-info-card"
             style="width: 500px; background: #FFFFFF; margin: 0 auto; text-align: center; padding: 26px 20px; margin-top: -65px; border-radius: 2px">
            <div class="info-icon" style="width: 45px;margin: 0 auto;">
                <img src="https://cdn.razorpay.com/process-under-review.png" alt="process under review"
                     style="width: 100%;">
            </div>
            <div class="header">
                <h2 color: #0D2366;>Application Under Review!</h2>
                <div class="underline" style="width: 24px;height: 4px;background: #2DD589; margin:-5px auto 15px"></div>
            </div>
            <div class="email-content" style="color: #7B8199; line-height: 26px">
                Dear <span style="color: #5B6583; font-weight: bold">{{{$merchant['name']}}}</span>,
                <br/><br/>
                Hope you are doing well and keeping safe.
                <br/><br/>
                As the next step of your Razorpay onboarding process, we need to conduct a few routine compliance
                checks.
            </div>
        </div>
        <div class="note-content"
             style="width: 500px; background: #FFFFFF; margin: 10px auto; padding:20px; border-top: 2px solid #528FF0; color: #7B8199; line-height: 26px;">
            <div style="font-weight: bold; color:#5B6583; margin-bottom: 10px;">
                Please keep the following pages ready for review on your website:
            </div>
            <div class="points">
                <li>About Us page (your business description)</li>
                <li>Contact Us page (your operating address, email address)</li>
                <li>Pricing page (the pricing of your product /solution)</li>
                <li>Privacy Policy (<a
                        href="https://docs.google.com/document/u/1/d/1yqqWTE_jfC8F_u9UV9nLq3AUZR2wwpQGJigRJV3YQvg/pub"
                        style="text-decoration: none; color: #528FF0">template</a> for your reference)
                </li>
                <li>Terms & Conditions (<a
                        href="https://docs.google.com/document/u/1/d/1bCwt0WccF7oDMBGAGRxtPgUfzqGzkUjtLnnE1JlL2dg/pub"
                        style="text-decoration: none; color: #528FF0">template</a> for your
                    reference)
                </li>
                <li>Cancellation/ Refund Policy (<a
                        href="https://docs.google.com/document/u/1/d/1xYM1QHm9S5phnkzyENqJ3KXv37schlsiTp0Id_4IMwE/pub"
                        style="text-decoration: none; color: #528FF0">template</a> for your
                    reference)
                </li>
            </div>
        </div>
        <div class="content"
             style="width: 500px; background: #FFFFFF; margin: 10px auto; padding:20px; color: #7B8199;text-align: left; line-height: 26px">
            In case you don’t have these pages readily available, feel free to use the templates we have shared to
            create
            them.
            <br/><br/>
            These pages will be reviewed by our compliance team and are crucial to complete your Razorpay onboarding. We
            might
            also
            reach out in case any further clarifications are required.
            <br/><br/>
            <span style="font-weight: bold; color:#5B6583">
        Please have this ready and respond to our team when they reach out. Else, we might have to suspend settlements
        for your
        account until the pages are up and running for our review.
      </span>
        </div>
        <div class="note-content"
             style="width: 500px; background: #FFFFFF; margin: 10px auto; padding:20px 20px 85px; border-top: 2px solid #528FF0; color: #7B8199; line-height: 26px;">
            With that, you are just a couple of steps away from completing your Razorpay onboarding process!
            <div class="steps" style="display: flex; justify-content:center;padding-top: 26px">
                <div class="step" style="display: inline-block">
                    <div class="check-point"
                         style="width: 13px; height: 13px; background: #2DD589; border-radius: 50%;position: relative;left: 36px;">
                    </div>
                    <div class="process-path"
                         style="width: 102px; height:5px; border-top: 1px dashed #2DD589; position: relative; top: -7px;left: 36px;">
                    </div>
                    <div class="check-point-desc"
                         style="width: 84px; text-align: center;font-size: 9px; line-height: 15px">
                        You
                        can
                        start accepting
                        payments!
                    </div>
                </div>
                <div class="step" style="display: inline-block; position:relative;">
                    <div class="note" style="position: absolute;font-size: 9px;top: -25px;left: 16px;">You are here!
                    </div>
                    <div class="check-point"
                         style="width: 13px; height: 13px; background: #2DD589; border-radius: 50%; z-index: 999;position: relative;left: 36px;">
                        <div class="active-point"
                             style="position: absolute; width: 7px; height: 7px; background: #FFF;border-radius: 50%; top: 3px; left: 3px;">
                        </div>
                    </div>
                    <div class="process-path"
                         style="width: 102px; height:5px; border-top: 1px dashed #528FF0; position: relative; top: -7px;left: 36px;">
                    </div>
                    <div class="check-point-desc"
                         style="width: 90px; text-align: center;font-size: 9px; line-height: 15px">
                        You can start receiving
                        payments in your bank account!
                    </div>
                    <div class="tooltip"
                         style="font-size: 9px; line-height: 14px; position: absolute; width: 120px; text-align: center; background: #ECF5FF; padding: 10px; border-radius: 2px; top: 83px; left: -22px;">
                        <div class="arc"
                             style="width: 0; height: 0; border-left: 10px solid transparent; border-right: 10px solid transparent; border-bottom: 10px solid #ECF5FF; position: absolute; top: -10px;left: 58px;border-radius: 4px">
                        </div>
                        Please be ready with the
                        checklist of information
                        mentioned in the mail.
                    </div>
                </div>
                <div class="step" style="display: inline-block">
                    <div class="check-point"
                         style="width: 11px; height: 11px; background: #FFF; border: 1px solid #528FF0; border-radius: 50%; z-index: 999;position: relative;left: 36px;">
                    </div>
                    <div class="process-path"
                         style="width: 102px; height:5px; border-top: 1px dashed #528FF0; position: relative; top: -7px;left: 36px;">
                    </div>
                    <div class="check-point-desc"
                         style="width: 84px; text-align: center;font-size: 9px; line-height: 15px">
                        Your business details are getting reviewed
                    </div>
                </div>
                <div class="step" style="display: inline-block">
                    <div class="check-point"
                         style="width: 11px; height: 11px; border: 1px solid #528FF0; border-radius: 50%; z-index: 999;position: relative;left: 36px;">
                    </div>
                    <div class="check-point-desc"
                         style="width: 84px; text-align: center;font-size: 9px; line-height: 15px; margin-top: 6px">
                        Congratulations!
                        Your account is fully activated!
                    </div>
                </div>
            </div>
        </div>
    @endisset
    @empty($merchant['business_website'])
        <div class="email-info-card"
             style="width: 500px; background: #FFFFFF; margin: 0 auto; text-align: center; padding: 26px 20px; margin-top: -65px; border-radius: 2px">
            <div class="info-icon" style="width: 45px;margin: 0 auto;">
                <img src="https://cdn.razorpay.com/process-under-review.png" alt="process under review"
                     style="width: 100%;">
            </div>
            <div class="header">
                <h2 color: #0D2366;>Application Under Review!</h2>
                <div class="underline" style="width: 24px;height: 4px;background: #2DD589; margin:-5px auto 15px"></div>
            </div>
            <div class="email-content" style="color: #7B8199; line-height: 26px">
                Dear <span style="color: #5B6583; font-weight: bold">{{{$merchant['name']}}}</span>,
                <br/><br/>
                Hope you are doing well and keeping safe.
                <br/><br/>
                As the next step of your Razorpay onboarding process, we need to conduct a
                few routine compliance checks. Our team might reach out to you in case any further clarifications are
                required,
                regarding your business model and policies. Please respond to our team when they reach out.
            </div>
        </div>

        <div class="note-content"
             style="width: 500px; background: #FFFFFF; margin: 10px auto; padding:20px; border-top: 2px solid #528FF0; color: #7B8199; line-height: 26px;">
            With that, you are just a couple of steps away from completing your Razorpay onboarding process!
            <div class="steps" style="display: flex; justify-content:center;padding-top: 26px">
                <div class="step" style="display: inline-block">
                    <div class="check-point"
                         style="width: 13px; height: 13px; background: #2DD589; border-radius: 50%;position: relative;left: 36px;">
                    </div>
                    <div class="process-path"
                         style="width: 102px; height:5px; border-top: 1px dashed #2DD589; position: relative; top: -7px;left: 36px;">
                    </div>
                    <div class="check-point-desc"
                         style="width: 84px; text-align: center;font-size: 9px; line-height: 15px">
                        You
                        can
                        start accepting
                        payments!
                    </div>
                </div>
                <div class="step" style="display: inline-block; position:relative;">
                    <div class="note" style="position: absolute;font-size: 9px;top: -25px;left: 16px;">You are here!
                    </div>
                    <div class="check-point"
                         style="width: 13px; height: 13px; background: #2DD589; border-radius: 50%; z-index: 999;position: relative;left: 36px;">
                        <div class="active-point"
                             style="position: absolute; width: 7px; height: 7px; background: #FFF;border-radius: 50%; top: 3px; left: 3px;">
                        </div>
                    </div>
                    <div class="process-path"
                         style="width: 102px; height:5px; border-top: 1px dashed #528FF0; position: relative; top: -7px;left: 36px;">
                    </div>
                    <div class="check-point-desc"
                         style="width: 90px; text-align: center;font-size: 9px; line-height: 15px">
                        You can start receiving
                        payments in your bank account!
                    </div>
                </div>
                <div class="step" style="display: inline-block">
                    <div class="check-point"
                         style="width: 11px; height: 11px; background: #FFF; border: 1px solid #528FF0; border-radius: 50%; z-index: 999;position: relative;left: 36px;">
                    </div>
                    <div class="process-path"
                         style="width: 102px; height:5px; border-top: 1px dashed #528FF0; position: relative; top: -7px;left: 36px;">
                    </div>
                    <div class="check-point-desc"
                         style="width: 84px; text-align: center;font-size: 9px; line-height: 15px">
                        Your business details are getting reviewed
                    </div>
                </div>
                <div class="step" style="display: inline-block">
                    <div class="check-point"
                         style="width: 11px; height: 11px; border: 1px solid #528FF0; border-radius: 50%; z-index: 999;position: relative;left: 36px;">
                    </div>
                    <div class="check-point-desc"
                         style="width: 84px; text-align: center;font-size: 9px; line-height: 15px; margin-top: 6px">
                        Congratulations!
                        Your account is fully activated!
                    </div>
                </div>
            </div>

        </div>
    @endif
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
