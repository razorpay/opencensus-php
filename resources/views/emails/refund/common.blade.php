<!doctype html>
<html xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml"
  xmlns:o="urn:schemas-microsoft-com:office:office">

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


  <style type="text/css">
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

    <div class="max-width-override" style="background: {{ $merchant['brand_color'] }}; background-color: {{ $merchant['brand_color'] }}; Margin: 0px auto; max-width: unset;">
      <table align="center" border="0" cellpadding="0" cellspacing="0" role="presentation" style="background:{{ $merchant['brand_color'] }};background-color:{{ $merchant['brand_color'] }};width:100%;">
        <tbody>
          <tr>
            <td style="direction:ltr;font-size:0px;padding:0px;text-align:center;vertical-align:top;">
              <div class="mj-column-per-100 outlook-group-fix" style="font-size:13px;text-align:left;direction:ltr;display:inline-block;vertical-align:top;width:100%;">
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
                                        @isset($merchant["brand_logo"]) <img src="{{$merchant['brand_logo']}}"
                                          style="height: 32px; width: 32px; margin: 7px;" width="32" height="32">
                                        @endisset</div>
                                      <div class="content-element"
                                        style="display: inline-block; vertical-align: middle; margin-left: 10px; color: {{ $merchant['contrast_color'] }};">
                                        {{ $merchant['billing_label'] }}</div>
                                    </div>
                                  </div>
                                  <div class="content title"
                                    style="width: 85%; width: calc(46000% - 211600px); max-width: 460px; min-width: 308px; margin-left: auto; margin-right: auto; box-sizing: border-box; padding-left: 16px; padding-right: 16px; border-radius: 2px; background-color: #FFFFFF; padding-top: 12px; border-bottom-left-radius: 0; border-bottom-right-radius: 0;">
                                    <div class="title-content"
                                      style="text-align: center; width: fit-content; margin: 0 auto;">
                                      <div class="amount header"
                                        style="box-sizing: border-box; display: inline-block; max-width: 100%;"><span
                                          class="symbol"
                                          style="font-size: 24px; line-height: 1.5; color: #0D2366;">{{$refund['amount_components'][0]}}</span><span
                                          class="rupees"
                                          style="font-size: 24px; line-height: 1.5; color: #0D2366;">{{$refund['amount_components'][1]}}</span><span
                                          class="paise"
                                          style="font-size: 16px; line-height: 1.5; color: #515978;">.{{$refund['amount_components'][2]}}</span>
                                      </div>
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
                                  style="width: 85%; width: calc(46000% - 211600px); max-width: 460px; min-width: 308px; margin-left: auto; margin-right: auto; box-sizing: border-box; padding-left: 16px; padding-right: 16px; border-radius: 2px; background-color: #FFFFFF; padding-bottom: 16px; border-top-left-radius: 0; border-top-right-radius: 0;">
                                  <div class="center-align" style="text-align: center;">
                                    <div class="para para-banner para-light"
                                      style="margin: 0; font-size: 16px; line-height: 1.5; color: #7B8199;">
                                      <div class="icon"
                                        style="width: 14px; display: inline-block; vertical-align: middle;"><img
                                          src="https://cdn.razorpay.com/static/assets/email/payment_refund.png"
                                          style="height: 100%; width: 100%;"></div>
                                      <div class="inline-block" style="display: inline-block;">&nbsp;Refund has been
                                        initiated</div>
                                    </div>
                                    <div class="divider" style="padding: 12px 0;">
                                      <div class="divider-line" style="height: 1px; background: #EBEDF2;"></div>
                                    </div>
                                    <p class="para font-size-medium font-color-secondary"
                                      style="font-size: 14px; line-height: 1.5; color: #515978; margin: 0;">It may take up to 5-7 business days for the credit to reflect in the
                                      customer account. You can track the&nbsp;<a class="link"
                                        href="https://razorpay.com/support/?utm_source=customer_mailer&amp;utm_medium=email&amp;utm_campaign=payment_refund#refund/{{$refund['public_id']}}"
                                        target="_blank" style="text-decoration: none; color: #528FF0;">refund status
                                        here.</a></p>
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
              <div class="mj-column-per-100 outlook-group-fix" style="font-size:13px;text-align:left;direction:ltr;display:inline-block;vertical-align:top;width:100%;">
                <table border="0" cellpadding="0" cellspacing="0" role="presentation" width="100%">
                  <tbody>
                    <tr>
                      <td style="vertical-align:top;padding:0px;">
                        <table border="0" cellpadding="0" cellspacing="0" role="presentation" width="100%">
                          <tr>
                            <td align="left" style="font-size:0px;padding:0px;word-break:break-word;">
                              <div style="font-family:Trebuchet MS;font-size:13px;line-height:1;text-align:left;color:#000000;">
                                <div class="card merchant-highlight informative" style="width: 85%; width: calc(46000% - 211600px); max-width: 460px; min-width: 308px; margin-left: auto; margin-right: auto; box-sizing: border-box; padding-left: 16px; padding-right: 16px; border-radius: 2px; background-color: #FFFFFF; border-top-width: 0; border-top-style: solid; padding-top: 20px; padding-bottom: 20px; border-top-color: {{ $merchant['brand_color'] }}; margin-top: 8px;">
                                  @isset ($payment['orderId'])
                                    <div class="information-row" style="font-size: 14px; line-height: 1.5; width: 100%; box-sizing: border-box; padding-left: 9.3%; margin-bottom: 20px;">
                                      <div class="label" style="color: #7B8199; display: inline-block; vertical-align: top; width: 50%; width: calc((388.203px - 100%) * 388.203); max-width: 100%; min-width: 50%;">
                                        Order Id
                                      </div>
                                      <div class="value" style="color: #515978; display: inline-block;">
                                        {{$payment['orderId']}}
                                      </div>
                                    </div>
                                  @endisset
                                  <div class="information-row" style="font-size: 14px; line-height: 1.5; width: 100%; box-sizing: border-box; padding-left: 9.3%; margin-bottom: 20px;">
                                    <div class="label" style="color: #7B8199; display: inline-block; vertical-align: top; width: 50%; width: calc((388.203px - 100%) * 388.203); max-width: 100%; min-width: 50%;">
                                      Refund Id
                                    </div>
                                    <div class="value" style="color: #515978; display: inline-block;">
                                      {{ $refund['public_id'] }}
                                    </div>
                                  </div>
                                  <div class="information-row" style="font-size: 14px; line-height: 1.5; width: 100%; box-sizing: border-box; padding-left: 9.3%; margin-bottom: 20px;">
                                    <div class="label"
                                      style="color: #7B8199; display: inline-block; vertical-align: top; width: 50%; width: calc((388.203px - 100%) * 388.203); max-width: 100%; min-width: 50%;">
                                      Initiated On</div>
                                    <div class="value" style="color: #515978; display: inline-block;">
                                      {{ $refund['created_at_formatted'] }}</div>
                                  </div>
                                  <div class="information-row"
                                    style="font-size: 14px; line-height: 1.5; width: 100%; box-sizing: border-box; padding-left: 9.3%; margin-bottom: 20px;">
                                    <div class="label" style="color: #7B8199; display: inline-block; vertical-align: top; width: 50%; width: calc((388.203px - 100%) * 388.203); max-width: 100%; min-width: 50%;">
                                      Payment Amount
                                    </div>
                                    <div class="value" style="color: #515978; display: inline-block;">
                                      <div class="amount value"style="font-size: 14px; line-height: 1.5; color: #515978; display: inline-block;">
                                        <span class="symbol"style="font-size: 14px; line-height: 1.5; color: inherit;">
                                          {{$payment['amount_spread'][0]}}
                                        </span>
                                        <span class="rupees" style="font-size: 14px; line-height: 1.5; color: inherit;">
                                          {{$payment['amount_spread'][1]}}
                                        </span>
                                        <span class="paise" style="font-size: 12px; line-height: 1.5; color: inherit;">.
                                          {{$payment['amount_spread'][2]}}
                                        </span>
                                      </div>
                                    </div>
                                  </div>
                                  <div class="information-row" style="font-size: 14px; line-height: 1.5; width: 100%; box-sizing: border-box; padding-left: 9.3%; margin-bottom: 20px;">
                                    <div class="label" style="color: #7B8199; display: inline-block; vertical-align: top; width: 50%; width: calc((388.203px - 100%) * 388.203); max-width: 100%; min-width: 50%;">
                                      Payment Id
                                    </div>
                                    <div class="value" style="color: #515978; display: inline-block;">
                                      {{ $payment['public_id'] }}
                                    </div>
                                  </div>
                                  <div class="information-row" style="font-size: 14px; line-height: 1.5; width: 100%; box-sizing: border-box; padding-left: 9.3%; margin-bottom: 20px;">
                                    <div class="label" style="color: #7B8199; display: inline-block; vertical-align: top; width: 50%; width: calc((388.203px - 100%) * 388.203); max-width: 100%; min-width: 50%;">
                                      Payment Via</div>
                                    <div class="value" style="color: #515978; display: inline-block;">
                                      <div>{{ $payment['method'][1] }}</div>
                                      <div class="font-color-tertiary" style="color: #7B8199;">
                                        {{ $payment['method'][0] }}</div>
                                    </div>
                                  </div>
                                  <div class="information-row" style="font-size: 14px; line-height: 1.5; width: 100%; box-sizing: border-box; padding-left: 9.3%; margin-bottom: 20px;">
                                    <div class="label" style="color: #7B8199; display: inline-block; vertical-align: top; width: 50%; width: calc((388.203px - 100%) * 388.203); max-width: 100%; min-width: 50%;">
                                      RRN
                                    </div>
                                    <div class="value" style="color: #515978; display: inline-block;">
                                      <div>Refund reference number is</div>
                                      <div>awaited from the bank</div>
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
                                <div class="card merchant-highlight informative"
                                  style="width: 85%; width: calc(46000% - 211600px); max-width: 460px; min-width: 308px; margin-left: auto; margin-right: auto; box-sizing: border-box; padding-left: 16px; padding-right: 16px; border-radius: 2px; background-color: #FFFFFF; border-top-width: 0; border-top-style: solid; padding-top: 20px; padding-bottom: 20px; border-top-color: {{ $merchant['brand_color'] }}; margin-top: 8px;">
                                  <div class="information-row"
                                    style="font-size: 14px; line-height: 1.5; width: 100%; box-sizing: border-box; padding-left: 9.3%;">
                                    <div class="label"
                                      style="color: #7B8199; display: inline-block; vertical-align: top; width: 50%; width: calc((388.203px - 100%) * 388.203); max-width: 100%; min-width: 50%;margin-bottom:15px;">
                                      Mobile Number</div>
                                    <div class="value" style="color: #515978; display: inline-block;">
                                      {{ $customer['phone'] }}</div>
                                  </div>
                                  <div class="information-row"
                                    style="font-size: 14px; line-height: 1.5; width: 100%; box-sizing: border-box; padding-left: 9.3%;">
                                    <div class="label"
                                      style="color: #7B8199; display: inline-block; vertical-align: top; width: 50%; width: calc((388.203px - 100%) * 388.203); max-width: 100%; min-width: 50%;">
                                      Email</div>
                                    <div class="value" style="color: #515978; display: inline-block;">
                                      <a class="link" href="mailto:{{ $customer['email'] }}" target="_blank" style="text-decoration:none;color: #528ff0">{{ $customer['email'] }}</a>
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
    @if ($custom_branding)
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

                                <div style="font-family:Trebuchet MS;font-size:13px;line-height:1;text-align:left;color:#000000;">
                                  <div class="information-row" style="text-align: center;">
                                    <img src="{{ $email_logo }}" style="height: 32px;" />
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
    @endif
  </div>
  </td>
  </tr>
  </tbody>
  </table>

  </div>

  </div>

</body>

</html>
