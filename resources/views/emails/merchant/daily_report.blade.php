<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<meta name="viewport" content="width=device-width">
</head>
<body class="body" style="width: 100% !important; min-width: 100%; -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; padding: 0; margin: 0; text-align: left; font-size: 14px; line-height: 19px; background: #EBECEE;">
    <table class="container" style="border-spacing: 0; border-collapse: collapse; padding: 0; vertical-align: top; width: 580px; margin: 0 auto; text-align: inherit; background: #EBECEE;"><tr style="padding: 0; vertical-align: top; text-align: left;">
<td style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; padding: 0; margin: 0; text-align: left; font-size: 14px; line-height: 19px;">
  @include('emails.partials.header', ['message' => $message, 'custom_branding' => $custom_branding])
          <!-- Text Header -->
          <table class="container" style="border-spacing: 0; border-collapse: collapse; padding: 0; vertical-align: top; width: 580px; margin: 0 auto; text-align: inherit;"><tr style="padding: 0; vertical-align: top; text-align: left;">
<td style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; padding: 0; margin: 0; text-align: left; font-size: 14px; line-height: 19px;">
                <table class="row bluebg" style="border-spacing: 0; border-collapse: collapse; vertical-align: top; text-align: left; padding: 0px; width: 100%; position: relative; display: block; background: #39ACE5;"><tr style="padding: 0; vertical-align: top; text-align: left;">
<td class="wrapper offset-by-two" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; text-align: left; font-size: 14px; line-height: 19px; padding: 10px 20px 0px 0px; position: relative; padding-left: 100px;">
                      <table class="eight columns " style="border-spacing: 0; border-collapse: collapse; padding: 0; vertical-align: top; text-align: left; margin: 0 auto; width: 380px;"><tr style="padding: 0; vertical-align: top; text-align: left;">
<td class="center" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; font-size: 14px; line-height: 19px; text-align: center; padding: 0px 0px 10px;">
                            <center style="width: 100%; min-width: 380px;">
                              <h2 class="center" style="font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; padding: 0; margin: 0; line-height: 1.3; word-break: normal; text-align: center; font-size: 20px; font-weight: bold; color: white;">
                                Daily Transaction Report
                              </h2>
                            </center>
                            <center class="whitetext" style="width: 100%; color: #f2f2f2; min-width: 380px; text-align: center;">
                            for {{$date}}
                            </center>
                          </td>
                          <td class="expander" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; text-align: left; font-size: 14px; line-height: 19px; visibility: hidden; width: 0px; padding: 0 !important;"></td>
                        </tr></table>
</td>
                  </tr></table>
</td>
            </tr></table>
          @include('emails.partials.header_image', ['image' => 'report', 'alt' => 'Daily Transaction Report'], ['message' => $message])
          <!-- Header Subtext -->
          <table class="container" style="border-spacing: 0; border-collapse: collapse; padding: 0; vertical-align: top; width: 580px; margin: 0 auto; text-align: inherit;"><tr style="padding: 0; vertical-align: top; text-align: left;">
<td style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; padding: 0; margin: 0; text-align: left; font-size: 14px; line-height: 19px;">
                <table class="row bluebg" style="border-spacing: 0; border-collapse: collapse; vertical-align: top; text-align: left; background: #39ACE5; padding: 0px; width: 100%; position: relative; display: block;"><tr style="padding: 0; vertical-align: top; text-align: left;">
<td class="wrapper offset-by-two" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; text-align: left; font-size: 14px; line-height: 19px; padding: 10px 20px 0px 0px; position: relative; padding-left: 100px; background: #39ACE5;">
                      <table class="eight columns" style="border-spacing: 0; border-collapse: collapse; padding: 0; vertical-align: top; text-align: left; margin: 0 auto; width: 380px;"><tr style="padding: 0; vertical-align: top; text-align: left;">
