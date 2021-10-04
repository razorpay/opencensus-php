
<!doctype html>
<html xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
    <title>
        {{ $billing_label }}
    </title>
    <!--[if !mso]><!-- -->
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <!--<![endif]-->
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style type="text/css">
        #outlook a { padding:0; }
        .ReadMsgBody { width:100%; }
        .ExternalClass { width:100%; }
        .ExternalClass * { line-height:100%; }
        body { margin:0;padding:0;-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%; }
        table, td { border-collapse:collapse;mso-table-lspace:0pt;mso-table-rspace:0pt; }
        img { border:0;height:auto;line-height:100%; outline:none;text-decoration:none;-ms-interpolation-mode:bicubic; }
        p { display:block;margin:13px 0; }
    </style>
    <!--[if !mso]><!-->
    <style type="text/css">
        @media only screen and (max-width:480px) {
            @-ms-viewport { width:320px; }
            @viewport { width:320px; }
        }
    </style>
    <!--<![endif]-->
    <!--[if mso]>
    <xml>
        <o:OfficeDocumentSettings>
            <o:AllowPNG/>
            <o:PixelsPerInch>96</o:PixelsPerInch>
        </o:OfficeDocumentSettings>
    </xml>
    <![endif]-->
    <!--[if lte mso 11]>
    <style type="text/css">
        .outlook-group-fix { width:100% !important; }
    </style>
    <![endif]-->
    <style type="text/css">
        @media only screen and (min-width:480px) {
            .mj-column-per-100 { width:100% !important; max-width: 100%; }
        }
    </style>
    <style type="text/css">
    </style>
