<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<meta name="viewport" content="width=device-width">
</head>
<body class="body" style="width: 100% !important; min-width: 100%; -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; padding: 0; margin: 0; text-align: left; font-size: 14px; line-height: 19px; background: #EBECEE;">
    <table class="container" style="border-spacing: 0; border-collapse: collapse; padding: 0; vertical-align: top; width: 580px; margin: 0 auto; text-align: inherit; background: #EBECEE;"><tr style="padding: 0; vertical-align: top; text-align: left;"><td style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; padding: 0; margin: 0; text-align: left; font-size: 14px; line-height: 19px;">
          @include('emails.partials.header', ['message' => $message, 'custom_branding'=>$custom_branding])
          <!-- Text Header -->
          <table class="container" style="border-spacing: 0; border-collapse: collapse; padding: 0; vertical-align: top; width: 580px; margin: 0 auto; text-align: inherit;"><tr style="padding: 0; vertical-align: top; text-align: left;">
<td style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; padding: 0; margin: 0; text-align: left; font-size: 14px; line-height: 19px;">
                <table class="row bluebg" style="border-spacing: 0; border-collapse: collapse; vertical-align: top; text-align: left; padding: 0px; width: 100%; position: relative; display: block; background: #39ACE5;"><tr style="padding: 0; vertical-align: top; text-align: left;">
<td class="wrapper offset-by-two" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; text-align: left; font-size: 14px; line-height: 19px; padding: 10px 20px 0px 0px; position: relative; padding-left: 100px;">
                      <table class="eight columns " style="border-spacing: 0; border-collapse: collapse; padding: 0; vertical-align: top; text-align: left; margin: 0 auto; width: 380px;"><tr style="padding: 0; vertical-align: top; text-align: left;">
<td class="center" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; font-size: 14px; line-height: 19px; text-align: center; padding: 0px 0px 10px;">
                            <center style="width: 100%; min-width: 380px;">
                              <h2 class="center" style="font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; padding: 0; margin: 0; line-height: 1.3; word-break: normal; text-align: center; font-size: 20px; font-weight: bold; color: white;">
                                Authorized Payments Reminder
                              </h2>
                            </center>
                          </td>
                          <td class="expander" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; text-align: left; font-size: 14px; line-height: 19px; visibility: hidden; width: 0px; padding: 0 !important;"></td>
                        </tr></table>
</td>
                  </tr></table>
</td>
            </tr></table>
          @include('emails.partials.header_image', ['image' => 'report', 'alt' => 'Authorized Payments Reminder'], ['message' => $message])
          <!-- Header Subtext -->
          <table class="container" style="border-spacing: 0; border-collapse: collapse; padding: 0; vertical-align: top; width: 580px; margin: 0 auto; text-align: inherit;"><tr style="padding: 0; vertical-align: top; text-align: left;">
<td style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; padding: 0; margin: 0; text-align: left; font-size: 14px; line-height: 19px;">
                <table class="row bluebg" style="border-spacing: 0; border-collapse: collapse; vertical-align: top; text-align: left; background: #39ACE5; padding: 0px; width: 100%; position: relative; display: block;"><tr style="padding: 0; vertical-align: top; text-align: left;">
<td class="wrapper offset-by-two" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; text-align: left; font-size: 14px; line-height: 19px; padding: 10px 20px 0px 0px; position: relative; padding-left: 100px; background: #39ACE5;">
                      <table class="eight columns" style="border-spacing: 0; border-collapse: collapse; padding: 0; vertical-align: top; text-align: left; margin: 0 auto; width: 380px;"><tr style="padding: 0; vertical-align: top; text-align: left;">
<td class="center" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; font-size: 14px; line-height: 19px; text-align: center; padding: 0px 0px 10px;">
                            <center style="width: 100%; min-width: 380px;">
                            <h2 class="center" style="font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; padding: 0; margin: 0; line-height: 1.3; word-break: normal; font-size: 20px; font-weight: bold; color: white; text-align: center;">
                              <a href="" style="color: white; text-decoration: none;">{{{$merchant['billing_label']}}}</a>
                            </h2>
                            </center>
                          </td>
                          <td class="expander" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; text-align: left; font-size: 14px; line-height: 19px; visibility: hidden; width: 0px; padding: 0 !important;"></td>
                        </tr></table>
