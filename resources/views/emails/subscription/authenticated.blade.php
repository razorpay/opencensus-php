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
          <!-- Payment Successfull Header -->
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
                                Subscription Initialized
                              </center>
                              </h1>
                            </td>
                            <td class="expander" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; text-align: left; font-size: 14px; line-height: 19px; visibility: hidden; width: 0px; padding: 0 !important;"></td>
                          </tr></table>
</center>
                      </td>
                    </tr></table>
</center>
              </td>
            </tr></table>
          @include('emails.partials.header_image', ['image'=>'payment_green'], ['message'=>$message])
          <!-- Merchant Name -->
          <table class="row" style="border-spacing: 0; border-collapse: collapse; vertical-align: top; text-align: left; padding: 0px; width: 100%; position: relative; display: block;"><tr style="padding: 0; vertical-align: top; text-align: left;">
<td class="center" align="center" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; padding: 0; margin: 0; font-size: 14px; line-height: 19px; text-align: center;">
                <center style="width: 100%; min-width: 580px;">
                  <table class="container" style="border-spacing: 0; border-collapse: collapse; padding: 0; vertical-align: top; width: 580px; margin: 0 auto; text-align: inherit;"><tr style="padding: 0; vertical-align: top; text-align: left;">
<td class="wrapper last bluebg" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; text-align: left; font-size: 14px; line-height: 19px; padding: 10px 20px 0px 0px; position: relative; padding-right: 0px; background: #39ACE5;">
                        <table class="six columns bluebg" style="border-spacing: 0; border-collapse: collapse; padding: 0; vertical-align: top; text-align: left; margin: 0 auto; width: 280px; background: #39ACE5;"><tr style="padding: 0; vertical-align: top; text-align: left;">
<td class="center" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; font-size: 14px; line-height: 19px; text-align: center; padding: 0px 0px 10px;">
                              <center style="width: 100%; min-width: 280px;">
                                <h3 class="center" style="font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; padding: 0; margin: 0; line-height: 1.3; word-break: normal; font-size: 16px; font-weight: bold; color: #39ACE5; margin-top: 10px; text-align: center;">
                                <a href="{{$merchant['website']}}" title="{{$merchant['billing_label']}} Website" style="text-decoration: none; font-size: 22px; color: #f2f2f2;">{{$merchant['billing_label']}}</a>
                                </h3>
                              </center>
                            </td>
                            <td class="expander" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; text-align: left; font-size: 14px; line-height: 19px; visibility: hidden; width: 0px; padding: 0 !important;"></td>
                          </tr></table>
</td>
                    </tr></table>
</center>
              </td>
            </tr></table>
<!-- Customer Information --><table class="row" style="border-spacing: 0; border-collapse: collapse; vertical-align: top; text-align: left; padding: 0px; width: 100%; position: relative; display: block;"><tr style="padding: 0; vertical-align: top; text-align: left;">
<td class="wrapper white" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; text-align: left; font-size: 14px; line-height: 19px; background: #ffffff; background-color: #ffffff; padding: 10px 20px 0px 0px; position: relative;">
                <table class="eight columns" style="border-spacing: 0; border-collapse: collapse; padding: 0; vertical-align: top; text-align: left; margin: 0 auto; width: 380px;"><tr style="padding: 0; vertical-align: top; text-align: left;">
<td class="lighttext left-text-pad" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; text-align: left; line-height: 19px; color: #B2B2B2; font-size: 12px; text-decoration: none; padding: 0px 0px 10px; padding-left: 10px;">
                      <a class="email" style="text-decoration: none; color: inherit;">{{$customer['email']}}</a>
                    </td>
                    <td class="expander" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; text-align: left; font-size: 14px; line-height: 19px; visibility: hidden; width: 0px; padding: 0 !important;"></td>
                  </tr></table>
</td>
              <td class="wrapper white last" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; text-align: left; font-size: 14px; line-height: 19px; background: #ffffff; background-color: #ffffff; padding: 10px 20px 0px 0px; position: relative; padding-right: 0px;">
                <table class="four columns" style="border-spacing: 0; border-collapse: collapse; padding: 0; vertical-align: top; text-align: left; margin: 0 auto; width: 180px;"><tr style="padding: 0; vertical-align: top; text-align: left;">
