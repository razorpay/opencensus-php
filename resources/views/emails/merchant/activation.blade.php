<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml"><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"><meta name="viewport" content="width=device-width"></head><body class="body" style="-ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%; margin: 0; min-width: 100%; padding: 0; width: 100% !important; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; line-height: 19px; text-align: left; font-size: 14px; background: #EBECEE;">
    <table class="container" style="border-collapse: collapse; border-spacing: 0; padding: 0; text-align: inherit; vertical-align: top; margin: 0 auto; width: 580px; background: #EBECEE;"><tr style="padding: 0; text-align: left; vertical-align: top;"><td style="-moz-hyphens: auto; -webkit-hyphens: auto; border-collapse: collapse !important; hyphens: auto; word-break: break-word; padding: 0; text-align: left; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; line-height: 19px; margin: 0; font-size: 14px;">
          @include('emails.partials.header', ['message'=>$message])
          <!-- Activation Header -->
          <table class="row" style="border-collapse: collapse; border-spacing: 0; padding: 0px; text-align: left; vertical-align: top; position: relative; width: 100%; display: block;"><tr style="padding: 0; text-align: left; vertical-align: top;"><td class="center" align="center" style="-moz-hyphens: auto; -webkit-hyphens: auto; border-collapse: collapse !important; hyphens: auto; word-break: break-word; padding: 0; text-align: center; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; line-height: 19px; margin: 0; font-size: 14px;">
                <center style="min-width: 580px; width: 100%;">
                  <table class="container" style="border-collapse: collapse; border-spacing: 0; padding: 0; text-align: inherit; vertical-align: top; margin: 0 auto; width: 580px;"><tr style="padding: 0; text-align: left; vertical-align: top;"><td class="wrapper center last" style="-moz-hyphens: auto; -webkit-hyphens: auto; border-collapse: collapse !important; hyphens: auto; word-break: break-word; padding: 10px 20px 0px 0px; text-align: center; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; line-height: 19px; margin: 0; font-size: 14px; position: relative; padding-right: 0px;">
                        <center style="min-width: 580px; width: 100%;">
                        <table class="twelve columns bluebg" style="border-collapse: collapse; border-spacing: 0; padding: 0; text-align: left; vertical-align: top; background: #39ACE5; margin: 0 auto; width: 580px;"><tr style="padding: 0; text-align: left; vertical-align: top;"><td class="center" style="-moz-hyphens: auto; -webkit-hyphens: auto; border-collapse: collapse !important; hyphens: auto; word-break: break-word; padding: 0px 0px 10px; text-align: center; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; line-height: 19px; margin: 0; font-size: 14px;">
                              <h1 style="color: #f2f2f2; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: bold; line-height: 1.3; margin: 0; padding: 0; text-align: center; word-break: normal; font-size: 32px; margin-top: 40px;">
                              <center style="min-width: 580px; width: 100%;">
                                Congratulations!
                              </center>
                              </h1>
                              <center class="whitetext" style="min-width: 580px; width: 100%; color: #f2f2f2; text-align: center;">
                              Your {{{$merchant['org']['business_name']}}} account has been activated.
                              </center>
                            </td>
                            <td class="expander" style="-moz-hyphens: auto; -webkit-hyphens: auto; border-collapse: collapse !important; hyphens: auto; word-break: break-word; padding: 0 !important; text-align: left; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; line-height: 19px; margin: 0; font-size: 14px; visibility: hidden; width: 0px;"></td>
                          </tr></table></center>
                      </td>
                    </tr></table></center>
              </td>
            </tr></table>
          @include('emails.partials.header_image', ['image'=>'merchant_verified'], ['message'=>$message])
          <!-- Header Subtext -->
          <table class="row" style="border-collapse: collapse; border-spacing: 0; padding: 0px; text-align: left; vertical-align: top; position: relative; width: 100%; display: block;"><tr style="padding: 0; text-align: left; vertical-align: top;"><td class="center" align="center" style="-moz-hyphens: auto; -webkit-hyphens: auto; border-collapse: collapse !important; hyphens: auto; word-break: break-word; padding: 0; text-align: center; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; line-height: 19px; margin: 0; font-size: 14px;">
                <center style="min-width: 580px; width: 100%;">
                  <table class="container" style="border-collapse: collapse; border-spacing: 0; padding: 0; text-align: inherit; vertical-align: top; margin: 0 auto; width: 580px;"><tr style="padding: 0; text-align: left; vertical-align: top;"><td class="wrapper last bluebg" style="-moz-hyphens: auto; -webkit-hyphens: auto; border-collapse: collapse !important; hyphens: auto; word-break: break-word; padding: 10px 20px 0px 0px; text-align: left; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; line-height: 19px; margin: 0; font-size: 14px; background: #39ACE5; position: relative; padding-right: 0px;">
                        <table class="twelve columns" style="border-collapse: collapse; border-spacing: 0; padding: 0; text-align: left; vertical-align: top; margin: 0 auto; width: 580px;"><tr style="padding: 0; text-align: left; vertical-align: top;"><td class="center" style="-moz-hyphens: auto; -webkit-hyphens: auto; border-collapse: collapse !important; hyphens: auto; word-break: break-word; padding: 0px 0px 10px; text-align: center; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; line-height: 19px; margin: 0; font-size: 14px;">
                              <center class="whitetext" style="min-width: 580px; width: 100%; color: #f2f2f2; text-align: center;">
                              You can now start accepting payments from your customers.
                              </center>
                              <center style="min-width: 580px; width: 100%;">
                              </center>
                            </td>
                            <td class="expander" style="-moz-hyphens: auto; -webkit-hyphens: auto; border-collapse: collapse !important; hyphens: auto; word-break: break-word; padding: 0 !important; text-align: left; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; line-height: 19px; margin: 0; font-size: 14px; visibility: hidden; width: 0px;"></td>
                          </tr></table></td>
                    </tr></table></center>
              </td>
            </tr></table><!-- Header Subtext --><table class="row" style="border-collapse: collapse; border-spacing: 0; padding: 0px; text-align: left; vertical-align: top; position: relative; width: 100%; display: block;"><tr style="padding: 0; text-align: left; vertical-align: top;"><td class="center" align="center" style="-moz-hyphens: auto; -webkit-hyphens: auto; border-collapse: collapse !important; hyphens: auto; word-break: break-word; padding: 0; text-align: center; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; line-height: 19px; margin: 0; font-size: 14px;">
                <center style="min-width: 580px; width: 100%;">
                  <table class="container" style="border-collapse: collapse; border-spacing: 0; padding: 0; text-align: inherit; vertical-align: top; margin: 0 auto; width: 580px;"><tr style="padding: 0; text-align: left; vertical-align: top;"><td class="wrapper last bluebg" style="-moz-hyphens: auto; -webkit-hyphens: auto; border-collapse: collapse !important; hyphens: auto; word-break: break-word; padding: 10px 20px 0px 0px; text-align: left; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; line-height: 19px; margin: 0; font-size: 14px; background: #39ACE5; position: relative; padding-right: 0px;">
                        <table class="three columns" style="border-collapse: collapse; border-spacing: 0; padding: 0; text-align: left; vertical-align: top; margin: 0 auto; width: 130px;"><tr style="padding: 0; text-align: left; vertical-align: top;"><td style="-moz-hyphens: auto; -webkit-hyphens: auto; border-collapse: collapse !important; hyphens: auto; word-break: break-word; padding: 0px 0px 10px; text-align: left; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; line-height: 19px; margin: 0; font-size: 14px;">
                              <table class="tiny-button" style="border-collapse: collapse; border-spacing: 0; padding: 0; text-align: left; vertical-align: top; overflow: hidden; width: 100%;"><tr style="padding: 0; text-align: left; vertical-align: top;"><td style="-moz-hyphens: auto; -webkit-hyphens: auto; border-collapse: collapse !important; hyphens: auto; word-break: break-word; padding: 5px 0 4px; text-align: center; vertical-align: top; color: #f2f2f2; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; line-height: 19px; margin: 0; font-size: 14px; background: #2F87C7; border: 1px solid #2284a1; display: block; width: auto !important; border-bottom: 2px solid #2C7CAF; margin-bottom: 1em;">
                                    <a href="{{{$merchant['org']['hostname']}}}" style="color: #f2f2f2 !important; text-decoration: none; font-family: 'Lucida Sans', Helvetica, Arial, sans-serif !important; font-size: 12px; font-weight: normal;">Go to Dashboard</a>
                                  </td>
                                </tr></table></td>
                          </tr></table></td>
                    </tr></table></center>
              </td>
            </tr></table><!-- Main text --><table class="row" style="border-collapse: collapse; border-spacing: 0; padding: 0px; text-align: left; vertical-align: top; position: relative; width: 100%; display: block;"><tr style="padding: 0; text-align: left; vertical-align: top;"><td class="center" align="center" style="-moz-hyphens: auto; -webkit-hyphens: auto; border-collapse: collapse !important; hyphens: auto; word-break: break-word; padding: 0; text-align: center; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; line-height: 19px; margin: 0; font-size: 14px;">
                <center style="min-width: 580px; width: 100%;">
                  <table class="container" style="border-collapse: collapse; border-spacing: 0; padding: 0; text-align: inherit; vertical-align: top; margin: 0 auto; width: 580px;"><tr style="padding: 0; text-align: left; vertical-align: top;"><td class="wrapper last white" style="-moz-hyphens: auto; -webkit-hyphens: auto; border-collapse: collapse !important; hyphens: auto; word-break: break-word; padding: 10px 20px 0px 0px; text-align: left; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; line-height: 19px; margin: 0; font-size: 14px; background: #ffffff; background-color: #ffffff; position: relative; padding-right: 0px;">
                        <table class="twelve columns" style="border-collapse: collapse; border-spacing: 0; padding: 0; text-align: left; vertical-align: top; margin: 0 auto; width: 580px;"><tr style="padding: 0; text-align: left; vertical-align: top;"><td class="darktext left-text-pad right-text-pad" style="-moz-hyphens: auto; -webkit-hyphens: auto; border-collapse: collapse !important; hyphens: auto; word-break: break-word; padding: 0px 0px 10px; text-align: right; vertical-align: top; color: #484B4C; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; line-height: 25px; margin: 0; font-size: 14px; padding-left: 10px; padding-right: 10px;">
