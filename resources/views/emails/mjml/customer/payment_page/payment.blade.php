
<!doctype html>
<html xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
    <title>
        {{ $merchant['billing_label'] }}
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
<body style="background-color:#FAFAFA;">


<div style="background-color:#FAFAFA;">

    <!--[if mso | IE]>
    <table
        align="center" border="0" cellpadding="0" cellspacing="0" class="max-width-override-outlook" style="width:600px;" width="600"
    >
        <tr>
            <td style="line-height:0px;font-size:0px;mso-line-height-rule:exactly;">
    <![endif]-->


    <div class="max-width-override" style="background: {{ $merchant['brand_color'] }}; background-color: {{ $merchant['brand_color'] }}; Margin: 0px auto; max-width: unset;">

        <table align="center" border="0" cellpadding="0" cellspacing="0" role="presentation" style="background:{{ $merchant['brand_color'] }};background-color:{{ $merchant['brand_color'] }};width:100%;">
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
                                                    <div class="header" style="box-sizing: border-box; max-width: 100%;"><div class="content branding merchant" style="width: 85%; width: calc(46000% - 211600px); max-width: 460px; min-width: 308px; margin-left: auto; margin-right: auto; box-sizing: border-box; padding-left: 16px; padding-right: 16px; border-radius: 2px; padding-top: 18px; padding-bottom: 18px;"><div class="branding-content" style="text-align: center; width: fit-content; margin: 0 auto; font-size: 16px; line-height: 1.5; color: #0D2366;"><div class="content-element logo" style="display: inline-block; vertical-align: middle; background-color: #FFFFFF; box-sizing: border-box; line-height: 0;">@isset($merchant["brand_logo"]) <img src="{{$merchant['brand_logo']}}" style="height: 32px; width: 32px; margin: 7px;" width="32" height="32"> @endisset</div><div class="content-element" style="display: inline-block; vertical-align: middle; margin-left: 10px; color: {{ $merchant['contrast_color'] }};">{{ $merchant['billing_label'] }}</div></div></div><div class="content title" style="width: 85%; width: calc(46000% - 211600px); max-width: 460px; min-width: 308px; margin-left: auto; margin-right: auto; box-sizing: border-box; padding-left: 16px; padding-right: 16px; border-radius: 2px; background-color: #FFFFFF; padding-top: 12px; border-bottom-left-radius: 0; border-bottom-right-radius: 0;"><div class="title-content" style="text-align: center; width: fit-content; margin: 0 auto;"><div class="amount header" style="box-sizing: border-box; display: inline-block; max-width: 100%;"><span class="symbol" style="font-size: 24px; line-height: 1.5; color: #0D2366;">{{$invoice['payment_page']['payment']['amount_spread'][0]}}</span><span class="rupees" style="font-size: 24px; line-height: 1.5; color: #0D2366;">{{$invoice['payment_page']['payment']['amount_spread'][1]}}</span><span class="paise" style="font-size: 16px; line-height: 1.5; color: #515978;">.{{$invoice['payment_page']['payment']['amount_spread'][2]}}</span></div></div></div></div>
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
                                                    <div class="card title" style="width: 85%; width: calc(46000% - 211600px); max-width: 460px; min-width: 308px; margin-left: auto; margin-right: auto; box-sizing: border-box; padding-left: 16px; padding-right: 16px; border-radius: 2px; background-color: #FFFFFF; padding-bottom: 16px; border-top-left-radius: 0; border-top-right-radius: 0;"><div class="center-align" style="text-align: center;"><div class="para para-banner para-light" style="margin: 0; font-size: 16px; line-height: 1.5; color: #7B8199;"><div class="icon" style="width: 14px; display: inline-block; vertical-align: middle;"><img src="https://cdn.razorpay.com/static/assets/email/payment_success.png" style="height: 100%; width: 100%;"></div><div class="inline-block" style="display: inline-block;">&nbsp;Paid Successfully</div></div></div></div>
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
                                                    <div class="card merchant-highlight informative" style="width: 85%; width: calc(46000% - 211600px); max-width: 460px; min-width: 308px; margin-left: auto; margin-right: auto; box-sizing: border-box; padding-left: 16px; padding-right: 16px; border-radius: 2px; background-color: #FFFFFF; border-top-width: 2px; border-top-style: solid; padding-top: 20px; padding-bottom: 20px; border-top-color: {{ $merchant['brand_color'] }}; margin-top: 8px;"><div class="information-row" style="font-size: 14px; line-height: 1.5; width: 100%; box-sizing: border-box; padding-left: 9.3%; margin-bottom: 20px;"><div class="label" style="color: #7B8199; display: inline-block; vertical-align: top; width: 50%; width: calc((388.203px - 100%) * 388.203); max-width: 100%; min-width: 50%;">Payment Id</div><div class="value" style="color: #515978; display: inline-block; max-width: 50%;">{{$invoice['payment_page']['payment']['public_id']}}</div></div><div class="information-row" style="font-size: 14px; line-height: 1.5; width: 100%; box-sizing: border-box; padding-left: 9.3%; margin-bottom: 20px;"><div class="label" style="color: #7B8199; display: inline-block; vertical-align: top; width: 50%; width: calc((388.203px - 100%) * 388.203); max-width: 100%; min-width: 50%;">Paid On</div><div class="value" style="color: #515978; display: inline-block; max-width: 50%;">{{$invoice['payment_page']['payment']['created_at_formatted']}}</div></div><div class="information-row" style="font-size: 14px; line-height: 1.5; width: 100%; box-sizing: border-box; padding-left: 9.3%; margin-bottom: 20px;"><div class="label" style="color: #7B8199; display: inline-block; vertical-align: top; width: 50%; width: calc((388.203px - 100%) * 388.203); max-width: 100%; min-width: 50%;">Method</div><div class="value" style="color: #515978; display: inline-block; max-width: 50%;"><div>{{$invoice['payment_page']['payment']['method'][1]}}</div><div class="font-color-tertiary" style="color: #7B8199;">{{$invoice['payment_page']['payment']['method'][0]}}</div></div></div><div class="information-row" style="font-size: 14px; line-height: 1.5; width: 100%; box-sizing: border-box; padding-left: 9.3%;"><div class="label" style="color: #7B8199; display: inline-block; vertical-align: top; width: 50%; width: calc((388.203px - 100%) * 388.203); max-width: 100%; min-width: 50%;">Mobile Number</div><div class="value" style="color: #515978; display: inline-block; max-width: 50%;">{{ $invoice['customer_details']['contact'] }}</div></div></div>
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
                                                    <div class="card informative" style="width: 85%; width: calc(46000% - 211600px); max-width: 460px; min-width: 308px; margin-left: auto; margin-right: auto; box-sizing: border-box; padding-left: 16px; padding-right: 16px; border-radius: 2px; background-color: #FFFFFF; padding-top: 20px; padding-bottom: 20px; margin-top: 8px;">@foreach ($invoice['payment_page']['payment']['notes'] as $key => $val)<div class="information-row" style="font-size: 14px; line-height: 1.5; width: 100%; box-sizing: border-box; padding-left: 9.3%; margin-bottom: 20px;"><div class="label" style="color: #7B8199; display: inline-block; vertical-align: top; width: 50%; width: calc((388.203px - 100%) * 388.203); max-width: 100%; min-width: 50%;">{{ ucwords(str_replace("_", " ", $key)) }}</div><div class="value" style="color: #515978; display: inline-block; max-width: 50%;">{{ $val }}</div></div>@endforeach</div>
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
                                                    <div class="card descriptive" style="width: 85%; width: calc(46000% - 211600px); max-width: 460px; min-width: 308px; margin-left: auto; margin-right: auto; box-sizing: border-box; padding-left: 16px; padding-right: 16px; border-radius: 2px; background-color: #FFFFFF; padding-top: 20px; padding-bottom: 20px; margin-top: 8px;"><div class="description" style="text-align: center; color: #515978; font-size: 14px; line-height: 1.5;">For any product or service related queries, please contact {{$merchant['billing_label']}} support.</div></div>
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
                                                    <div class="header" style="box-sizing: border-box; max-width: 100%;">
                                                        <div class="content branding rzp" style="width: 85%; width: calc(46000% - 211600px); max-width: 460px; min-width: 308px; margin-left: auto; margin-right: auto; box-sizing: border-box; padding-left: 16px; padding-right: 16px; border-radius: 2px; padding-top: 12px; padding-bottom: 12px;">
                                                            <div class="branding-content" style="text-align: center; width: fit-content; margin: 0 auto; font-size: 12px; line-height: 1.5; color: #8D92A4;">
                                                                @if(isset($org) === true and empty($org['branding']) === false and empty($org['branding']['branding_logo']) === false)
                                                                    <div class="content-element logo" style="vertical-align: middle; height: 28px; margin: 4px auto;">
                                                                        <img style="height: 100%;" src="{{$org['branding']['branding_logo']}}"></a>
                                                                    </div>
                                                                    <div class="content-element" style="display: inline-block; vertical-align: middle;">Powered by Razorpay</div>
                                                                @else
                                                                    <div class="content-element" style="display: inline-block; vertical-align: middle;">Powered by</div>
                                                                    <div class="content-element logo" style="display: inline-block; vertical-align: middle; height: 18px; width: 85px; margin-left: 5px;">
                                                                        <img src="https://cdn.razorpay.com/logo.png" style="height: 100%; width: 100%;">
                                                                    </div>
                                                                @endif
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
    <![endif]-->


</div>

</body>
</html>
