<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="viewport" content="width=device-width">
  </head>
  <body style="width: 100% !important; min-width: 100%; -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; padding: 0; margin: 0; text-align: left; font-size: 14px; line-height: 19px;">
    <link rel="stylesheet" type="text/css" href="ink.css">
    <link rel="stylesheet" type="text/css" href="welcome.css">
    <table class="row" style="border-spacing: 0; border-collapse: collapse; vertical-align: top; text-align: left; padding: 0px; width: 100%; position: relative;">
      <tr style="padding: 0; vertical-align: top; text-align: left;">
        <td class="wrapper" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; text-align: left; font-size: 14px; line-height: 19px; padding: 10px 20px 0px 0px; position: relative;">

          <table class="four columns" style="border-spacing: 0; border-collapse: collapse; padding: 0; vertical-align: top; text-align: left; margin: 0 auto; width: 180px;">
            <tr style="padding: 0; vertical-align: top; text-align: left;">
              <td class="center" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; font-size: 14px; line-height: 19px; text-align: center; padding: 0px 0px 10px;">
                @if ((isset($data['custom_branding']) === true) && ($data['custom_branding'] === false))
                  <img class="center" src="https://cdn.razorpay.com/logo.svg" style="outline: none; text-decoration: none; -ms-interpolation-mode: bicubic; width: auto; max-width: 100%; clear: both; display: block; margin: 0 auto; float: none;">
                @endif
              </td>
              <td class="expander" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; text-align: left; font-size: 14px; line-height: 19px; visibility: hidden; width: 0px; padding: 0 !important;"></td>
            </tr>
          </table>
        </td>
      </tr>
    </table>

    <!-- Heading -->
    <table class="row" style="border-spacing: 0; border-collapse: collapse; vertical-align: top; text-align: left; padding: 0px; width: 100%; position: relative;">
      <tr style="padding: 0; vertical-align: top; text-align: left;">
        <td class="wrapper last" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; text-align: left; font-size: 14px; line-height: 19px; padding: 10px 20px 0px 0px; position: relative; padding-right: 0px;">

        <table class="twelve columns" style="border-spacing: 0; border-collapse: collapse; padding: 0; vertical-align: top; text-align: left; margin: 0 auto; width: 580px;">
          <tr style="padding: 0; vertical-align: top; text-align: left;">
            <td class="center panel" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; font-size: 14px; line-height: 19px; background: #f2f2f2; border: 1px solid #d9d9d9; padding: 10px !important; text-align: center; border-left: none; border-right: none;">
              <h6 style="color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; padding: 0; margin: 0; text-align: left; line-height: 1.3; word-break: normal; font-size: 20px;">
                <center style="width: 100%; min-width: 560px;">
                  {{{$subMerchant['name']}}} • ID: {{{$subMerchant['id']}}}
                </center>
              </h6>
            </td>
          </tr>
        </table>

          <table class="twelve columns" style="border-spacing: 0; border-collapse: collapse; padding: 0; vertical-align: top; text-align: left; margin: 0 auto; width: 580px;">
            <tr style="padding: 0; vertical-align: top; text-align: left;">
              <td class="center" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; font-size: 14px; line-height: 19px; text-align: center; padding: 0px 0px 10px;">
                <br>
                <h1 style="font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; padding: 0; margin: 0; line-height: 1.3; word-break: normal; margin-top: 40px; color: #f2f2f2; font-size: 32px; text-align: center; font-weight: bold;"><center style="width: 100%; min-width: 580px;">
                  <span style="color:#222">Greetings!</span>
                </center></h1>
              </td>
              <td class="expander" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; text-align: left; font-size: 14px; line-height: 19px; visibility: hidden; width: 0px; padding: 0 !important;"></td>
            </tr>
          </table>

        </td>
      </tr>
    </table>

    <!-- Contact Us -->
    <table class="container" style="border-spacing: 0; border-collapse: collapse; padding: 0; vertical-align: top; width: 580px; margin: 0 auto; text-align: inherit;">
      <tr style="padding: 0; vertical-align: top; text-align: left;">
        <td style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; padding: 0; margin: 0; text-align: left; font-size: 14px; line-height: 19px;">
          <table class="row" style="border-spacing: 0; border-collapse: collapse; vertical-align: top; text-align: left; padding: 0px; width: 100%; position: relative; display: block;">
            <tr style="padding: 0; vertical-align: top; text-align: left;">
              <td class="wrapper offset-by-one" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; text-align: left; font-size: 14px; line-height: 19px; padding: 10px 20px 0px 0px; position: relative; padding-left: 50px;">
                <table class="ten columns" style="border-spacing: 0; border-collapse: collapse; padding: 0; vertical-align: top; text-align: left; margin: 0 auto; width: 480px;">
                  <tr style="padding: 0; vertical-align: top; text-align: left;">
                    <td class="center welcome" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; font-size: 16px; color: #2d2d2d; line-height: 24px; background: white; margin: 10px 0px 10px 0px; text-align: center; padding: 0px 0px 10px;">

<p style="font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; padding: 0; margin-bottom: 10px; font-size: 16px; color: #2d2d2d; text-align: justify; line-height: 24px; background: white; margin: 10px 0px 10px 0px;">
  You have been added to {{{$subMerchant['name']}}}'s {{{$org['business_name']}}} account with id: {{{$subMerchant['id']}}}.
  <br>
  You can switch to the account from the Switch Merchant dropdown in the top panel on your dashboard.
</p>

<p style="font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; padding: 0; margin-bottom: 10px; font-size: 16px; color: #2d2d2d; text-align: justify; line-height: 24px; background: white; margin: 10px 0px 10px 0px;">
  Cheers,
  <br>
  Team Razorpay
