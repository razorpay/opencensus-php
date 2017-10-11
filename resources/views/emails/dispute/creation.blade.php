<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<meta name="viewport" content="width=device-width">
</head>
<body style="width: 100% !important; min-width: 100%; -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; padding: 0; margin: 0; text-align: left; font-size: 14px; line-height: 19px;">

    @php
        $createdDate = date('d-m-Y', $dispute['created_at']);
        $expiryDate = date('d-m-Y', $dispute['expires_on']);
    @endphp

    <link rel="stylesheet" type="text/css" href="ink.css">
<link rel="stylesheet" type="text/css" href="welcome.css">
<table class="row" style="border-spacing: 0; border-collapse: collapse; vertical-align: top; text-align: left; padding: 0px; width: 100%; position: relative;"><tr style="padding: 0; vertical-align: top; text-align: left;">
<td class="wrapper" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; text-align: left; font-size: 14px; line-height: 19px; padding: 10px 20px 0px 0px; position: relative;">

              <table class="four columns" style="border-spacing: 0; border-collapse: collapse; padding: 0; vertical-align: top; text-align: left; margin: 0 auto; width: 180px;"><tr style="padding: 0; vertical-align: top; text-align: left;">
<td class="center" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; font-size: 14px; line-height: 19px; text-align: center; padding: 0px 0px 10px;">
                    <img class="center" src="https://cdn.razorpay.com/logo.svg" style="outline: none; text-decoration: none; -ms-interpolation-mode: bicubic; width: auto; max-width: 100%; clear: both; display: block; margin: 0 auto; float: none;">
</td>
                  <td class="expander" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; text-align: left; font-size: 14px; line-height: 19px; visibility: hidden; width: 0px; padding: 0 !important;"></td>
                </tr></table>
</td>
          </tr></table>
<table class="container" style="border-spacing: 0; border-collapse: collapse; padding: 0; vertical-align: top; width: 580px; margin: 0 auto; text-align: inherit;"><tr style="padding: 0; vertical-align: top; text-align: left;">
<td style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; padding: 0; margin: 0; text-align: left; font-size: 14px; line-height: 19px;">
              <table class="row" style="border-spacing: 0; border-collapse: collapse; vertical-align: top; text-align: left; padding: 0px; width: 100%; position: relative; display: block;"><tr style="padding: 0; vertical-align: top; text-align: left;">
<td class="wrapper offset-by-one" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; text-align: left; font-size: 14px; line-height: 19px; padding: 10px 20px 0px 0px; position: relative; padding-left: 50px;">
                        <!-- Mail content here -->
                        <div>

                          Hi {{ $merchant['name'] }}, <br>
                          We have received a chargeback against the below mentioned payment. <br><br><table border="1" style="border-spacing: 0; border-collapse: collapse; padding: 0; vertical-align: top; text-align: left; font-size: 10px;">
<tr style="padding: 0; vertical-align: top; text-align: left;">
<td style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; padding: 0; margin: 0; text-align: left; font-size: 14px; line-height: 19px;">Payment ID</td>
                                <td style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; padding: 0; margin: 0; text-align: left; font-size: 14px; line-height: 19px;">Date of Chargeback</td>
                                <td style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; padding: 0; margin: 0; text-align: left; font-size: 14px; line-height: 19px;">Amount</td>
                                <td style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; padding: 0; margin: 0; text-align: left; font-size: 14px; line-height: 19px;">Chargeback Reference Number</td>
                                <td style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; padding: 0; margin: 0; text-align: left; font-size: 14px; line-height: 19px;">Deadline of Resolution</td>
                            </tr>
<tr style="padding: 0; vertical-align: top; text-align: left;">
<td style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; padding: 0; margin: 0; text-align: left; font-size: 14px; line-height: 19px;">{{ $dispute['payment_id'] }}</td>
                                <td style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; padding: 0; margin: 0; text-align: left; font-size: 14px; line-height: 19px;">{{ $createdDate }}</td>
                                <td style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; padding: 0; margin: 0; text-align: left; font-size: 14px; line-height: 19px;">{{ $dispute['amount'] }}</td>
                                <td style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; padding: 0; margin: 0; text-align: left; font-size: 14px; line-height: 19px;">{{ $dispute['id'] }}</td>
                                <td style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; padding: 0; margin: 0; text-align: left; font-size: 14px; line-height: 19px;">{{ $expiryDate }}</td>
                            </tr>
</table>
<br><strong> Chargeback Reason Code:  {{ $dispute['reason_code'] }}. Reason :  {{ $dispute['reason_description'] }} </strong>

                          <br><br>
                          Please share all proofs (invoices, proof of delivery of product/service, any relevant screenshots) pertaining to each transaction in a consolidated Zip archive named as the payment ID.

                          <br><br>
                          Note: Disputed transactions should not be refunded. Kindly provide the requested details in the required format on or before due date, failing which we would lose the case and the corresponding amount would be debited from the current balance.

                          <br><br>
                          Want to know more about Chargebacks? Please refer to our
                          <a href="https://razorpay.com/chargeback/" style="color: #2ba6cb; text-decoration: none;">Chargeback Guide.</a>

                        </div>

                        <br>
                        Team Razorpay
                  </td>
                </tr></table>
</td>
          </tr></table>