<td class="right-text-pad lighttext" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; line-height: 19px; color: #B2B2B2; font-size: 12px; text-decoration: none; padding: 0px 0px 10px; padding-right: 10px; text-align: right;">
                      {{$customer['phone']}}
                    </td>
                    <td class="expander" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; text-align: left; font-size: 14px; line-height: 19px; visibility: hidden; width: 0px; padding: 0 !important;"></td>
                  </tr></table>
</td>
            </tr></table>
<!-- Payment Id --><table class="row" style="border-spacing: 0; border-collapse: collapse; vertical-align: top; text-align: left; padding: 0px; width: 100%; position: relative; display: block;"><tr style="padding: 0; vertical-align: top; text-align: left;">
<td class="center" align="center" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; padding: 0; margin: 0; font-size: 14px; line-height: 19px; text-align: center;">
                <center style="width: 100%; min-width: 580px;">
                  <table class="container" style="border-spacing: 0; border-collapse: collapse; padding: 0; vertical-align: top; width: 580px; margin: 0 auto; text-align: inherit;"><tr style="padding: 0; vertical-align: top; text-align: left;">
<td class="wrapper last white" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; text-align: left; font-size: 14px; line-height: 19px; background: #ffffff; background-color: #ffffff; padding: 10px 20px 0px 0px; position: relative; padding-right: 0px;">
                        <table class="six columns" style="border-spacing: 0; border-collapse: collapse; padding: 0; vertical-align: top; text-align: left; margin: 0 auto; width: 280px;"><tr style="padding: 0; vertical-align: top; text-align: left;">
                                You have been successfully subscribed and a payment of {{$payment['amount']}} has been made. Your card details have been successfully stored for future payments.
                            <td class="expander" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; text-align: left; font-size: 14px; line-height: 19px; visibility: hidden; width: 0px; padding: 0 !important;"></td>
                          </tr></table>
</td>
                    </tr></table>
</center>
              </td>
            </tr></table>
<!-- Payment Details --><table class="row" style="border-spacing: 0; border-collapse: collapse; vertical-align: top; text-align: left; padding: 0px; width: 100%; position: relative; display: block;"><tr style="padding: 0; vertical-align: top; text-align: left;">
<td class="wrapper white" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; text-align: left; font-size: 14px; line-height: 19px; background: #ffffff; background-color: #ffffff; padding: 10px 20px 0px 0px; position: relative;">
                <table class="four columns" style="border-spacing: 0; border-collapse: collapse; padding: 0; vertical-align: top; text-align: left; margin: 0 auto; width: 180px;"><tr style="padding: 0; vertical-align: top; text-align: left;">
<td class="darktext left-text-pad" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; text-align: left; font-size: 14px; line-height: 25px; color: #484B4C; padding: 0px 0px 10px; padding-left: 10px;">
                      Amount
                    </td>
                    <td class="expander" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; text-align: left; font-size: 14px; line-height: 19px; visibility: hidden; width: 0px; padding: 0 !important;"></td>
                  </tr></table>
</td>
              <td class="wrapper white offset-by-five last" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; text-align: left; font-size: 14px; line-height: 19px; background: #ffffff; background-color: #ffffff; padding: 10px 20px 0px 0px; position: relative; padding-left: 250px; padding-right: 0px;">
                <table class="three columns" style="border-spacing: 0; border-collapse: collapse; padding: 0; vertical-align: top; text-align: left; margin: 0 auto; width: 130px;"><tr style="padding: 0; vertical-align: top; text-align: left;">
<td class="right-text-pad darktext" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; font-size: 14px; line-height: 25px; color: #484B4C; padding: 0px 0px 10px; padding-right: 10px; text-align: right;">
                    {{$payment['amount'] ?? 4}}
                    </td>
                    <td class="expander" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; text-align: left; font-size: 14px; line-height: 19px; visibility: hidden; width: 0px; padding: 0 !important;"></td>
                  </tr></table>