<td class="center" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; font-size: 14px; line-height: 19px; text-align: center; padding: 0px 0px 10px;">
                            <center style="width: 100%; min-width: 380px;">
                            <h2 class="center" style="font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; padding: 0; margin: 0; line-height: 1.3; word-break: normal; font-size: 20px; font-weight: bold; color: white; text-align: center;">
                              <a href="" style="color: white; text-decoration: none;">{{{$billing_label}}}</a>
                            </h2>
                            </center>
                          </td>
                          <td class="expander" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; text-align: left; font-size: 14px; line-height: 19px; visibility: hidden; width: 0px; padding: 0 !important;"></td>
                        </tr></table>
</td>
                  </tr></table>
</td>
            </tr></table>
<table class="container" style="border-spacing: 0; border-collapse: collapse; padding: 0; vertical-align: top; width: 580px; margin: 0 auto; text-align: inherit;"><tr style="padding: 0; vertical-align: top; text-align: left;">
<td style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; padding: 0; margin: 0; text-align: left; font-size: 14px; line-height: 19px;">
                <table class="row bluebg" style="border-spacing: 0; border-collapse: collapse; vertical-align: top; text-align: left; background: #39ACE5; padding: 0px; width: 100%; position: relative; display: block;"><tr style="padding: 0; vertical-align: top; text-align: left;">
<td class="wrapper whitetext" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; text-align: left; font-size: 14px; line-height: 19px; color: #f2f2f2; padding: 10px 20px 0px 0px; position: relative;">
                      <table class="four columns" style="border-spacing: 0; border-collapse: collapse; padding: 0; vertical-align: top; text-align: left; margin: 0 auto; width: 180px;"><tr style="padding: 0; vertical-align: top; text-align: left;">
<td class="center" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; font-size: 14px; line-height: 19px; text-align: center; padding: 0px 0px 10px;"><center style="width: 100%; min-width: 180px;">
                            Captured: {{{$captured['count']}}}<br>
                            INR {{{number_format($captured['sum']/100, 2)}}}
                          </center></td>
                        </tr></table>
</td>
                    <td class="wrapper whitetext" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; text-align: left; font-size: 14px; line-height: 19px; color: #f2f2f2; padding: 10px 20px 0px 0px; position: relative;">
                      <table class="four columns" style="border-spacing: 0; border-collapse: collapse; padding: 0; vertical-align: top; text-align: left; margin: 0 auto; width: 180px;"><tr style="padding: 0; vertical-align: top; text-align: left;">
<td class="center" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; font-size: 14px; line-height: 19px; text-align: center; padding: 0px 0px 10px;"><center style="width: 100%; min-width: 180px;">
                            Authorized: {{{$authorized['count']}}}<br>
                            INR {{{number_format($authorized['sum']/100, 2)}}}
                          </center></td>
                        </tr></table>
</td>
                    <td class="wrapper last whitetext" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; text-align: left; font-size: 14px; line-height: 19px; color: #f2f2f2; padding: 10px 20px 0px 0px; position: relative; padding-right: 0px;">
                      <table class="four columns" style="border-spacing: 0; border-collapse: collapse; padding: 0; vertical-align: top; text-align: left; margin: 0 auto; width: 180px;"><tr style="padding: 0; vertical-align: top; text-align: left;">
<td class="center" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; font-size: 14px; line-height: 19px; text-align: center; padding: 0px 0px 10px;"><center style="width: 100%; min-width: 180px;">
                            Refunds: {{{$refunds['count']}}}<br>
                            INR {{{number_format($refunds['sum']/100, 2)}}}
                          </center></td>
                        </tr></table>
</td>
                    <td class="expander" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; text-align: left; font-size: 14px; line-height: 19px; visibility: hidden; width: 0px; padding: 0 !important;"></td>
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
          @if($settlements)
          <!-- Settlement Section -->
          <table class="white container" style="border-spacing: 0; border-collapse: collapse; padding: 0; vertical-align: top; background: #ffffff; background-color: #ffffff; width: 580px; margin: 0 auto; text-align: inherit;"><tr style="padding: 0; vertical-align: top; text-align: left;">
<td style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; padding: 0; margin: 0; text-align: left; font-size: 14px; line-height: 19px;">
                <table class="row" style="border-spacing: 0; border-collapse: collapse; vertical-align: top; text-align: left; padding: 0px; width: 100%; position: relative; display: block;"><tr style="padding: 0; vertical-align: top; text-align: left;">
