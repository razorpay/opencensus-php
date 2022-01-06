<!doctype html>
<html xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml"
      xmlns:o="urn:schemas-microsoft-com:office:office">

<head>
    <title>
        {{ $merchant_billing_label }}
    </title>
    <!--[if !mso]><!-- -->
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <!--<![endif]-->
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style type="text/css">
        tbody .b-demarcate .middle {
            border-bottom: 1px solid #eaeaea;
        }

        tfoot .middle {
            background: #fafafa;
            padding: 30px 20px 20px;
        }
        tfoot .title,
        tfoot .sub-title {
            text-align: center;
        }
        tfoot .title {
            font-size: 13px;
            color: #8b8b8b;
        }
        tfoot .sub-title {
            font-size: 12px;
            color: #989898;
        }

        tfoot .b-demarcate .middle {
            border-bottom: 1px solid #d6d6d6;
        }

        tfoot img {
            width: 100px;
            display: inline-block;
            float: none;
            vertical-align: bottom;
        }

        #outlook a {
            padding: 0;
        }
        .ReadMsgBody {
            width: 100%;
        }
        .ExternalClass {
            width: 100%;
        }
        .ExternalClass * {
            line-height: 100%;
        }
        body {
            margin: 0;
            padding: 0;
            -webkit-text-size-adjust: 100%;
            -ms-text-size-adjust: 100%;
        }
        table,
        td {
            border-collapse: collapse;
            mso-table-lspace: 0pt;
            mso-table-rspace: 0pt;
        }
        img {
            border: 0;
            height: auto;
            line-height: 100%;
            outline: none;
            text-decoration: none;
            -ms-interpolation-mode: bicubic;
        }
        p {
            display: block;
            margin: 13px 0;
        }
    </style>
    <!--[if !mso]><!-->
    <style type="text/css">
        @media only screen and (max-width:480px) {
            @-ms-viewport {
                width: 320px;
            }
            @viewport {
                width: 320px;
            }
        }
    </style>

    <style type="text/css">
        @media only screen and (min-width:480px) {
            .mj-column-per-100 {
                width: 100% !important;
                max-width: 100%;
            }
        }
    </style>
</head>