<table class="row footer" style="border-spacing: 0; border-collapse: collapse; vertical-align: top; text-align: left; padding: 0px; width: 100%; position: relative;"><tr style="padding: 0; vertical-align: top; text-align: left;">
<td class="wrapper last" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; text-align: left; padding: 10px 20px 0px 0px; position: relative; color: #aaa; font-size: 12px; line-height: 18px; padding-right: 0px;">

          <table class="twelve columns" style="border-spacing: 0; border-collapse: collapse; padding: 0; vertical-align: top; text-align: left; margin: 0 auto; width: 580px;"><tr style="padding: 0; vertical-align: top; text-align: left;">
<td class="center" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; text-align: center; color: #aaa; font-size: 12px; line-height: 18px; padding: 0px 0px 10px;">
                <center style="width: 100%; min-width: 580px;">
                  <hr style="background-color: #d9d9d9; border: none; height: 2px; color: #E5E5E5;">
<a href="https://razorpay.com/terms/" style="display: inline-block; color: #aaa !important; text-decoration: none;">Terms &amp; Conditions</a>
                  |
                  <a href="https://razorpay.com/privacy/" style="display: inline-block; color: #aaa !important; text-decoration: none;">Privacy Policy</a>
                  |
                  <a href="https://razorpay.com/refund/" style="display: inline-block; color: #aaa !important; text-decoration: none;">Refund Policy</a>
                </center>
              </td>
              <td class="expander" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; text-align: left; visibility: hidden; width: 0px; padding: 0 !important; color: #aaa; font-size: 12px; line-height: 18px;"></td>
            </tr></table>
</td>
      </tr></table>
<table class="row footer" style="border-spacing: 0; border-collapse: collapse; vertical-align: top; text-align: left; padding: 0px; width: 100%; position: relative;"><tr style="padding: 0; vertical-align: top; text-align: left;">
<td class="wrapper last" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; text-align: left; padding: 10px 20px 0px 0px; position: relative; color: #aaa; font-size: 12px; line-height: 18px; padding-right: 0px;">

          <table class="three columns offset-by-six" style="border-spacing: 0; border-collapse: collapse; padding: 0; vertical-align: top; text-align: left; margin: 0 auto; width: 130px;"><tr style="padding: 0; vertical-align: top; text-align: left;">
<td class="center" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; text-align: center; color: #aaa; font-size: 12px; line-height: 18px; padding: 0px 0px 10px;">
                <center style="width: 100%; min-width: 130px;">
                  <a href="https://facebook.com/razorpay" class="logo" style="height: 22px; width: 22px; float: left !important; padding: 0px 5px 0px 5px; display: inline-block; color: #aaa !important; text-decoration: none;">
                    <img height="22" width="22" src="https://s3.amazonaws.com/checkout-live/facebook.png" alt="Facebook Icon" title="Razorpay on Facebook" style="outline: none; text-decoration: none; -ms-interpolation-mode: bicubic; width: auto; max-width: 100%; clear: both; display: block; border: none; float: none;"></a>
                  <a href="https://twitter.com/razorpay" class="logo" style="height: 22px; width: 22px; float: left !important; padding: 0px 5px 0px 5px; display: inline-block; color: #aaa !important; text-decoration: none;">
                    <img height="22" width="22" src="https://s3.amazonaws.com/checkout-live/twitter.png" alt="Twitter Icon" title="Razorpay on Twitter" style="outline: none; text-decoration: none; -ms-interpolation-mode: bicubic; width: auto; max-width: 100%; clear: both; display: block; border: none; float: none;"></a>
                  <a href="https://github.com/razorpay" class="logo" style="height: 22px; width: 22px; float: left !important; padding: 0px 5px 0px 5px; display: inline-block; color: #aaa !important; text-decoration: none;">
                    <img height="22" width="22" src="https://s3.amazonaws.com/checkout-live/github.png" alt="GitHub Icon" title="Razorpay on GitHub" style="outline: none; text-decoration: none; -ms-interpolation-mode: bicubic; width: auto; max-width: 100%; clear: both; display: block; border: none; float: none;"></a>
                </center>
              </td>
              <td class="expander" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; text-align: left; visibility: hidden; width: 0px; padding: 0 !important; color: #aaa; font-size: 12px; line-height: 18px;"></td>
            </tr></table>
</td>
      </tr></table>
<table class="row footer" style="border-spacing: 0; border-collapse: collapse; vertical-align: top; text-align: left; padding: 0px; width: 100%; position: relative;"><tr style="padding: 0; vertical-align: top; text-align: left;">
<td class="wrapper last" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; text-align: left; padding: 10px 20px 0px 0px; position: relative; color: #aaa; font-size: 12px; line-height: 18px; padding-right: 0px;">

          <table class="seven columns" style="border-spacing: 0; border-collapse: collapse; padding: 0; vertical-align: top; text-align: left; margin: 0 auto; width: 330px;"><tr style="padding: 0; vertical-align: top; text-align: left;">
<td class="center" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; text-align: center; color: #aaa; font-size: 12px; line-height: 18px; padding: 0px 0px 10px;">
                <center style="width: 100%; min-width: 330px;">
                  Reach out to us by replying
                  to this email or at <a href="mailto:support@razorpay.com" style="display: inline-block; color: #aaa !important; text-decoration: none;">
                  support@razorpay.com</a>
                </center>
              </td>
              <td class="expander" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; text-align: left; visibility: hidden; width: 0px; padding: 0 !important; color: #aaa; font-size: 12px; line-height: 18px;"></td>
            </tr></table>
</td>
      </tr></table>
</body>
</html>