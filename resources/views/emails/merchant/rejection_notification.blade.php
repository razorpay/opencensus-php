<!DOCTYPE html>
<html lang="en-US">

<head>
    <meta charset="utf-8">
</head>

<body>
<div>
    <div>
        Hello,
    </div>
    <br />
    <div>
        We regret to inform you that, unfortunately, we would not be able to support your business as the bank has
        not approved your account for activation.
    </div>

    <br />
    <div>
        We request you to kindly look for any other alternative and wish you all the best.
    </div>
    <div>
        For any further queries or clarifications, feel free to reach out to us by visiting- <a
            rel="noopener noreferrer" href="https://razorpay.com/support"
            target="_blank">https://razorpay.com/support</a>
    </div>
    <br />
    <br />
    <div>
        @if ((empty($custom_branding) === false) and ($custom_branding === true) and (empty($email_logo) === false))
            <table class="container" style="border-spacing: 0; border-collapse: collapse; padding: 0; vertical-align: top; width: 580px; margin: 0 auto; text-align: inherit;">
                <tr style="padding: 0; vertical-align: top; text-align: left;">
                    <td style="border-collapse: collapse !important; vertical-align: top; padding: 0; margin: 0; text-align: left;">
                        <table class="row bluebg" style="border-spacing: 0; border-collapse: collapse; vertical-align: top; text-align: left; padding: 0px; width: 100%; position: relative; display: block;">
                            <tr style="padding: 0; vertical-align: top; text-align: left;">
                                <td class="wrapper offset-by-two" style="border-collapse: collapse !important; vertical-align: top;margin: 0; text-align: left; padding: 10px 20px 0px 0px; position: relative; padding-left: 100px;">
                                    <table class="eight columns" style="border-spacing: 0; border-collapse: collapse; padding: 0; vertical-align: top; text-align: left; margin: 0 auto; width: 380px;">
                                        <tr style="padding: 0; vertical-align: top; text-align: left;">
                                            <td class="center" style="border-collapse: collapse !important; vertical-align: top;margin: 0; text-align: center; padding: 0px 0px 10px;">
                                                <center style="width: 100%; min-width: 380px;">
                                                    <img src="{{ $email_logo }}" style="height: 32px;"/>
                                                </center>
                                            </td>
                                            <td class="expander" style="border-collapse: collapse !important; vertical-align: top;margin: 0; text-align: left; visibility: hidden; width: 0px; padding: 0 !important;">
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        @else
        Regards,
        <br />
        Team Razorpay
        @endif
    </div>
</div>
</body>