<body style="background-color:#FAFAFA;">
<div style="background-color:#FAFAFA;">

    <div class="max-width-override"
         style="background: #FBFDFF; background-color: #FBFDFF; Margin: 0px auto; max-width: unset;">

        <table align="center" border="0" cellpadding="0" cellspacing="0" role="presentation"
               style="background:#FBFDFF;background-color:#FBFDFF;width:100%;">
            <tbody>
            <tr>
                <td style="direction:ltr;font-size:0px;padding:0px;text-align:center;vertical-align:top;">
                </td>
            </tr>
            </tbody>
        </table>

    </div>

    <div class="max-width-override"
         style="background: {{ $merchant_brand_color }}; background-color: {{ $merchant_brand_color }}; Margin: 0px auto; max-width: unset;">

        <table align="center" border="0" cellpadding="0" cellspacing="0" role="presentation"
               style="background:{{ $merchant_brand_color }};background-color:{{ $merchant_brand_color }};width:100%;">
            <tbody>
            <tr>
                <td style="direction:ltr;font-size:0px;padding:0px;text-align:center;vertical-align:top;">

                    <div class="mj-column-per-100 outlook-group-fix"
                         style="font-size:13px;text-align:left;direction:ltr;display:inline-block;vertical-align:top;width:100%;">

                        <table border="0" cellpadding="0" cellspacing="0" role="presentation" width="100%">
                            <tbody>
                            <tr>
                                <td style="vertical-align:top;padding:0px;">

                                    <table border="0" cellpadding="0" cellspacing="0" role="presentation" style="" width="100%">

                                        <tr>
                                            <td align="left" style="font-size:0px;padding:0px;word-break:break-word;">

                                                <div
                                                    style="font-family:Trebuchet MS;font-size:13px;line-height:1;text-align:left;color:#000000;">
                                                    <div class="header" style="box-sizing: border-box; max-width: 100%;">
                                                        <div class="content branding merchant"
                                                             style="width: 85%; width: calc(46000% - 211600px); max-width: 460px; min-width: 308px; margin-left: auto; margin-right: auto; box-sizing: border-box; padding-left: 16px; padding-right: 16px; border-radius: 2px; padding-top: 18px; padding-bottom: 18px;">
                                                            <div class="branding-content"
                                                                 style="text-align: center; width: fit-content; margin: 0 auto; font-size: 16px; line-height: 1.5; color: #0D2366;">
                                                                <div class="content-element logo"
                                                                     style="display: inline-block; vertical-align: middle; background-color: #FFFFFF; box-sizing: border-box; line-height: 0;">
                                                                    @isset($merchant_brand_logo) <img src="{{$merchant_brand_logo}}"
                                                                                                      style="height: 32px; width: 32px; margin: 7px;" width="32" height="32">
                                                                    @endisset</div>
                                                                <div class="content-element"
                                                                     style="display: inline-block; vertical-align: middle; margin-left: 10px; color: {{ $merchant_contrast_color }};">
                                                                    {{ $merchant_billing_label }}</div>
                                                            </div>
                                                        </div>
                                                        <div class="content title"
                                                             style="width: 85%; width: calc(46000% - 211600px); max-width: 460px; min-width: 308px; margin-left: auto; margin-right: auto; box-sizing: border-box; padding-left: 16px; padding-right: 16px; border-radius: 2px; background-color: #FFFFFF; padding-top: 12px; border-bottom-left-radius: 0; border-bottom-right-radius: 0;">
                                                            <div class="title-content"
                                                                 style="text-align: center; width: fit-content; margin: 0 auto;">
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
                </td>
            </tr>
            </tbody>
        </table>

    </div>

    <div class="max-width-override" style="Margin: 0px auto; max-width: unset;">

        <table align="center" border="0" cellpadding="0" cellspacing="0" role="presentation" style="width:100%;">
            <tbody>
            <tr>
                <td style="direction:ltr;font-size:0px;padding:0px;text-align:center;vertical-align:top;">

                    <div class="mj-column-per-100 outlook-group-fix"
                         style="font-size:13px;text-align:left;direction:ltr;display:inline-block;vertical-align:top;width:100%;">

                        <table border="0" cellpadding="0" cellspacing="0" role="presentation" width="100%">
                            <tbody>
                            <tr>
                                <td style="vertical-align:top;padding:0px;">

                                    <table border="0" cellpadding="0" cellspacing="0" role="presentation" style="" width="100%">

                                        <tr>
                                            <td align="left" style="font-size:0px;padding:0px;word-break:break-word;">

                                                <div
                                                    style="font-family:Trebuchet MS;font-size:13px;line-height:1;text-align:left;color:#000000;">
                                                    <div class="card title"
                                                         style="width: 85%; width: calc(46000% - 211600px); max-width: 460px; padding-top: 16px; min-width: 308px; margin-left: auto; margin-right: auto; box-sizing: border-box; padding-left: 16px; padding-right: 16px; border-radius: 2px; background-color: #FFFFFF; padding-bottom: 16px; border-top-left-radius: 0; border-top-right-radius: 0;">
                                                        <div class="center-align" style="text-align: center;">
                                                            <div class="para para-banner para-light"
                                                                 style="margin: 0; text-align: center; font-size: 16px; line-height: 1.5; color: #7B8199; border-bottom: 1px solid #747a9347; padding-bottom: 16px;">
                                                                <div class="icon"
                                                                     style="width: 32px; display: inline-block; vertical-align: middle; margin-right: 10px;"><img
                                                                        src="https://cdn.razorpay.com/static/assets/email/payment_success.png"
                                                                        style="height: 100%; width: 100%;"></div>
                                                                <div>
                                                                    <div class="amount header"
                                                                         style="box-sizing: border-box; display: inline-block; max-width: 100%;"><span
                                                                            class="symbol"
                                                                            style="font-size: 24px; line-height: 1.5; color: #0D2366;">{{$payout_amount[0]}}</span><span
                                                                            class="rupees"
                                                                            style="font-size: 24px; line-height: 1.5; color: #0D2366;">{{$payout_amount[1]}}</span><span
                                                                            class="paise"
                                                                            style="font-size: 16px; line-height: 1.5; color: #515978;">.{{$payout_amount[2]}}</span>
                                                                    </div>
                                                                </div>
                                                                <div class="inline-block" style="display: inline-block;">&nbsp;Received Successfully
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="card title"
                                                         style="width: 85%; width: calc(46000% - 211600px); max-width: 460px; min-width: 308px; margin-left: auto; margin-right: auto; box-sizing: border-box; padding-left: 16px; padding-right: 16px; border-radius: 2px; background-color: #FFFFFF; border-top-left-radius: 0; border-top-right-radius: 0;">
                                                        <div class="para para-banner para-light"
                                                             style="margin: 0; font-size: 15px; line-height: 1.5; color: #7B8199; padding-bottom: 16px;">
                                                            <p style="margin-top: 0;">You have received <strong>{{ $payout_amount[0] }} {{ $payout_amount[1] }}.{{ $payout_amount[2] }}</strong> from <strong>{{ $merchant_billing_label }}</strong>. Please find details below.</p>
                                                            <p style="margin-bottom: 0;">If you haven't received this amount, kindly contact your bank with the UTR.</p>
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
                </td>
            </tr>
            </tbody>
        </table>

    </div>

    <div class="max-width-override" style="Margin: 0px auto; max-width: unset;">

        <table align="center" border="0" cellpadding="0" cellspacing="0" role="presentation" style="width:100%;">
            <tbody>
            <tr>
                <td style="direction:ltr;font-size:0px;padding:0px;text-align:center;vertical-align:top;">

                    <div class="mj-column-per-100 outlook-group-fix"
                         style="font-size:13px;text-align:left;direction:ltr;display:inline-block;vertical-align:top;width:100%;">

                        <table border="0" cellpadding="0" cellspacing="0" role="presentation" width="100%">
                            <tbody>
                            <tr>
                                <td style="vertical-align:top;padding:0px;">

                                    <table border="0" cellpadding="0" cellspacing="0" role="presentation" style="" width="100%">

                                        <tr>
                                            <td align="left" style="font-size:0px;padding:0px;word-break:break-word;">

                                                <div
                                                    style="font-family:Trebuchet MS;font-size:13px;line-height:1;text-align:left;color:#000000;">
                                                    <div class="card merchant-highlight informative"
                                                         style="width: 85%; width: calc(46000% - 211600px); max-width: 460px; min-width: 308px; margin-left: auto; margin-right: auto; box-sizing: border-box; padding-left: 16px; padding-right: 16px; border-radius: 2px; background-color: #FFFFFF; border-top-width: 0px; border-top-style: solid; padding-top: 20px; padding-bottom: 20px; border-top-color: {{ $merchant_brand_color }}; margin-top: 8px;">
                                                        <div class="information-row"
                                                             style="font-size: 16px; text-align: left; font-weight: 600; line-height: 1.5; width: 100%; box-sizing: border-box; margin-bottom: 20px; padding-bottom: 16px;">
                                                            <p style="margin: 0;">Transfer details</p>
                                                        </div>
                                                        <div class="information-row"
                                                             style="font-size: 14px; border-bottom: 1px solid #747a9347; line-height: 1.5; font-weight: 500; width: 100%; box-sizing: border-box; margin-bottom: 10px; padding-bottom: 10px">
                                                            <div class="label"
                                                                 style="color: #7B8199; display: inline-block; vertical-align: top; width: 50%; width: calc((388.203px - 100%) * 388.203); max-width: 100%; min-width: 50%;">
                                                                UTR</div>
                                                            <div class="value" style="color: #515978; font-weight: bolder; display: inline-block; font-weight: 500; max-width: 50%;">
                                                                {{$payout_utr}}</div>
                                                        </div>
                                                        <div class="information-row"
                                                             style="font-size: 14px; border-bottom: 1px solid #747a9347; line-height: 1.5; font-weight: 500; width: 100%; box-sizing: border-box; margin-bottom: 10px; padding-bottom: 10px">
                                                            <div class="label"
                                                                 style="color: #7B8199; display: inline-block; vertical-align: top; width: 50%; width: calc((388.203px - 100%) * 388.203); max-width: 100%; min-width: 50%;">
                                                                Amount</div>
                                                            <div class="value" style="color: #515978; font-weight: bolder; display: inline-block; font-weight: 500; max-width: 50%;">
                                                                {{ $payout_amount[0] }} {{ $payout_amount[1] }}.{{ $payout_amount[2] }}</div>
                                                        </div>
                                                        @isset($payout_reference_id)
                                                            <div class="information-row"
                                                                 style="font-size: 14px; border-bottom: 1px solid #747a9347; line-height: 1.5; font-weight: 500; width: 100%; box-sizing: border-box; margin-bottom: 10px; padding-bottom: 10px">
                                                                <div class="label"
                                                                     style="color: #7B8199; display: inline-block; vertical-align: top; width: 50%; width: calc((388.203px - 100%) * 388.203); max-width: 100%; min-width: 50%;">
                                                                    Reference Id</div>
                                                                <div class="value" style="color: #515978; font-weight: bolder; display: inline-block; font-weight: 500; max-width: 50%;">
                                                                    {{ $payout_reference_id }}</div>
                                                            </div>
                                                        @endisset
                                                        <div class="information-row"
                                                             style="font-size: 14px; border-bottom: 1px solid #747a9347; line-height: 1.5; font-weight: 500; width: 100%; box-sizing: border-box; margin-bottom: 10px; padding-bottom: 10px">
                                                            <div class="label"
                                                                 style="color: #7B8199; display: inline-block; vertical-align: top; width: 50%; width: calc((388.203px - 100%) * 388.203); max-width: 100%; min-width: 50%;">
                                                                Paid via</div>
                                                            <div class="value" style="color: #515978; font-weight: bolder; display: inline-block; font-weight: 500; max-width: 50%;">
                                                                {{ $payout_mode }}</div>
                                                        </div>
                                                        <div class="information-row"
                                                             style="font-size: 14px; border-bottom: 1px solid #747a9347; line-height: 1.5; font-weight: 500; width: 100%; box-sizing: border-box; margin-bottom: 10px; padding-bottom: 10px">
                                                            <div class="label"
                                                                 style="color: #7B8199; display: inline-block; vertical-align: top; width: 50%; width: calc((388.203px - 100%) * 388.203); max-width: 100%; min-width: 50%;">
                                                                Paid on</div>
                                                            <div class="value" style="color: #515978; font-weight: bolder; display: inline-block; font-weight: 500; max-width: 50%;">
                                                                {{ $payout_processed_at }}</div>
                                                        </div>
                                                        @if(count($payout_notes) > 0)
                                                            <div class="information-row"
                                                                 style="font-size: 14px; line-height: 1.5; font-weight: 500; width: 100%; box-sizing: border-box; margin-bottom: 10px; padding-bottom: 10px;">
                                                                <div class="label"
                                                                     style="color: #7B8199; display: inline-block; vertical-align: top; width: 50%; width: calc((388.203px - 100%) * 388.203); max-width: 100%; min-width: 50%;">
                                                                    Notes</div>
                                                                <div class="value" style="color: #515978; font-weight: bolder; display: inline-block; font-weight: 500; max-width: 50%;">
                                                                    <table>
                                                                        @foreach($payout_notes as $key => $value)
                                                                            <tr>
                                                                                <td class="text-left">
                                                                                    {{ $key }}:
                                                                                </td>
                                                                                <td class="text-center">
                                                                                    {{ $value }}
                                                                                </td>
                                                                            </tr>
                                                                        @endforeach
                                                                    </table>
                                                                </div>
                                                            </div>
                                                        @endif

                                                    </div>
                                                    <div style="width: 85%; width: calc(46000% - 211600px); max-width: 460px; min-width: 308px; margin-left: auto; margin-right: auto; box-sizing: border-box; background-color: #f5f5f5; padding: 10px;">
                                                        <p style="vertical-align:top; line-height: 20px; text-align: center; color: #8b8b8b;">Powered by <img style="height: 20px; vertical-align: bottom;" src="https://cdn.razorpay.com/static/assets/logo/rzpX/rzpX-dark.png" alt="RazorpayX" /></p>
                                                        <p style="text-align: center; color: #8b8b8b;">Powerfully Simple Business Banking! <a style="text-decoration: none;" href="{{ $learn_more_url }}">Learn more</a></p>
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
                </td>
            </tr>
            </tbody>
        </table>

    </div>

    <div class="max-width-override" style="Margin: 0px auto; max-width: unset; text-align: center; color: #8b8b8b;">
        <div style="display: inline-block;">
            <table align="center" border="0" cellpadding="0" cellspacing="0" style="width:100%;">
                <tbody>
                <tr style="font-family:Trebuchet MS; font-size: 14px;">
                    <td style="direction:ltr;padding:20px;text-align:left;vertical-align:top;">
                        <div>
                            <a class="link" href="{{ $merchant_website }}" target="_blank" style="text-decoration:none;color: inherit">{{ $merchant_website }}</a>
                        </div>
                    </td>
                    <td style="direction:ltr;padding:20px;text-align:right;vertical-align:top;">
                        <div>{{$merchant_phone }}</div>
                    </td>
                    <td style="direction:ltr;padding:20px;text-align:right;vertical-align:top;">
                        <div><a class="link" href="mailto:{{ $merchant_email }}" target="_blank" style="text-decoration:none;color: inherit">{{ $merchant_email }}</a></div>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>

    </div>

</div>

</td>
</tr>
</tbody>
</table>

</div>

</div>

</body>

</html>