</td>
            </tr></table>
          @if ($payment)
          <!-- Payment Method -->
          <table class="row" style="border-spacing: 0; border-collapse: collapse; vertical-align: top; text-align: left; padding: 0px; width: 100%; position: relative; display: block;"><tr style="padding: 0; vertical-align: top; text-align: left;">
<td class="wrapper white" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; text-align: left; font-size: 14px; line-height: 19px; background: #ffffff; background-color: #ffffff; padding: 10px 20px 0px 0px; position: relative;">
                <table class="four columns" style="border-spacing: 0; border-collapse: collapse; padding: 0; vertical-align: top; text-align: left; margin: 0 auto; width: 180px;"><tr style="padding: 0; vertical-align: top; text-align: left;">
<td class="darktext left-text-pad" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; text-align: left; font-size: 14px; line-height: 25px; color: #484B4C; padding: 0px 0px 10px; padding-left: 10px;">
                      Payment Method
                    </td>
                    <td class="expander" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; text-align: left; font-size: 14px; line-height: 19px; visibility: hidden; width: 0px; padding: 0 !important;"></td>
                  </tr></table>
</td>
              <td class="wrapper white last" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; text-align: left; font-size: 14px; line-height: 19px; background: #ffffff; background-color: #ffffff; padding: 10px 20px 0px 0px; position: relative; padding-right: 0px;">
                <table class="eight columns" style="border-spacing: 0; border-collapse: collapse; padding: 0; vertical-align: top; text-align: left; margin: 0 auto; width: 380px;"><tr style="padding: 0; vertical-align: top; text-align: left;">
<td class="right-text-pad darktext" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; font-size: 14px; line-height: 25px; color: #484B4C; padding: 0px 0px 10px; padding-right: 10px; text-align: right;">
                      {{$payment['method'][0]}}<br><span class="subtext" style="color: #7C8082; line-height: 10px; font-size: 16px;">
                      {{$payment['method'][1]}}
                      </span>
                    </td>
                    <td class="expander" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; text-align: left; font-size: 14px; line-height: 19px; visibility: hidden; width: 0px; padding: 0 !important;"></td>
                  </tr></table>
</td>
            </tr></table>
          @endif ($payment)
          @if ($options['immediate'])
          <!-- Billing Period -->
          <table class="row" style="border-spacing: 0; border-collapse: collapse; vertical-align: top; text-align: left; padding: 0px; width: 100%; position: relative; display: block;"><tr style="padding: 0; vertical-align: top; text-align: left;">
<td class="wrapper white" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; text-align: left; font-size: 14px; line-height: 19px; background: #ffffff; background-color: #ffffff; padding: 10px 20px 0px 0px; position: relative;">
                <table class="four columns" style="border-spacing: 0; border-collapse: collapse; padding: 0; vertical-align: top; text-align: left; margin: 0 auto; width: 180px;"><tr style="padding: 0; vertical-align: top; text-align: left;">
<td class="darktext left-text-pad" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; text-align: left; font-size: 14px; line-height: 25px; color: #484B4C; padding: 0px 0px 10px; padding-left: 10px;">
                      Billing Period
                    </td>
                    <td class="expander" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; text-align: left; font-size: 14px; line-height: 19px; visibility: hidden; width: 0px; padding: 0 !important;"></td>
                  </tr></table>
</td>
              <td class="wrapper white last" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; text-align: left; font-size: 14px; line-height: 19px; background: #ffffff; background-color: #ffffff; padding: 10px 20px 0px 0px; position: relative; padding-right: 0px;">
                <table class="eight columns" style="border-spacing: 0; border-collapse: collapse; padding: 0; vertical-align: top; text-align: left; margin: 0 auto; width: 380px;"><tr style="padding: 0; vertical-align: top; text-align: left;">
<td class="right-text-pad darktext" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; font-size: 14px; line-height: 25px; color: #484B4C; padding: 0px 0px 10px; padding-right: 10px; text-align: right;">
                      {{$invoice['billing_start']}}<br>
                      {{$invoice['billing_end']}}
                    </td>
                    <td class="expander" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; text-align: left; font-size: 14px; line-height: 19px; visibility: hidden; width: 0px; padding: 0 !important;"></td>
                  </tr></table>