</head>
<body style="background-color:#F0F0F0;">
<div style="background-color:#F0F0F0;">
    <!--[if mso | IE]>
    <table
        align="center" border="0" cellpadding="0" cellspacing="0" class="max-width-override-outlook" style="width:600px;" width="600"
    >
        <tr>
            <td style="line-height:0px;font-size:0px;mso-line-height-rule:exactly;">
    <![endif]-->
    <div class="max-width-override" style="background: {{ $brand_color }}; background-color: {{ $brand_color }}; Margin: 0px auto; max-width: unset;">
        <table align="center" border="0" cellpadding="0" cellspacing="0" role="presentation" style="background:{{ $brand_color }};background-color:{{ $brand_color }};width:100%;">
            <tbody>
            <tr>
                <td style="direction:ltr;font-size:0px;padding:0px;text-align:center;vertical-align:top;">
                    <!--[if mso | IE]>
                    <table role="presentation" border="0" cellpadding="0" cellspacing="0">
                        <tr>
                            <td
                                class="" style="vertical-align:top;width:600px;"
                            >
                    <![endif]-->
                    <div class="mj-column-per-100 outlook-group-fix" style="font-size:13px;text-align:left;direction:ltr;display:inline-block;vertical-align:top;width:100%;">
                        <table border="0" cellpadding="0" cellspacing="0" role="presentation" width="100%">
                            <tbody>
                            <tr>
                                <td style="vertical-align:top;padding:0px;">
                                    <table border="0" cellpadding="0" cellspacing="0" role="presentation" style="" width="100%">
                                        <tr>
                                            <td align="left" style="font-size:0px;padding:0px;word-break:break-word;">
                                                <div style="font-family:Trebuchet MS;font-size:13px;line-height:1;text-align:left;color:#000000;">
                                                    <div class="header" style="box-sizing: border-box; padding-top: 16px; max-width: 100%;">
                                                        <div class="content branding merchant" style="width: 85%; width: calc(46000% - 211600px); max-width: 460px; min-width: 308px; margin-left: auto; margin-right: auto; box-sizing: border-box; padding-left: 16px; padding-right: 16px; padding-bottom: 16px;">
                                                            <div class="branding-content" style="text-align: center; width: fit-content; margin: 0 auto; font-size: 16px; line-height: 1.5; color: #0D2366;">
                                                                <div class="content-element logo" style="display: inline-block; vertical-align: middle; background-color: #FFFFFF; box-sizing: border-box; line-height: 0;">@isset($brand_logo) <img src="{{$brand_logo}}" style="height: 38px; width: 38px; margin: 5px;" width="38" height="38"> @endisset</div>
                                                                <div class="content-element" style="display: inline-block; vertical-align: middle; margin-left: 10px; color: {{ $contrast_color }};">{{ $billing_label }}</div>
                                                            </div>
                                                        </div>
                                                        <div class="content title" style="width: 85%; width: calc(46000% - 211600px); max-width: 460px; min-width: 308px; margin-left: auto; margin-right: auto; box-sizing: border-box; padding-left: 16px; padding-right: 16px; background-color: #FFFFFF; padding-top: 24px;">
                                                            <div class="title-content" style="text-align: center; width: fit-content; margin: 0 auto;">
                                                                <p class="para font-color-otp font-size-normal padding-top-10" style="font-size: 16px; line-height: 1.5; color: #646D8B; margin: 0;"> is paying you {{ $purpose }} of</p>
                                                                <div class="font-color-otp font-size-large" style="font-size: 24px; line-height: 1.5; color: #646D8B;">₹ {{ $amount }}</div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                    <!--[if mso | IE]>
                    </td>
                    </tr>
                    </table>
                    <![endif]-->
                </td>
            </tr>
            </tbody>
        </table>
    </div>
    <!--[if mso | IE]>
    </td>
    </tr>
    </table>
    <table
        align="center" border="0" cellpadding="0" cellspacing="0" class="max-width-override-outlook" style="width:600px;" width="600"
    >
        <tr>
            <td style="line-height:0px;font-size:0px;mso-line-height-rule:exactly;">
    <![endif]-->
    <div class="max-width-override" style="Margin: 0px auto; max-width: unset;">
        <table align="center" border="0" cellpadding="0" cellspacing="0" role="presentation" style="width:100%;">
            <tbody>
            <tr>
                <td style="direction:ltr;font-size:0px;padding:0px;text-align:center;vertical-align:top;">
                    <!--[if mso | IE]>
                    <table role="presentation" border="0" cellpadding="0" cellspacing="0">
                        <tr>
                            <td
                                class="" style="vertical-align:top;width:600px;"
                            >
                    <![endif]-->
                    <div class="mj-column-per-100 outlook-group-fix" style="font-size:13px;text-align:left;direction:ltr;display:inline-block;vertical-align:top;width:100%;">
                        <table border="0" cellpadding="0" cellspacing="0" role="presentation" width="100%">
                            <tbody>
                            <tr>
                                <td style="vertical-align:top;padding:0px;">
                                    <table border="0" cellpadding="0" cellspacing="0" role="presentation" style="" width="100%">
                                        <tr>
                                            <td align="left" style="font-size:0px;padding:0px;word-break:break-word;">
                                                <div style="font-family:Trebuchet MS;font-size:13px;line-height:1;text-align:left;color:#000000;">
                                                    <div class="card title" style="width: 85%; width: calc(46000% - 211600px); max-width: 460px; min-width: 308px; margin-left: auto; margin-right: auto; box-sizing: border-box; padding-left: 16px; padding-right: 16px; background-color: #FFFFFF; padding-bottom: 16px;">
                                                        <div class="center-align" style="text-align: center;">
                                                            <div class="divider" style="padding: 12px 0;">
                                                                <div class="divider-line" style="height: 1px; background: #EBEDF2;"></div>
                                                            </div>
                                                            <div style="font-size: 16px; padding: 10px 0px; color: #323438; display: flex; justify-content: center; align-items: center;"><img src="https://cdn.razorpay.com/x/pl_expiry_clock.png" style="height: 20px; width: 20px; margin-right: 6px;" /> <span> Payout Link expiring soon! </span></div>
                                                            <div class="val margin-top-8 font-color-specific font-size-medium" id="account-brief" style="font-size: 14px; line-height: 1.5; color: #323438; margin-top: 8px; text-align: left;">Payout Link will expire on {{ $expire_by_date }} at {{ $expire_by_time }}. Add your account details to transfer the payment to you</div>
                                                            <div class="key" id="pl-cta"><a class="link btn font-bold" href="{{ $short_url }}" target="_blank" style="text-decoration: none; font-weight: 700; font-size: 14px; line-height: 1.5; border: 1px solid; padding: 8px 12px; letter-spacing: 1px; border-radius: 2px; overflow: hidden; min-width: 145px; display: inline-block; font-family: Trebuchet MS; margin: 24px auto 0 auto; text-align: center; width: calc(100% - 24px); background: {{ $brand_color }}; color: {{ $contrast_color }};">Give details</a></div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                    <!--[if mso | IE]>
                    </td>
                    </tr>
                    </table>
                    <![endif]-->
                </td>
            </tr>
            </tbody>
        </table>
    </div>
    <!--[if mso | IE]>
    </td>
    </tr>
    </table>
    <table
        align="center" border="0" cellpadding="0" cellspacing="0" class="max-width-override-outlook" style="width:600px;" width="600"
    >
        <tr>
            <td style="line-height:0px;font-size:0px;mso-line-height-rule:exactly;">
    <![endif]-->
    <div class="max-width-override" style="Margin: 0px auto; max-width: unset;">
        <table align="center" border="0" cellpadding="0" cellspacing="0" role="presentation" style="width:100%;">
            <tbody>
            <tr>
                <td style="direction:ltr;font-size:0px;padding:0px;text-align:center;vertical-align:top;">
                    <!--[if mso | IE]>
                    <table role="presentation" border="0" cellpadding="0" cellspacing="0">
                        <tr>
                            <td
                                class="" style="vertical-align:top;width:600px;"
                            >
                    <![endif]-->
                    <div class="mj-column-per-100 outlook-group-fix" style="font-size:13px;text-align:left;direction:ltr;display:inline-block;vertical-align:top;width:100%;">
                        <table border="0" cellpadding="0" cellspacing="0" role="presentation" width="100%">
                            <tbody>
                            <tr>
                                <td style="vertical-align:top;padding:0px;">
                                    <table border="0" cellpadding="0" cellspacing="0" role="presentation" style="" width="100%">
                                        <tr>
                                            <td align="left" style="font-size:0px;padding:0px;word-break:break-word;">
                                                <div style="font-family:Trebuchet MS;font-size:13px;line-height:1;text-align:left;color:#000000;">
                                                    <div class="card informative" style="width: 85%; width: calc(46000% - 211600px); max-width: 460px; min-width: 308px; margin-left: auto; margin-right: auto; box-sizing: border-box; padding-left: 16px; padding-right: 16px; background-color: #FFFFFF; padding-top: 20px; padding-bottom: 20px; margin-top: 8px;">
                                                        <div class="para font-color-otp font-size-medium" style="font-size: 14px; line-height: 1.5; color: #646D8B; margin: 0;">Payment For   </div>
                                                        <div class="para font-size-medium padding-top-8 font-color-specific" style="font-size: 14px; line-height: 1.5; color: #323438; margin: 0; padding-top: 8px;">{{ $description}}</div>
                                                        <div class="para font-color-otp font-size-14 margin-top-24" style="font-size: 14px; color: #646D8B; margin: 0; margin-top: 24px;">
                                                            Paying to
                                                            <p class="font-color-specific font-size-medium margin-top-16 margin-bottom-0" style="font-size: 14px; line-height: 1.5; color: #323438; margin-top: 16px; margin-bottom: 0px;"><span class="contact-label" style="display: inline-block; width: 58px; margin-right: 8px; color: #7B8199;">Name:</span><span>{{ $contact_name }}</span></p>
                                                            @isset($contact_phone) <p class="font-color-specific font-size-medium margin-y-4" style="font-size: 14px; line-height: 1.5; color: #323438; margin-top: 4px; margin-bottom: 4px;"><span class="contact-label" style="display: inline-block; width: 58px; margin-right: 8px; color: #7B8199;">Ph.No:</span><span>{{ $contact_phone }}</span></p>@endisset
                                                            @isset($contact_email) <p class="font-color-specific font-size-medium margin-y-4" style="font-size: 14px; line-height: 1.5; color: #323438; margin-top: 4px; margin-bottom: 4px;"><span class="contact-label" style="display: inline-block; width: 58px; margin-right: 8px; color: #7B8199;">Email ID:</span><span>{{ $contact_email }}</span></p>@endisset
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                    <!--[if mso | IE]>
                    </td>
                    </tr>
                    </table>
                    <![endif]-->
                </td>
            </tr>
            </tbody>
        </table>
    </div>
    <!--[if mso | IE]>
    </td>
    </tr>
    </table>
    <table
        align="center" border="0" cellpadding="0" cellspacing="0" class="max-width-override-outlook" style="width:600px;" width="600"
    >
        <tr>
            <td style="line-height:0px;font-size:0px;mso-line-height-rule:exactly;">
    <![endif]-->
    <div class="max-width-override" style="Margin: 0px auto; max-width: unset;">
        <table align="center" border="0" cellpadding="0" cellspacing="0" role="presentation" style="width:100%;">
            <tbody>
            <tr>
                <td style="direction:ltr;font-size:0px;padding:0px;text-align:center;vertical-align:top;">
                    <!--[if mso | IE]>
                    <table role="presentation" border="0" cellpadding="0" cellspacing="0">
                        <tr>
                            <td
                                class="" style="vertical-align:top;width:600px;"
                            >
                    <![endif]-->
                    <div class="mj-column-per-100 outlook-group-fix" style="font-size:13px;text-align:left;direction:ltr;display:inline-block;vertical-align:top;width:100%;">
                        <table border="0" cellpadding="0" cellspacing="0" role="presentation" width="100%">
                            <tbody>
                            <tr>
                                <td style="vertical-align:top;padding:0px;">
                                    <table border="0" cellpadding="0" cellspacing="0" role="presentation" style="" width="100%">
                                        <tr>
                                            <td align="left" style="font-size:0px;padding:0px;word-break:break-word;">
                                                <div style="font-family:Trebuchet MS;font-size:13px;line-height:1;text-align:left;color:#000000;">
                                                    <div class="card footer-card" style="width: 85%; width: calc(46000% - 211600px); max-width: 460px; min-width: 308px; margin-left: auto; margin-right: auto; box-sizing: border-box; padding-left: 16px; padding-right: 16px; background-color: #FFFFFF; background: #FBFCFF; padding: 0px; margin-top: 0px;">
                                                        <mj-section css-class="max-width-override" background-color="#FBFDFF">
                                                            <mj-column>
                                                                <mj-text>
                                                                    <div class="header" style="box-sizing: border-box; padding-top: 16px; max-width: 100%;">
                                                                        <div class="content branding rzp" style="width: 85%; width: calc(46000% - 211600px); max-width: 460px; min-width: 308px; margin-left: auto; margin-right: auto; box-sizing: border-box; padding-left: 16px; padding-right: 16px; padding-bottom: 16px;">
                                                                            <div class="branding-content" style="text-align: center; width: fit-content; margin: 0 auto; font-size: 12px; line-height: 1.5;">
                                                                                <div class="content-element font-color-otp" style="color: #646D8B; display: inline-block; vertical-align: middle;">Secured by</div>
                                                                                <div class="content-element logo" style="display: inline-block; vertical-align: middle; margin-left: 10px; margin-bottom:4px; height: 28px; width: 85px;"><img src="https://cdn.razorpay.com/x/RzpX_logo_dark.png" style="height: 100%; width: 100%;"></div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </mj-text>
                                                            </mj-column>
                                                        </mj-section>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                    <!--[if mso | IE]>
                    </td>
                    </tr>
                    </table>
                    <![endif]-->
                </td>
            </tr>
            </tbody>
        </table>
    </div>
    <!--[if mso | IE]>
    </td>
    </tr>
    </table>
    <table
        align="center" border="0" cellpadding="0" cellspacing="0" class="footer-outlook" style="width:600px;" width="600"
    >
        <tr>
            <td style="line-height:0px;font-size:0px;mso-line-height-rule:exactly;">
    <![endif]-->
    <div class="footer" style="width: 100%; Margin: 0px auto; max-width: 600px; margin-top: 8px; margin-bottom: 8px;">
        <table align="center" border="0" cellpadding="0" cellspacing="0" role="presentation" style="width:100%;">
            <tbody>
            <tr>
                <td style="direction:ltr;font-size:0px;padding:0px;text-align:center;vertical-align:top;">
                    <!--[if mso | IE]>
                    <table role="presentation" border="0" cellpadding="0" cellspacing="0">
                        <tr>
                            <td
                                class="" style="vertical-align:top;width:600px;"
                            >
                    <![endif]-->
                    <div class="mj-column-per-100 outlook-group-fix" style="font-size:13px;text-align:left;direction:ltr;display:inline-block;vertical-align:top;width:100%;">
                        <table border="0" cellpadding="0" cellspacing="0" role="presentation" width="100%">
                            <tbody>
                            <tr>
                                <td style="vertical-align:top;padding:0px;">
                                    <table border="0" cellpadding="0" cellspacing="0" role="presentation" style="" width="100%">
                                        <tr>
                                            <td align="left" style="font-size:0px;padding:0px;word-break:break-word;">
                                                <div style="font-family:Trebuchet MS;font-size:13px;line-height:1;text-align:left;color:#000000;">
                                                    <div class="footer-text" style="font-size: 12px; line-height: 1.5; color: #7B8199; text-align: center; padding: 8px 0;">
                                                        <div class="test" style="width: 100%; box-sizing: border-box;">
                                                            @isset($support_contact)<div class="value" style="display: inline-block; width: 25%;">{{ $support_contact }}</div>@endisset
                                                            @isset($support_contact)<div class="line" style="display: inline-block; width: 2%;">|</div>@endisset
                                                                @isset($support_phonenumber)<div class="value" style="display: inline-block; width: 25%;">{{ $support_phonenumber }}</div>@endisset
                                                                @isset($support_phonenumber)<div class="line" style="display: inline-block; width: 2%;">|</div>@endisset
                                                                @isset($support_email)<div class="value" style="display: inline-block; width: 25%;">{{ $support_email }}</div>@endisset
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                    <!--[if mso | IE]>
                    </td>
                    </tr>
                    </table>
                    <![endif]-->
                </td>
            </tr>
            </tbody>
        </table>
    </div>
    <!--[if mso | IE]>
    </td>
    </tr>
    </table>
    <![endif]-->
</div>
</body>
</html>