</td>
                  </tr></table>
</td>
            </tr></table>
<!-- Header Subtext --><table class="container" style="border-spacing: 0; border-collapse: collapse; padding: 0; vertical-align: top; width: 580px; margin: 0 auto; text-align: inherit;"><tr style="padding: 0; vertical-align: top; text-align: left;">
<td style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; padding: 0; margin: 0; text-align: left; font-size: 14px; line-height: 19px;">
                <table class="row bluebg" style="border-spacing: 0; border-collapse: collapse; vertical-align: top; text-align: left; background: #39ACE5; padding: 0px; width: 100%; position: relative; display: block;"><tr style="padding: 0; vertical-align: top; text-align: left;">
<td class="wrapper offset-by-four" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; text-align: left; font-size: 14px; line-height: 19px; padding: 10px 20px 0px 0px; position: relative; padding-left: 200px;">
                      <table class="four columns" style="border-spacing: 0; border-collapse: collapse; padding: 0; vertical-align: top; text-align: left; margin: 0 auto; width: 180px;"><tr style="padding: 0; vertical-align: top; text-align: left;">
<td style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; text-align: left; font-size: 14px; line-height: 19px; padding: 0px 0px 10px;">
                            <table class="small-button" style="border-spacing: 0; border-collapse: collapse; padding: 0; vertical-align: top; text-align: left; width: 100%; overflow: hidden;"><tr style="padding: 0; vertical-align: top; text-align: left;">
<td style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; font-size: 14px; line-height: 19px; display: block; width: auto !important; text-align: center; padding: 8px 0 7px; background: #2F87C7; border: 1px solid #2284a1; color: #f2f2f2; border-bottom: 2px solid #2C7CAF; margin-bottom: 1em;">
                                  <a title="Open Dashboard" href="https://dashboard.razorpay.com" style="text-decoration: none; font-size: 16px; font-weight: normal; font-family: 'Lucida Sans', Helvetica, Arial, sans-serif !important; color: #f2f2f2 !important;">Open Dashboard</a>
                                </td>
                              </tr></table>
</td>
                        </tr></table>
</td>
                  </tr></table>
</td>
            </tr></table>
<!-- Main text --><table class="row" style="border-spacing: 0; border-collapse: collapse; vertical-align: top; text-align: left; padding: 0px; width: 100%; position: relative; display: block;"><tr style="padding: 0; vertical-align: top; text-align: left;">
<td class="center" align="center" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; padding: 0; margin: 0; font-size: 14px; line-height: 19px; text-align: center;">
                <center style="width: 100%; min-width: 580px;">
                  <table class="container" style="border-spacing: 0; border-collapse: collapse; padding: 0; vertical-align: top; width: 580px; margin: 0 auto; text-align: inherit;"><tr style="padding: 0; vertical-align: top; text-align: left;">
<td class="wrapper last white" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; text-align: left; font-size: 14px; line-height: 19px; background: #ffffff; background-color: #ffffff; padding: 10px 20px 0px 0px; position: relative; padding-right: 0px;">
                        <table class="twelve columns" style="border-spacing: 0; border-collapse: collapse; padding: 0; vertical-align: top; text-align: left; margin: 0 auto; width: 580px;"><tr style="padding: 0; vertical-align: top; text-align: left;">
<td class="darktext left-text-pad right-text-pad" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; font-size: 14px; line-height: 25px; color: #484B4C; text-align: right; padding: 0px 0px 10px; padding-left: 10px; padding-right: 10px;">
<p style="color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; padding: 0; margin: 0; text-align: left; font-size: 14px; margin-bottom: 10px; line-height: 25px;">Hi {{{$merchant['name']}}},</p>