</td>
            </tr></table>
          @endif
          <!-- Next charge date -->
          <table class="row" style="border-spacing: 0; border-collapse: collapse; vertical-align: top; text-align: left; padding: 0px; width: 100%; position: relative; display: block;"><tr style="padding: 0; vertical-align: top; text-align: left;">
<td class="wrapper white" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; text-align: left; font-size: 14px; line-height: 19px; background: #ffffff; background-color: #ffffff; padding: 10px 20px 0px 0px; position: relative;">
                <table class="four columns" style="border-spacing: 0; border-collapse: collapse; padding: 0; vertical-align: top; text-align: left; margin: 0 auto; width: 180px;"><tr style="padding: 0; vertical-align: top; text-align: left;">
<td class="darktext left-text-pad" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; text-align: left; font-size: 14px; line-height: 25px; color: #484B4C; padding: 0px 0px 10px; padding-left: 10px;">
                      Next Charge Date
                    </td>
                    <td class="expander" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; text-align: left; font-size: 14px; line-height: 19px; visibility: hidden; width: 0px; padding: 0 !important;"></td>
                  </tr></table>
</td>
              <td class="wrapper white offset-by-five last" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; text-align: left; font-size: 14px; line-height: 19px; background: #ffffff; background-color: #ffffff; padding: 10px 20px 0px 0px; position: relative; padding-left: 250px; padding-right: 0px;">
                <table class="three columns" style="border-spacing: 0; border-collapse: collapse; padding: 0; vertical-align: top; text-align: left; margin: 0 auto; width: 130px;"><tr style="padding: 0; vertical-align: top; text-align: left;">
<td class="right-text-pad darktext" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; font-size: 14px; line-height: 25px; color: #484B4C; padding: 0px 0px 10px; padding-right: 10px; text-align: right;">
                    {{$subscription['charge_at']}}
                    </td>
                    <td class="expander" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; text-align: left; font-size: 14px; line-height: 19px; visibility: hidden; width: 0px; padding: 0 !important;"></td>
                  </tr></table>
</td>
            </tr></table>
<!-- Contact Us --><table class="row" style="border-spacing: 0; border-collapse: collapse; vertical-align: top; text-align: left; padding: 0px; width: 100%; position: relative; display: block;"><tr style="padding: 0; vertical-align: top; text-align: left;">
<td class="center" align="center" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; padding: 0; margin: 0; font-size: 14px; line-height: 19px; text-align: center;">
                <center style="width: 100%; min-width: 580px;">
                  <table class="container" style="border-spacing: 0; border-collapse: collapse; padding: 0; vertical-align: top; width: 580px; margin: 0 auto; text-align: inherit;"><tr style="padding: 0; vertical-align: top; text-align: left;">
<td class="wrapper last white" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; text-align: left; font-size: 14px; line-height: 19px; background: #ffffff; background-color: #ffffff; padding: 10px 20px 0px 0px; position: relative; padding-right: 0px;">
                        <table class="twelve columns" style="border-spacing: 0; border-collapse: collapse; padding: 0; vertical-align: top; text-align: left; margin: 0 auto; width: 580px;"><tr style="padding: 0; vertical-align: top; text-align: left;">
<td class="center lighttext" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; line-height: 19px; color: #B2B2B2; font-size: 12px; text-decoration: none; text-align: center; padding: 0px 0px 10px;">
                              <hr class="wide" style="background-color: #d9d9d9; border: none; height: 2px; color: #E5E5E5; width: 490px;">
<center style="width: 100%; min-width: 580px;">
                                If this is correct, you don't need to take any further action.<br>
                                Please <a title="Click to send us a mail" href="mailto:contact@razorpay.com" style="color: #2ba6cb; text-decoration: none;">contact us</a> in case of any discrepancy.
                              </center>
                            </td>
                            <td class="expander" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; text-align: left; font-size: 14px; line-height: 19px; visibility: hidden; width: 0px; padding: 0 !important;"></td>
                          </tr></table>
</td>
                    </tr></table>
</center>
              </td>
            </tr></table>
          @include('emails.partials.footer', ['message'=>$message])
        </td>
      </tr></table>
</body>
</html>