</p>
                    </td>
                    <td class="expander" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; text-align: left; font-size: 14px; line-height: 19px; visibility: hidden; width: 0px; padding: 0 !important;"></td>
                  </tr>
                </table>
              </td>
            </tr>
          </table>
        </td>
      </tr>
    </table>

    <table class="row footer" style="border-spacing: 0; border-collapse: collapse; vertical-align: top; text-align: left; padding: 0px; width: 100%; position: relative;">
      <tr style="padding: 0; vertical-align: top; text-align: left;">
        <td class="wrapper last" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; text-align: left; padding: 10px 20px 0px 0px; position: relative; color: #aaa; font-size: 12px; line-height: 18px; padding-right: 0px;">

          <table class="twelve columns" style="border-spacing: 0; border-collapse: collapse; padding: 0; vertical-align: top; text-align: left; margin: 0 auto; width: 580px;">
            <tr style="padding: 0; vertical-align: top; text-align: left;">
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
            </tr>
          </table>

        </td>
      </tr>
    </table>

    <table class="row footer" style="border-spacing: 0; border-collapse: collapse; vertical-align: top; text-align: left; padding: 0px; width: 100%; position: relative;">
      <tr style="padding: 0; vertical-align: top; text-align: left;">
        <td class="wrapper last" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; text-align: left; padding: 10px 20px 0px 0px; position: relative; color: #aaa; font-size: 12px; line-height: 18px; padding-right: 0px;">

          <table class="three columns offset-by-six" style="border-spacing: 0; border-collapse: collapse; padding: 0; vertical-align: top; text-align: left; margin: 0 auto; width: 130px;">
            <tr style="padding: 0; vertical-align: top; text-align: left;">
              <td class="center" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; text-align: center; color: #aaa; font-size: 12px; line-height: 18px; padding: 0px 0px 10px;">
                <center style="width: 100%; min-width: 130px;">
                  <a href="https://facebook.com/razorpay" class="logo" style="height: 22px; width: 22px; float: left !important; padding: 0px 5px 0px 5px; display: inline-block; color: #aaa !important; text-decoration: none;">
                    <img height="22" width="22" src="https://cdn.razorpay.com/facebook.png" alt="Facebook Icon" title="Razorpay on Facebook" style="outline: none; text-decoration: none; -ms-interpolation-mode: bicubic; width: auto; max-width: 100%; clear: both; display: block; border: none; float: none;">
                  </a>
                  <a href="https://twitter.com/razorpay" class="logo" style="height: 22px; width: 22px; float: left !important; padding: 0px 5px 0px 5px; display: inline-block; color: #aaa !important; text-decoration: none;">
                    <img height="22" width="22" src="https://cdn.razorpay.com/twitter.png" alt="Twitter Icon" title="Razorpay on Twitter" style="outline: none; text-decoration: none; -ms-interpolation-mode: bicubic; width: auto; max-width: 100%; clear: both; display: block; border: none; float: none;">
                  </a>
                  <a href="https://github.com/razorpay" class="logo" style="height: 22px; width: 22px; float: left !important; padding: 0px 5px 0px 5px; display: inline-block; color: #aaa !important; text-decoration: none;">
                    <img height="22" width="22" src="https://cdn.razorpay.com/github.png" alt="GitHub Icon" title="Razorpay on GitHub" style="outline: none; text-decoration: none; -ms-interpolation-mode: bicubic; width: auto; max-width: 100%; clear: both; display: block; border: none; float: none;">
                  </a>
                </center>
              </td>
              <td class="expander" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; text-align: left; visibility: hidden; width: 0px; padding: 0 !important; color: #aaa; font-size: 12px; line-height: 18px;"></td>
            </tr>
          </table>

        </td>
      </tr>
    </table>

    <table class="row footer" style="border-spacing: 0; border-collapse: collapse; vertical-align: top; text-align: left; padding: 0px; width: 100%; position: relative;">
      <tr style="padding: 0; vertical-align: top; text-align: left;">
        <td class="wrapper last" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; text-align: left; padding: 10px 20px 0px 0px; position: relative; color: #aaa; font-size: 12px; line-height: 18px; padding-right: 0px;">

          <table class="seven columns" style="border-spacing: 0; border-collapse: collapse; padding: 0; vertical-align: top; text-align: left; margin: 0 auto; width: 330px;">
            <tr style="padding: 0; vertical-align: top; text-align: left;">
              <td class="center" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; text-align: center; color: #aaa; font-size: 12px; line-height: 18px; padding: 0px 0px 10px;">
                <center style="width: 100%; min-width: 330px;">
                  This message was sent to <a href="mailto:{{$user['email']}}" style="display: inline-block; color: #aaa !important; text-decoration: none;">
                  {{{$user['email']}}}</a>. @include('emails.partials.support')
                </center>
              </td>
              <td class="expander" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; text-align: left; visibility: hidden; width: 0px; padding: 0 !important; color: #aaa; font-size: 12px; line-height: 18px;"></td>
            </tr>
          </table>

        </td>
      </tr>
    </table>

    @if ((isset($data['custom_branding']) === true) && ($data['custom_branding'] === true))
      <div class="max-width-override" style="Margin: 0px auto; max-width: unset;">
        <table align="center" border="0" cellpadding="0" cellspacing="0" role="presentation" style="width:100%;">
          <tbody>
            <tr>
              <td style="direction:ltr;font-size:0px;padding:0px;text-align:center;vertical-align:top;">
                <img src="{{ $data['email_logo'] }}" style="height: 32px;" />
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    @endif

  </body>
</html>