@if($autoRefundsDisabledForMerchant === true)
<p style="color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; padding: 0; margin: 0; text-align: left; font-size: 14px; margin-bottom: 10px; line-height: 25px;">There are payments that are not captured.</p>
@elseif($final === true)
<p style="color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; padding: 0; margin: 0; text-align: left; font-size: 14px; margin-bottom: 10px; line-height: 25px;">The following payments will be refunded after 1 day if they are not captured.</p>
@else
<p style="color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; padding: 0; margin: 0; text-align: left; font-size: 14px; margin-bottom: 10px; line-height: 25px;">The following payments will be refunded after 2 days if they are not captured.</p>
@endif
<p style="color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; padding: 0; margin: 0; text-align: left; font-size: 14px; margin-bottom: 10px; line-height: 25px;">You can capture these payments by clicking on the payment link below and
capturing it in the dashboard.</p>
                            </td>
                            <td class="expander" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; text-align: left; font-size: 14px; line-height: 19px; visibility: hidden; width: 0px; padding: 0 !important;"></td>
                          </tr></table>
</td>
                    </tr></table>
</center>
              </td>
            </tr></table>
<table class="white container" style="border-spacing: 0; border-collapse: collapse; padding: 0; vertical-align: top; background: #ffffff; background-color: #ffffff; width: 580px; margin: 0 auto; text-align: inherit;">
<tr style="padding: 0; vertical-align: top; text-align: left;">
<td style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; padding: 0; margin: 0; text-align: left; font-size: 14px; line-height: 19px;">
                <table class="row" style="border-spacing: 0; border-collapse: collapse; vertical-align: top; text-align: left; padding: 0px; width: 100%; position: relative; display: block;">
<td class="shortwrapper" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; padding: 0; margin: 0; text-align: left; font-size: 14px; line-height: 19px;">
                    <table class="five columns" style="border-spacing: 0; border-collapse: collapse; padding: 0; vertical-align: top; text-align: left; margin: 0 auto; width: 230px;"><tr style="padding: 0; vertical-align: top; text-align: left;">
<td class="lighttext left-text-pad" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; text-align: left; line-height: 19px; color: #B2B2B2; font-size: 12px; text-decoration: none; padding: 0px 0px 10px; padding-left: 10px;">Payment ID</td>
                      </tr></table>
</td>
                  <td class="shortwrapper" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; padding: 0; margin: 0; text-align: left; font-size: 14px; line-height: 19px;">
                    <table class="three columns" style="border-spacing: 0; border-collapse: collapse; padding: 0; vertical-align: top; text-align: left; margin: 0 auto; width: 130px;"><tr style="padding: 0; vertical-align: top; text-align: left;">
<td class="lighttext center" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; line-height: 19px; color: #B2B2B2; font-size: 12px; text-decoration: none; text-align: center; padding: 0px 0px 10px;">Time</td>
                      </tr></table>
</td>
                  <td class="shortwrapper last" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; padding: 0; margin: 0; text-align: left; font-size: 14px; line-height: 19px; padding-right: 0px;">
                    <table class="four columns" style="border-spacing: 0; border-collapse: collapse; padding: 0; vertical-align: top; text-align: left; margin: 0 auto; width: 180px;"><tr style="padding: 0; vertical-align: top; text-align: left;">
<td class="lighttext right-text-pad" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; line-height: 19px; color: #B2B2B2; font-size: 12px; text-decoration: none; text-align: right; padding: 0px 0px 10px; padding-right: 10px;">Amount</td>
                        <td class="expander" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; text-align: left; font-size: 14px; line-height: 19px; visibility: hidden; width: 0px; padding: 0 !important;"></td>
                      </tr></table>
</td>
                </table>
</td>
            </tr>
          @foreach ($payments as $payment)
          <table class="white container" style="border-spacing: 0; border-collapse: collapse; padding: 0; vertical-align: top; background: #ffffff; background-color: #ffffff; width: 580px; margin: 0 auto; text-align: inherit;"><tr style="padding: 0; vertical-align: top; text-align: left;">
<td style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; padding: 0; margin: 0; text-align: left; font-size: 14px; line-height: 19px;">
                <table class="row" style="border-spacing: 0; border-collapse: collapse; vertical-align: top; text-align: left; padding: 0px; width: 100%; position: relative; display: block;">
<td class="shortwrapper" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; padding: 0; margin: 0; text-align: left; font-size: 14px; line-height: 19px;">
                    <table class="five columns" style="border-spacing: 0; border-collapse: collapse; padding: 0; vertical-align: top; text-align: left; margin: 0 auto; width: 230px;"><tr style="padding: 0; vertical-align: top; text-align: left;">