<p style="margin: 0; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; line-height: 25px; padding: 0; text-align: left; font-size: 14px; margin-bottom: 10px;">Hi {{{$merchant['name']}}},</p>

<p style="margin: 0; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; line-height: 25px; padding: 0; text-align: left; font-size: 14px; margin-bottom: 10px;">Your {{{$merchant['org']['business_name']}}} account for

<a title="Merchant Website" href="{{$merchant['website']}}" style="color: #2ba6cb; text-decoration: none;">{{{$merchant['billing_label']}}}</a>

is now active. The pricing details associated with your account are:

</p><ul align="left">
  @if(count($rules['amountRangeRules']) === 2)
    <li style="text-align:left;" align="left">{{$rules['amountRangeRules']['low']}}</li>
    <li style="text-align:left;" align="left">{{$rules['amountRangeRules']['high']}}</li>
  @endif
  @foreach ($rules['otherRules'] as $pricing => $methodDisplay)
    <li style="text-align:left;" align="left">{{implode(', ', $methodDisplay)}} - {{$pricing}}</li>
  @endforeach
</ul><p style="margin: 0; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; line-height: 25px; padding: 0; text-align: left; font-size: 14px; margin-bottom: 10px;"><i> GST Extra (18%)</i></p>
<p style="margin: 0; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; line-height: 25px; padding: 0; text-align: left; font-size: 14px; margin-bottom: 10px;">In case you haven't integrated our API in your application, the instructions can be found <a href="https://docs.razorpay.com" title="Razorpay Integration Documentation" style="color: #2ba6cb; text-decoration: none;">here</a>. Please ensure that your production website/app is using the live keys generated from the dashboard.</p>

