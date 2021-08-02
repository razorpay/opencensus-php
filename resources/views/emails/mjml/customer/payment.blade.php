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
      style="background: {{ $merchant['brand_color'] }}; background-color: {{ $merchant['brand_color'] }}; Margin: 0px auto; max-width: unset;">

      <table align="center" border="0" cellpadding="0" cellspacing="0" role="presentation"
        style="background:{{ $merchant['brand_color'] }};background-color:{{ $merchant['brand_color'] }};width:100%;">
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
                                          style="font-size: 24px; line-height: 1.5; color: #0D2366;">{{$payment['gateway_amount_spread'][0]}}</span><span
                                          class="rupees"
                                          style="font-size: 24px; line-height: 1.5; color: #0D2366;">{{$payment['gateway_amount_spread'][1]}}</span><span
                                          class="paise"
                                          style="font-size: 16px; line-height: 1.5; color: #515978;">.{{$payment['gateway_amount_spread'][2]}}</span>
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
                                          src="https://cdn.razorpay.com/static/assets/email/payment_success.png"
                                          style="height: 100%; width: 100%;"></div>
                                      <div class="inline-block" style="display: inline-block;">&nbsp;Paid Successfully
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
                                <div class="card merchant-highlight informative"
                                  style="width: 85%; width: calc(46000% - 211600px); max-width: 460px; min-width: 308px; margin-left: auto; margin-right: auto; box-sizing: border-box; padding-left: 16px; padding-right: 16px; border-radius: 2px; background-color: #FFFFFF; border-top-width: 0px; border-top-style: solid; padding-top: 20px; padding-bottom: 20px; border-top-color: {{ $merchant['brand_color'] }}; margin-top: 8px;">
                                  <div class="information-row"
                                    style="font-size: 14px; line-height: 1.5; width: 100%; box-sizing: border-box; padding-left: 9.3%; margin-bottom: 20px;">
                                    <div class="label"
                                      style="color: #7B8199; display: inline-block; vertical-align: top; width: 50%; width: calc((388.203px - 100%) * 388.203); max-width: 100%; min-width: 50%;">
                                      Payment Id</div>
                                    <div class="value" style="color: #515978; display: inline-block; max-width: 50%;">
                                      {{$payment['public_id']}}</div>
                                  </div>
                                  <div class="information-row"
                                    style="font-size: 14px; line-height: 1.5; width: 100%; box-sizing: border-box; padding-left: 9.3%; margin-bottom: 20px;">
                                    <div class="label"
                                      style="color: #7B8199; display: inline-block; vertical-align: top; width: 50%; width: calc((388.203px - 100%) * 388.203); max-width: 100%; min-width: 50%;">
                                      Paid On</div>
                                    <div class="value" style="color: #515978; display: inline-block; max-width: 50%;">
                                      {{$payment['created_at_formatted']}}</div>
                                  </div>
                                  <div class="information-row"
                                    style="font-size: 14px; line-height: 1.5; width: 100%; box-sizing: border-box; padding-left: 9.3%; margin-bottom: 20px;">
                                    <div class="label"
                                      style="color: #7B8199; display: inline-block; vertical-align: top; width: 50%; width: calc((388.203px - 100%) * 388.203); max-width: 100%; min-width: 50%;">
                                      Method</div>
                                    <div class="value" style="color: #515978; display: inline-block; max-width: 50%;">
                                      <div>{{$payment['method'][1]}}</div>
                                      <div class="font-color-tertiary" style="color: #7B8199;">{{$payment['method'][0]}}
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

    <!--- DCC payments ---->
    @if ($payment['dcc'] === true)
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
                                                           style="width: 85%; width: calc(46000% - 211600px); max-width: 460px; min-width: 308px; margin-left: auto; margin-right: auto; box-sizing: border-box; padding-left: 16px; padding-right: 16px; border-radius: 2px; background-color: #FFFFFF; border-top-width: 0px; border-top-style: solid; padding-top: 20px; padding-bottom: 20px; border-top-color: {{ $merchant['brand_color'] }}; margin-top: 8px;">
                                                          <div style="text-align:center">
                                                              <div style="margin:0;font-size:16px;line-height:1.5;color:#7b8199">
                                                                  <div style="display:inline-block">Currency Conversion Details</div>
                                                              </div>
                                                          </div>
                                                          <hr width="90%" color = "#EBEDF2">
                                                          <div class="information-row"
                                                               style="font-size: 14px; line-height: 1.5; width: 100%; box-sizing: border-box; padding-left: 5.5%; margin-bottom: 20px; padding-right: 4%;">
                                                              <div class="label"
                                                                   style="color: #7B8199; display: inline-block; vertical-align: top; width: 50%; max-width: 100%; min-width: 50%;">
                                                                  Base Amount</div>
                                                              <div class="value" style="color: #515978;display: inline-block;max-width: 50%;float: right;">
                                                                  <div class="font-color-tertiary" style="color: #7B8199; text-align: right">{{$payment['dcc_base_amount']}}</div>
                                                              </div>
                                                          </div>
                                                          <div class="information-row"
                                                               style="font-size: 14px; line-height: 1.5; width: 100%; box-sizing: border-box; padding-left: 5.5%; margin-bottom: 20px;padding-right: 4%">
                                                              <div class="label"
                                                                   style="color: #7B8199; display: inline-block; vertical-align: top; width: 50%; max-width: 100%; min-width: 50%;">
                                                                  Fees</div>
                                                              <div class="value" style="color: #515978;display: inline-block;max-width: 50%;float: right;">
                                                              <div class="font-color-tertiary" style="color: #7B8199; text-align: right">{{$payment['currency_conversion_fee']}}</div>
                                                              </div>
                                                          </div>
                                                          <div class="information-row"
                                                               style="font-size: 14px; line-height: 1.5; width: 100%; box-sizing: border-box; padding-left: 5.5%; margin-bottom: 20px; padding-right: 4%">
                                                              <div class="label"
                                                                   style="color: #7B8199; display: inline-block; vertical-align: top; width: 50%; max-width: 100%; min-width: 50%;">
                                                                  Total Amount</div>
                                                              <div class="value" style="color: #515978;display: inline-block;max-width: 50%;float: right;">
                                                                  <div class="font-color-tertiary" style="color: #7B8199; text-align: right ">{{$payment['gateway_amount']}}</div>
                                                              </div>
                                                          </div>
                                                          <div>
                                                             <hr width="90%" color = "#EBEDF2">
                                                          </div>
                                                                  <div class="description"
                                                                       style="text-align: center; color: #515978; font-size: 14px; line-height: 1.5;">
                                                                  <!-- DCC Payments Disclaimer -->
                                                                      The cost of currency conversion as they may be different depending on
                                                                      whether you select your home currency or the transaction currency.
                                                                      <br />
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
    @endif
    <!------->
    <div class="max-width-override" style="Margin: 0px auto; max-width: unset;">

      <table align="center" border="0" cellpadding="0" cellspacing="0" role="presentation" style="width:100%;">
        <tbody>
          <tr>
            <td style="direction:ltr;font-size:0px;padding:0px;text-align:center;vertical-align:top;">
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
                                  style="width: 85%; width: calc(46000% - 211600px); max-width: 460px; min-width: 308px; margin-left: auto; margin-right: auto; box-sizing: border-box; padding-left: 16px; padding-right: 16px; border-radius: 2px; background-color: #FFFFFF; border-top-width: 0px; border-top-style: solid; padding-top: 20px; padding-bottom: 20px; border-top-color: {{ $merchant['brand_color'] }}; margin-top: 8px;">
                                  <div class="information-row"
                                    style="font-size: 14px; line-height: 1.5; width: 100%; box-sizing: border-box; padding-left: 9.3%;">
                                    <div class="label"
                                      style="color: #7B8199; display: inline-block; vertical-align: top; width: 50%; width: calc((388.203px - 100%) * 388.203); max-width: 100%; min-width: 50%;margin-bottom:15px;">
                                      Email</div>
                                    <div class="value" style="color: #515978; display: inline-block; max-width: 50%;">
                                      <a class="link" href="mailto:{{ $customer['email'] }}" target="_blank" style="text-decoration:none;color: #528ff0">{{ $customer['email'] }}</a>
                                  </div>
                                  </div>
                                  <div class="information-row"
                                    style="font-size: 14px; line-height: 1.5; width: 100%; box-sizing: border-box; padding-left: 9.3%;">
                                    <div class="label"
                                      style="color: #7B8199; display: inline-block; vertical-align: top; width: 50%; width: calc((388.203px - 100%) * 388.203); max-width: 100%; min-width: 50%;">
                                      Mobile Number</div>
                                    <div class="value" style="color: #515978; display: inline-block; max-width: 50%;">
                                      {{ $customer['phone'] }}</div>
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

    @isset($rewards)
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

                          <table border="0" cellpadding="0" cellspacing="0" role="presentation" width="100%">

                            <tr>
                              <td align="left" style="font-size:0px;padding:0px;word-break:break-word;">

                                <div
                                  style="font-family:Trebuchet MS;font-size:13px;line-height:1;text-align:left;color:#000000;">
                                  <div class="card merchant-highlight informative"
                                    style="width: 85%; width: calc(46000% - 211600px); max-width: 460px; min-width: 308px; margin-left: auto; margin-right: auto; box-sizing: border-box; padding-left: 25px; padding-right: 25px; border-radius: 2px; background-color: #FFFFFF; border-top-width: 0px; border-top-style: solid; padding-top: 20px; padding-bottom: 20px; border-top-color: {{ $merchant['brand_color'] }}; margin-top: 8px;">

                                      @foreach ($rewards as $reward)
                                        <div style="text-align: center; font-size: 15px; font-weight: 900; color: #525A76;">
                                            You have successfully unlocked reward from {{$reward['brand_name']}}
                                        </div>
                                        <div style="text-align: center; font-size: 15px; color: #525A76; margin-top: 7px;">
                                            Copy & Use Coupon Codes below to avail the discount
                                        </div>
                                        <div style="margin-top: 30px; text-align: center;">
                                            <div style="width: 132px; height: 125px; margin: auto; text-align: center; box-shadow: 0px 6px 10px rgb(0, 0, 0, 0.2); border-radius: 3px; background-image: url('https://cdn.razorpay.com/static/assets/email/reward_frame.png'); background-color: #F0F0F0;">
                                                @if (isset($reward["merchant_website_redirect_link"]))
                                                    <a href="https://api.razorpay.com/v1/reward/{{$reward['id']}}/{{$payment['id']}}/icon/metrics?email_variant={{$email_variant ?? ''}}" style="text-decoration: none">
                                                        <div style="height: 125px;">
                                                            <div style="margin-bottom: 11px; padding-top: 6px;">
                                                                {{$reward['brand_name']}}
                                                            </div>
                                                            <div style="margin-top: 6px; padding-left: 30px; padding-right: 30px; width: 72px; height: 72px;">
                                                                <img src="{{$reward['logo']}}" style="max-width: 70px; max-height: 70px;" />
                                                            </div>
                                                        </div>
                                                    </a>
                                                @else
                                                    <div style="margin-bottom: 11px; padding-top: 6px;">
                                                        {{$reward['brand_name']}}
                                                    </div>
                                                    <div style="margin-top: 6px; padding-left: 30px; padding-right: 30px; width: 72px; height: 72px;">
                                                        <img src="{{$reward['logo']}}" style="max-width: 70px; max-height: 70px;" />
                                                    </div>
                                                @endif
                                            </div>
                                            <div style="margin-top: 25px;">
                                                @if (isset($reward["merchant_website_redirect_link"]))
                                                    <a href="https://api.razorpay.com/v1/reward/{{$reward['id']}}/{{$payment['id']}}/coupon/metrics?email_variant={{$email_variant ?? ''}}"style="font-weight: 900; font-size: 25px; color: #2F58E4; line-height: 138%; text-decoration: none;">
                                                        {{$reward["coupon_code"]}}
                                                    </a>
                                                @else
                                                    <div style="font-weight: 900; font-size: 25px; color: #2F58E4; line-height: 138%;">
                                                        {{$reward["coupon_code"]}}
                                                    </div>
                                                @endif
                                                <div style="color: #363636; margin: 8px 70px; font-weight: 900; font-size: 14px; line-height: 138%;">
                                                    {{$reward["name"]}}
                                                </div>
                                            </div>
                                            <a href="https://api.razorpay.com/v1/reward/{{$reward['id']}}/{{$payment['id']}}/terms?coupon_code={{$reward['coupon_code']}}&email_variant={{$email_variant ?? ''}}" style="color: #3F71D7; font-weight: 500; font-size: 15px; line-height: 20px;">
                                                View T&C
                                            </a>
                                        </div>
                                      @endforeach


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
    @endisset

    @if($merchant['eligible_for_covid_relief'] == true)
      <div class="max-width-override" style="Margin: 0px auto; max-width: unset;">
        <table align="center" border="0" cellpadding="0" cellspacing="0" role="presentation" style="width:100%">
          <tbody>
            <tr>
              <td style="direction:ltr;font-size:0px;padding:0px;text-align:center;vertical-align:top">
                <div class="mj-column-per-100 outlook-group-fix" style="font-size:13px;text-align:left;direction:ltr;display:inline-block;vertical-align:top;width:100%">
                  <table border="0" cellpadding="0" cellspacing="0" role="presentation" width="100%">
                    <tbody>
                      <tr>
                        <td style="vertical-align:top;padding:0px">
                          <table border="0" cellpadding="0" cellspacing="0" role="presentation" width="100%">
                            <tr>
                              <td align="left" style="font-size:0px;padding:0px;word-break:break-word">
                                <div style="font-family:Trebuchet MS;font-size:13px;line-height:1;text-align:left;color:#11082C" >
                                  <div style="width:85%;width:calc(46000% - 211600px);max-width:460px;min-width:308px;margin-left:auto;margin-right:auto;box-sizing:border-box;padding-left:32px;padding-right:32px;border-radius:2px;background-color:#ffffff;border-top-width:0px;border-top-style:solid;padding-top:20px;padding-bottom:20px;border-top-color:#528ff0;margin-top:8px">
                                    <div align="center" style="text-align:center;">
                                      <img width="18px" alt="razorpay donate" src="https://cdn.razorpay.com/static/assets/email/heart.png">
                                    </div>
                                    <div style="text-align:center;font-size:14px;font-weight:900;margin-top: 8px; line-height: 20px;">
                                      Covid Relief Initiative by Razorpay
                                    </div>
                                    <div style="text-align:center;font-size:11px;margin-top:10px;font-weight: 400;line-height: 16px;">
                                      The entire country is fighting Covid 19 and you can help save lives.
                                      <br />
                                      Please consider donating towards covid relief, every donation matters.
                                    </div>
                                    <div style="text-align:center;font-size:12px;margin-top:15px">
                                      <a href="https://razorpay.com/links/covid19" target="_blank" style="background: linear-gradient(267.08deg, #0067FF 4.96%, #2B8DD5 102.48%); border-radius: 2px; padding: 6px 20px; box-shadow: 0px 1px 4px rgba(0, 0, 0, 0.18);text-decoration: none; font-weight: 700; line-height: 18px;color: #ffffff; display: inline-block;">
                                      Donate with Razorpay
                                      </a>
                                    </div>
                                    <div style="text-align:center;font-size:11px;margin-top:15px;margin-bottom: 15px;font-weight: 400;line-height: 16px;">
                                      Help people in need of oxygen cylinders, oximeters or medicines by sharing a small contribution.
                                    </div>
                                    <hr style="height:1px;border-width:0;color:#e9e9e9;background-color:#e9e9e9">
                                    <div style="text-align:center;font-size:11px;margin-top:14px;font-weight: 400;line-height: 149%;">
                                      Share this donation link with as many people as possible and help save lives
                                    </div>
                                    <div style="text-align:center;font-size:11px;;margin-top:3px">
                                      <a href="https://razorpay.com/links/covid19" style="color:#528FF0;font-weight:500;font-size:11px;line-height:16px" target="_blank" >
                                      https://razorpay.com/links/covid19
                                      </a>
                                    </div>
                                    <div align="center" style="text-align:center;font-size:12px;margin-top:12px">
                                    <a href="https://www.facebook.com/sharer.php?u=https://razorpay.com/links/covid19" style="text-decoration: none; margin: 6px;" target="_blank">
                                      <img height="16px" alt="razorpay facebook" src="https://cdn.razorpay.com/static/assets/email/facebook.png" style="height: 16px; width: auto">
                                      </a>
                                      <a href="https://twitter.com/intent/tweet?url=https://razorpay.com/links/covid19&text=Here%E2%80%99s%20a%20list%20of%20organisations%20doing%20incredible%20work%20in%20the%20fight%20against%20COVID-19%20in%20India!%0AYou%20can%20play%20your%20part%20by%20donating%20to%20their%20cause.%20Together%20we%20can%20make%20a%20difference." style="text-decoration: none;  margin: 6px;" target="_blank">
                                      <img height="16px" alt="razorpay twitter" src="https://cdn.razorpay.com/static/assets/email/twitter.png" style="height: 16px; width: auto">
                                      </a>
                                      <a href="https://www.linkedin.com/shareArticle?url=https://razorpay.com/links/covid19" style="text-decoration: none;  margin: 6px;" target="_blank">
                                      <img height="16px" alt="razorpay linkedin" src="https://cdn.razorpay.com/static/assets/email/linkedin.png" style="height: 16px; width: auto">
                                      </a>
                                      <a href="https://api.whatsapp.com/send?text=Here%E2%80%99s%20a%20list%20of%20organisations%20doing%20incredible%20work%20in%20the%20fight%20against%20COVID-19%20in%20India!%0AYou%20can%20play%20your%20part%20by%20donating%20to%20their%20cause.%20Together%20we%20can%20make%20a%20difference.%0Ahttps%3A%2F%2Frazorpay.com%2Flinks%2Fcovid19" style="text-decoration: none;  margin: 6px;" target="_blank">
                                      <img height="16px" alt="razorpay whatsapp" src="https://cdn.razorpay.com/static/assets/email/whatsapp.png" style="height: 16px; width: auto">
                                      </a>
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
    @endif

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
                                <div class="card descriptive"
                                  style="width: 85%; width: calc(46000% - 211600px); max-width: 460px; min-width: 308px; margin-left: auto; margin-right: auto; box-sizing: border-box; padding-left: 16px; padding-right: 16px; border-radius: 2px; background-color: #FFFFFF; padding-top: 20px; padding-bottom: 20px; margin-top: 8px; border-top-width: 2px; border-top-style: solid; padding-top: 20px; padding-bottom: 20px; border-top-color: {{ $merchant['brand_color'] }};">
                                  <div class="description"
                                    style="text-align: center; color: #515978; font-size: 14px; line-height: 1.5;">
                                    <!-- For any product or service related queries, please contact {{$merchant['billing_label']}} support. -->
                                    For any order related queries, please reach out to
                                    @if ((isset($merchant['support_details']) === true) && (empty($merchant['support_details']['url']) === false))
                                      <a class="link" href="{{$merchant['support_details']['url']}}" target="_blank" style="text-decoration: none; color: #528FF0;">{{$merchant['billing_label']}}</a>
                                    @else
                                      {{$merchant['billing_label']}}
                                    @endif
                                    @isset ($merchant['support_details'])
                                      at <a class="link" href="mailto:{{$merchant['support_details']['email']}}" target="_blank" style="text-decoration:none;color:#528ff0">{{$merchant['support_details']['email']}}</a>
                                    @if (empty($merchant['support_details']['phone']) === false)
                                      or {{$merchant['support_details']['phone']}}
                                    @endif
                                    @endisset
                                    . <br />
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

                              <div style="font-family:Trebuchet MS;font-size:13px;line-height:1;text-align:left;color:#000000;">
                                <div style="text-align: center; font-size: 14px; line-height: 18px; color: #9B9B9B; margin-top: 10px; margin-bottom: 10px;">
                                  Please report this payment if you find it to be suspicious or fraudulent
                                  <a href="{{ $merchant['report_url'] }}" style="color: #528FF0; text-decoration: none; margin-left: 6px;">
                                    <img src="https://cdn.razorpay.com/static/assets/email/flag.png" width="15px" alt="report flag" style="vertical-align: text-top;" />
                                    Report Payment
                                  </a>
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
      <table class="row footer" style="border-spacing: 0; border-collapse: collapse; vertical-align: top; text-align: left; padding: 0px; width: 100%; position: relative;">
        <tr style="padding: 0; vertical-align: top; text-align: left;">
          <td class="center" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; text-align: center; color: #aaa; font-size: 12px; line-height: 18px; padding: 0px 0px 10px;">
            <center style="width: 100%; min-width: 330px;">
              <img src="{{ $email_logo }}" style="height: 32px;" />
            </center>
          </td>
          <td class="expander" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; text-align: left; visibility: hidden; width: 0px; padding: 0 !important; color: #aaa; font-size: 12px; line-height: 18px;">
          </td>
        </tr>
      </table>
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