<td class="darktext left-text-pad" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; text-align: left; font-size: 14px; line-height: 25px; color: #484B4C; padding: 0px 0px 10px; padding-left: 10px;">
                          <a href="https://dashboard.razorpay.com/#/app/payments/{{{$payment['public_id']}}}" style="text-decoration: none; color: #39ACE5;">
                          {{{$payment['public_id']}}}
                          </a>
                        </td>
                      </tr></table>
</td>
                  <td class="shortwrapper" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; padding: 0; margin: 0; text-align: left; font-size: 14px; line-height: 19px;">
                    <table class="three columns" style="border-spacing: 0; border-collapse: collapse; padding: 0; vertical-align: top; text-align: left; margin: 0 auto; width: 130px;"><tr style="padding: 0; vertical-align: top; text-align: left;">
<td class="darktext center" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; font-size: 14px; line-height: 25px; color: #484B4C; text-align: center; padding: 0px 0px 10px;">
                          {{{\Carbon\Carbon::createFromTimestamp($payment['authorized_at'], "Asia/Kolkata")->format('jS M, h:i a')}}}
                        </td>
                      </tr></table>
</td>
                  <td class="shortwrapper last" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; padding: 0; margin: 0; text-align: left; font-size: 14px; line-height: 19px; padding-right: 0px;">
                    <table class="four columns" style="border-spacing: 0; border-collapse: collapse; padding: 0; vertical-align: top; text-align: left; margin: 0 auto; width: 180px;"><tr style="padding: 0; vertical-align: top; text-align: left;">
                            @if (isset($payment['currency'])=== true)
                                <td class="darktext right-text-pad" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; font-size: 14px; line-height: 25px; color: #484B4C; text-align: right; padding: 0px 0px 10px; padding-right: 10px;"> {{{$payment['currency']}}} {{{number_format($payment['amount']/100, 2)}}}</td>
                            @else
                                <td class="darktext right-text-pad" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; font-size: 14px; line-height: 25px; color: #484B4C; text-align: right; padding: 0px 0px 10px; padding-right: 10px;">INR {{{number_format($payment['amount']/100, 2)}}}</td>
                            @endif
                        <td class="expander" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; text-align: left; font-size: 14px; line-height: 19px; visibility: hidden; width: 0px; padding: 0 !important;"></td>
                      </tr></table>
</td>
                </table>
</td>
            </tr></table>
          @endforeach
          @include('emails.partials.footer', ['message' => $message])

        @if ($custom_branding)
          <table class="row" style="border-collapse: collapse; border-spacing: 0; padding: 0px; text-align: left; vertical-align: top; position: relative; width: 100%; display: block;">
            <tr style="padding: 0; text-align: left; vertical-align: top;">
              <td class="center" align="center" style="border-collapse: collapse !important; padding: 0; text-align: center; vertical-align: top; margin: 0;">
                <center style="min-width: 580px; width: 100%;">
                  <table class="container" style="border-collapse: collapse; border-spacing: 0; padding: 0; text-align: inherit; vertical-align: top; margin: 0 auto; width: 580px;">
                    <tr style="padding: 0; text-align: left; vertical-align: top;">
                      <td class="wrapper last white" style="border-collapse: collapse !important; padding: 10px 20px 0px 0px; text-align: left; vertical-align: top; margin: 0; background: #ffffff; background-color: #ffffff; position: relative; padding-right: 0px;">
                        <table class="twelve columns" style="border-collapse: collapse; border-spacing: 0; padding: 0; text-align: left; vertical-align: top; margin: 0 auto; width: 580px;">
                          <tr style="padding: 0; text-align: left; vertical-align: top;">
                            <td class="center lighttext" style="border-collapse: collapse !important; padding: 0px 0px 10px; text-align: center; vertical-align: top; margin: 0; font-size: 12px;">
                              <center style="min-width: 580px; width: 100%;">
                                <img src="{{ $email_logo }}" style="height: 32px;" />
                              </center>
                            </td>
                            <td class="expander" style="border-collapse: collapse !important; padding: 0 !important; text-align: left; vertical-align: top; margin: 0; visibility: hidden; width: 0px;">
                            </td>
                          </tr>
                        </table>
                      </td>
                    </tr>
                  </table>
                </center>
              </td>
            </tr>
          </table>
        @endif
    </table>
</td></tr></table>
</body>
</html>