<td class="wrapper" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; text-align: left; font-size: 14px; line-height: 19px; padding: 10px 20px 0px 0px; position: relative;">
                      <table class="ten columns" style="border-spacing: 0; border-collapse: collapse; padding: 0; vertical-align: top; text-align: left; margin: 0 auto; width: 480px;"><tr style="padding: 0; vertical-align: top; text-align: left;">
<td class="white left-text-pad right-text-pad" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; font-size: 14px; line-height: 19px; background: #ffffff; background-color: #ffffff; text-align: right; padding: 0px 0px 10px; padding-left: 10px; padding-right: 10px;">
                            <a href="https://dashboard.razorpay.com/#/app/settlements/list" style="text-decoration: none; color: #39ACE5;"><h3 style="font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; padding: 0; margin: 0; text-align: left; line-height: 1.3; word-break: normal; font-size: 16px; font-weight: bold; color: #39ACE5; margin-top: 10px;">Settlements</h3></a>
                          </td>
                          <td class="expander" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; text-align: left; font-size: 14px; line-height: 19px; visibility: hidden; width: 0px; padding: 0 !important;"></td>
                        </tr></table>
</td>
                  </tr></table>
<table class="row" style="border-spacing: 0; border-collapse: collapse; vertical-align: top; text-align: left; padding: 0px; width: 100%; position: relative; display: block;"><tr style="padding: 0; vertical-align: top; text-align: left;">
<td class="wrapper" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; text-align: left; font-size: 14px; line-height: 19px; padding: 10px 20px 0px 0px; position: relative;">
                      <table class="four columns" style="border-spacing: 0; border-collapse: collapse; padding: 0; vertical-align: top; text-align: left; margin: 0 auto; width: 180px;"><tr style="padding: 0; vertical-align: top; text-align: left;">
<td style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; text-align: left; font-size: 14px; line-height: 19px; padding: 0px 0px 10px;">
                            <p class="left-text-pad" style="color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; padding: 0; margin: 0; text-align: left; font-size: 14px; line-height: 19px; margin-bottom: 10px; padding-left: 10px;">
                              Settled Amount
                            </p>
                            <p class="left-text-pad" style="color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; padding: 0; margin: 0; text-align: left; font-size: 14px; line-height: 19px; margin-bottom: 10px; padding-left: 10px;">
                              Account Number
                            </p>
                          </td>
                          <td class="expander" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; text-align: left; font-size: 14px; line-height: 19px; visibility: hidden; width: 0px; padding: 0 !important;"></td>
                        </tr></table>
</td>
                    <td class="wrapper last" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; text-align: left; font-size: 14px; line-height: 19px; padding: 10px 20px 0px 0px; position: relative; padding-right: 0px;">
                      <table class="eight columns" style="border-spacing: 0; border-collapse: collapse; padding: 0; vertical-align: top; text-align: left; margin: 0 auto; width: 380px;"><tr style="padding: 0; vertical-align: top; text-align: left;">
<td class="lighttext" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; text-align: left; line-height: 19px; color: #B2B2B2; font-size: 12px; text-decoration: none; padding: 0px 0px 10px;">
                            <p class="right-text-pad" style="color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; padding: 0; margin: 0; font-size: 14px; line-height: 19px; margin-bottom: 10px; text-align: right; padding-right: 10px;">
                              INR {{{number_format($settlements['sum']/100, 2)}}}
                            </p>
                            <p class="right-text-pad" style="color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; padding: 0; margin: 0; font-size: 14px; line-height: 19px; margin-bottom: 10px; text-align: right; padding-right: 10px;">
                              {{{$account_number}}}
                            </p>
                          </td>
                          <td class="expander" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; text-align: left; font-size: 14px; line-height: 19px; visibility: hidden; width: 0px; padding: 0 !important;"></td>
                        </tr></table>
</td>
                  </tr></table>
</td>
            </tr></table>
<!-- There is no separator after settlements -->
          @endif
          @include('emails.partials.footer', ['message' => $message])

          @if ($custom_branding === true)
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
                                <img src="{{ $email_logo }}" style="height: 32px;" />
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
          @endif
        </td>
      </tr></table>
</body>
</html>