<p style="margin: 0; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; line-height: 25px; padding: 0; text-align: left; font-size: 14px; margin-bottom: 10px;">If you face any issues while implementing this, feel free to drop us an <a href="mailto:support@razorpay.com" style="color: #2ba6cb; text-decoration: none;">email</a>.</p>

<p style="margin: 0; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; line-height: 25px; padding: 0; text-align: left; font-size: 14px; margin-bottom: 10px;">We hope that the association between you and {{{$merchant['org']['business_name']}}} will be fruitful for both organizations.</p>

<p style="margin: 0; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; line-height: 25px; padding: 0; text-align: left; font-size: 14px; margin-bottom: 10px;">
Regards,<br>
Team {{{$merchant['org']['business_name']}}}
</p>
                            </td>
                            <td class="expander" style="-moz-hyphens: auto; -webkit-hyphens: auto; border-collapse: collapse !important; hyphens: auto; word-break: break-word; padding: 0 !important; text-align: left; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; line-height: 19px; margin: 0; font-size: 14px; visibility: hidden; width: 0px;"></td>
                          </tr></table></td>
                    </tr></table></center>
              </td>
            </tr></table>
          @if ($merchant['org']['custom_code'] === 'rzp')
            @include('emails.partials.footer', ['message'=>$message])
          @endif
        </td>
      </tr></table></body></html>
