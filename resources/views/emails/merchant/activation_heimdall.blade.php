<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<meta name="viewport" content="width=device-width">
</head>
<body class="body" style="width: 100% !important; min-width: 100%; -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; padding: 0; margin: 0; text-align: left; font-size: 14px; line-height: 19px; background: #EBECEE;">
    <table class="container" style="border-spacing: 0; border-collapse: collapse; padding: 0; vertical-align: top; width: 580px; margin: 0 auto; text-align: inherit; background: #EBECEE;"><tr style="padding: 0; vertical-align: top; text-align: left;">
<td style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; padding: 0; margin: 0; text-align: left; font-size: 14px; line-height: 19px;">
          @include('emails.partials.header', ['message'=>$message])
          <!-- Activation Header -->
          <table class="row" style="border-spacing: 0; border-collapse: collapse; vertical-align: top; text-align: left; padding: 0px; width: 100%; position: relative; display: block;"><tr style="padding: 0; vertical-align: top; text-align: left;">
<td class="center" align="center" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; padding: 0; margin: 0; font-size: 14px; line-height: 19px; text-align: center;">
                <center style="width: 100%; min-width: 580px;">
                  <table class="container" style="border-spacing: 0; border-collapse: collapse; padding: 0; vertical-align: top; width: 580px; margin: 0 auto; text-align: inherit;"><tr style="padding: 0; vertical-align: top; text-align: left;">
<td class="wrapper center last" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; font-size: 14px; line-height: 19px; padding: 10px 20px 0px 0px; position: relative; text-align: center; padding-right: 0px;">
                        <center style="width: 100%; min-width: 580px;">
                        <table class="twelve columns bluebg" style="border-spacing: 0; border-collapse: collapse; padding: 0; vertical-align: top; text-align: left; margin: 0 auto; width: 580px; background: #39ACE5;"><tr style="padding: 0; vertical-align: top; text-align: left;">
<td class="center" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; font-size: 14px; line-height: 19px; text-align: center; padding: 0px 0px 10px;">
                              <h1 style="font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; padding: 0; margin: 0; line-height: 1.3; word-break: normal; margin-top: 40px; color: #f2f2f2; font-size: 32px; text-align: center; font-weight: bold;">
                              <center style="width: 100%; min-width: 580px;">
                                Congratulations!
                              </center>
                              </h1>
                              <center class="whitetext" style="width: 100%; color: #f2f2f2; min-width: 580px; text-align: center;">
                              Your {{{$merchant['org']['business_name']}}} account has been activated.
                              </center>
                            </td>
                            <td class="expander" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; text-align: left; font-size: 14px; line-height: 19px; visibility: hidden; width: 0px; padding: 0 !important;"></td>
                          </tr></table>
</center>
                      </td>
                    </tr></table>
</center>
              </td>
            </tr></table>
          @include('emails.partials.header_image', ['image'=>'merchant_verified'], ['message'=>$message])
          <!-- Header Subtext -->
          <table class="row" style="border-spacing: 0; border-collapse: collapse; vertical-align: top; text-align: left; padding: 0px; width: 100%; position: relative; display: block;"><tr style="padding: 0; vertical-align: top; text-align: left;">
<td class="center" align="center" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; padding: 0; margin: 0; font-size: 14px; line-height: 19px; text-align: center;">
                <center style="width: 100%; min-width: 580px;">
                  <table class="container" style="border-spacing: 0; border-collapse: collapse; padding: 0; vertical-align: top; width: 580px; margin: 0 auto; text-align: inherit;"><tr style="padding: 0; vertical-align: top; text-align: left;">
<td class="wrapper last bluebg" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; text-align: left; font-size: 14px; line-height: 19px; padding: 10px 20px 0px 0px; position: relative; padding-right: 0px; background: #39ACE5;">
                        <table class="twelve columns" style="border-spacing: 0; border-collapse: collapse; padding: 0; vertical-align: top; text-align: left; margin: 0 auto; width: 580px;"><tr style="padding: 0; vertical-align: top; text-align: left;">
<td class="center" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; font-size: 14px; line-height: 19px; text-align: center; padding: 0px 0px 10px;">
                              <center class="whitetext" style="width: 100%; color: #f2f2f2; min-width: 580px; text-align: center;">
                              You can now start accepting payments from your customers.
                              </center>
                              <center style="width: 100%; min-width: 580px;">
                              </center>
                            </td>
                            <td class="expander" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; text-align: left; font-size: 14px; line-height: 19px; visibility: hidden; width: 0px; padding: 0 !important;"></td>
                          </tr></table>
</td>
                    </tr></table>
</center>
              </td>
            </tr></table>
<!-- Header Subtext --><table class="row" style="border-spacing: 0; border-collapse: collapse; vertical-align: top; text-align: left; padding: 0px; width: 100%; position: relative; display: block;"><tr style="padding: 0; vertical-align: top; text-align: left;">
<td class="center" align="center" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; padding: 0; margin: 0; font-size: 14px; line-height: 19px; text-align: center;">
                <center style="width: 100%; min-width: 580px;">
                  <table class="container" style="border-spacing: 0; border-collapse: collapse; padding: 0; vertical-align: top; width: 580px; margin: 0 auto; text-align: inherit;"><tr style="padding: 0; vertical-align: top; text-align: left;">
<td class="wrapper last bluebg" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; text-align: left; font-size: 14px; line-height: 19px; padding: 10px 20px 0px 0px; position: relative; padding-right: 0px; background: #39ACE5;">
                        <table class="three columns" style="border-spacing: 0; border-collapse: collapse; padding: 0; vertical-align: top; text-align: left; margin: 0 auto; width: 130px;"><tr style="padding: 0; vertical-align: top; text-align: left;">
<td style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; text-align: left; font-size: 14px; line-height: 19px; padding: 0px 0px 10px;">
                              <table class="tiny-button" style="border-spacing: 0; border-collapse: collapse; padding: 0; vertical-align: top; text-align: left; width: 100%; overflow: hidden;"><tr style="padding: 0; vertical-align: top; text-align: left;">
<td style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; font-size: 14px; line-height: 19px; display: block; width: auto !important; text-align: center; padding: 5px 0 4px; background: #2F87C7; border: 1px solid #2284a1; color: #f2f2f2; border-bottom: 2px solid #2C7CAF; margin-bottom: 1em;">
                                    <a href="{{{$merchant['org']['hostname']}}}" style="text-decoration: none; font-size: 12px; font-weight: normal; font-family: 'Lucida Sans', Helvetica, Arial, sans-serif !important; color: #f2f2f2 !important;">Go to Dashboard</a>
                                  </td>
                                </tr></table>
</td>
                          </tr></table>
</td>
                    </tr></table>
</center>
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

<p style="color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; padding: 0; margin: 0; text-align: left; font-size: 14px; margin-bottom: 10px; line-height: 25px;">Your {{{$merchant['org']['business_name']}}} account for

@if (isset($merchant['website']) === true)
<a title="Merchant Website" href="{{$merchant['website']}}" style="color: #2ba6cb; text-decoration: none;">{{{$merchant['billing_label']}}}</a>
@else
{{{$merchant['billing_label']}}}
@endif

is now active.

</p>
<p style="color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; padding: 0; margin: 0; text-align: left; font-size: 14px; margin-bottom: 10px; line-height: 25px;">In case you haven't integrated our API in your application, the instructions can be found <a href="https://razorpay.com/docs" title="Razorpay Integration Documentation" style="color: #2ba6cb; text-decoration: none;">here</a>. Please ensure that your production website/app is using the live keys generated from the dashboard.</p>

<p style="color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; padding: 0; margin: 0; text-align: left; font-size: 14px; margin-bottom: 10px; line-height: 25px;">If you face any issues while implementing this, feel free to reach out to us <a href="https://dashboard.razorpay.com/#/app/dashboard#request" style="color: #2ba6cb; text-decoration: none;">here</a>.</p>

<p style="color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; padding: 0; margin: 0; text-align: left; font-size: 14px; margin-bottom: 10px; line-height: 25px;">We hope that the association between you and {{{$merchant['org']['business_name']}}} will be fruitful for both organizations.</p>

<p style="color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; padding: 0; margin: 0; text-align: left; font-size: 14px; margin-bottom: 10px; line-height: 25px;">
Regards,<br>
Team {{{$merchant['org']['business_name']}}}
</p>
                            </td>
                            <td class="expander" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; text-align: left; font-size: 14px; line-height: 19px; visibility: hidden; width: 0px; padding: 0 !important;"></td>
                          </tr></table>
</td>
                    </tr></table>
</center>
              </td>
            </tr></table>
          @if ($merchant['org']['custom_code'] === 'rzp')
            @include('emails.partials.footer', ['message'=>$message])
          @endif
        </td>
      </tr></table>
</body>
</html>
