<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml"><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"><meta name="viewport" content="width=device-width"></head><body class="body" style="-ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%; margin: 0; min-width: 100%; padding: 0; width: 100% !important; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; line-height: 19px; text-align: left; font-size: 14px; background: #EBECEE;">
    <table class="container" style="border-collapse: collapse; border-spacing: 0; padding: 0; text-align: inherit; vertical-align: top; margin: 0 auto; width: 580px; background: #EBECEE;"><tr style="padding: 0; text-align: left; vertical-align: top;"><td style="-moz-hyphens: auto; -webkit-hyphens: auto; border-collapse: collapse !important; hyphens: auto; word-break: break-word; padding: 0; text-align: left; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; line-height: 19px; margin: 0; font-size: 14px;">
          @include('emails.partials.header', ['message'=>$message, 'custom_branding' => $data['custom_branding']])
          <!-- Payment Successfull Header -->
          <table class="row" style="border-collapse: collapse; border-spacing: 0; padding: 0px; text-align: left; vertical-align: top; position: relative; width: 100%; display: block;"><tr style="padding: 0; text-align: left; vertical-align: top;"><td class="center" align="center" style="-moz-hyphens: auto; -webkit-hyphens: auto; border-collapse: collapse !important; hyphens: auto; word-break: break-word; padding: 0; text-align: center; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; line-height: 19px; margin: 0; font-size: 14px;">
                <center style="min-width: 580px; width: 100%;">

                  <table class="container" style="border-collapse: collapse; border-spacing: 0; padding: 0; text-align: inherit; vertical-align: top; margin: 0 auto; width: 580px;"><tr style="padding: 0; text-align: left; vertical-align: top;"><td class="wrapper center last" style="-moz-hyphens: auto; -webkit-hyphens: auto; border-collapse: collapse !important; hyphens: auto; word-break: break-word; padding: 10px 20px 0px 0px; text-align: center; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; line-height: 19px; margin: 0; font-size: 14px; position: relative; padding-right: 0px;">
                        <center style="min-width: 580px; width: 100%;">
                        <table class="twelve columns bluebg" style="border-collapse: collapse; border-spacing: 0; padding: 0; text-align: left; vertical-align: top; background: #39ACE5; margin: 0 auto; width: 580px;"><tr style="padding: 0; text-align: left; vertical-align: top;"><td class="center" style="-moz-hyphens: auto; -webkit-hyphens: auto; border-collapse: collapse !important; hyphens: auto; word-break: break-word; padding: 0px 0px 10px; text-align: center; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; line-height: 19px; margin: 0; font-size: 14px;">
                              <h1 style="color: #f2f2f2; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: bold; line-height: 1.3; margin: 0; padding: 0; text-align: center; word-break: normal; font-size: 32px; margin-top: 40px;">
                              <center style="min-width: 580px; width: 100%;">
                                Account created
                              </center>
                              </h1>
                            </td>
                            <td class="expander" style="-moz-hyphens: auto; -webkit-hyphens: auto; border-collapse: collapse !important; hyphens: auto; word-break: break-word; padding: 0 !important; text-align: left; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; line-height: 19px; margin: 0; font-size: 14px; visibility: hidden; width: 0px;"></td>
                          </tr></table></center>
                      </td>
                    </tr></table></center>
              </td>
            </tr></table>
          @include('emails.partials.header_image', ['image'=>'payment_green'], ['message'=>$message])
          <!-- Customer Information -->
          <table class="row" style="border-collapse: collapse; border-spacing: 0; padding: 0px; text-align: left; vertical-align: top; position: relative; width: 100%; display: block;"><tr style="padding: 0; text-align: left; vertical-align: top;"><td class="wrapper white" style="-moz-hyphens: auto; -webkit-hyphens: auto; border-collapse: collapse !important; hyphens: auto; word-break: break-word; padding: 10px 20px 0px 0px; text-align: left; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; line-height: 19px; margin: 0; font-size: 14px; background: #ffffff; background-color: #ffffff; position: relative;">
                <table class="eight columns" style="border-collapse: collapse; border-spacing: 0; padding: 0; text-align: left; vertical-align: top; margin: 0 auto; width: 380px;"><tr style="padding: 0; text-align: left; vertical-align: top;"><td class="lighttext left-text-pad" style="-moz-hyphens: auto; -webkit-hyphens: auto; border-collapse: collapse !important; hyphens: auto; word-break: break-word; padding: 0px 0px 10px; text-align: left; vertical-align: top; color: #B2B2B2; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; line-height: 19px; margin: 0; font-size: 12px; text-decoration: none; padding-left: 10px;">
                      <a class="email" style="color: inherit; text-decoration: none;">{{$user['email']}}</a>
                    </td>
                    <td class="expander" style="-moz-hyphens: auto; -webkit-hyphens: auto; border-collapse: collapse !important; hyphens: auto; word-break: break-word; padding: 0 !important; text-align: left; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; line-height: 19px; margin: 0; font-size: 14px; visibility: hidden; width: 0px;"></td>
                  </tr></table></td>
              <td class="wrapper white last" style="-moz-hyphens: auto; -webkit-hyphens: auto; border-collapse: collapse !important; hyphens: auto; word-break: break-word; padding: 10px 20px 0px 0px; text-align: left; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; line-height: 19px; margin: 0; font-size: 14px; background: #ffffff; background-color: #ffffff; position: relative; padding-right: 0px;">
                <table class="four columns" style="border-collapse: collapse; border-spacing: 0; padding: 0; text-align: left; vertical-align: top; margin: 0 auto; width: 180px;"><tr style="padding: 0; text-align: left; vertical-align: top;"><td class="right-text-pad lighttext" style="-moz-hyphens: auto; -webkit-hyphens: auto; border-collapse: collapse !important; hyphens: auto; word-break: break-word; padding: 0px 0px 10px; text-align: right; vertical-align: top; color: #B2B2B2; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; line-height: 19px; margin: 0; font-size: 12px; text-decoration: none; padding-right: 10px;">
                      {{$user['org']}}
                    </td>
                    <td class="expander" style="-moz-hyphens: auto; -webkit-hyphens: auto; border-collapse: collapse !important; hyphens: auto; word-break: break-word; padding: 0 !important; text-align: left; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; line-height: 19px; margin: 0; font-size: 14px; visibility: hidden; width: 0px;"></td>
                  </tr></table></td>
            </tr></table><!-- Contact Us --><table class="row" style="border-collapse: collapse; border-spacing: 0; padding: 0px; text-align: left; vertical-align: top; position: relative; width: 100%; display: block;"><tr style="padding: 0; text-align: left; vertical-align: top;"><td class="center" align="center" style="-moz-hyphens: auto; -webkit-hyphens: auto; border-collapse: collapse !important; hyphens: auto; word-break: break-word; padding: 0; text-align: center; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; line-height: 19px; margin: 0; font-size: 14px;">
                <center style="min-width: 580px; width: 100%;">
                  <table class="container" style="border-collapse: collapse; border-spacing: 0; padding: 0; text-align: inherit; vertical-align: top; margin: 0 auto; width: 580px;"><tr style="padding: 0; text-align: left; vertical-align: top;"><td class="wrapper last white" style="-moz-hyphens: auto; -webkit-hyphens: auto; border-collapse: collapse !important; hyphens: auto; word-break: break-word; padding: 10px 20px 0px 0px; text-align: left; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; line-height: 19px; margin: 0; font-size: 14px; background: #ffffff; background-color: #ffffff; position: relative; padding-right: 0px;">
                        <table class="twelve columns" style="border-collapse: collapse; border-spacing: 0; padding: 0; text-align: left; vertical-align: top; margin: 0 auto; width: 580px;"><tr style="padding: 0; text-align: left; vertical-align: top;"><td class="center lighttext" style="-moz-hyphens: auto; -webkit-hyphens: auto; border-collapse: collapse !important; hyphens: auto; word-break: break-word; padding: 0px 0px 10px; text-align: center; vertical-align: top; color: #B2B2B2; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; line-height: 19px; margin: 0; font-size: 12px; text-decoration: none;">
                              <hr class="wide" style="background-color: #d9d9d9; border: none; color: #E5E5E5; height: 2px; width: 490px;"><center style="min-width: 580px; width: 100%;">
                                If this is correct, you don't need to take any further action.<br>
                                Please <a title="Click to send us an email" href="mailto:contact@razorpay.com" style="color: #2ba6cb; text-decoration: none;">contact us</a> in case of any discrepancy.
                              </center>
                            </td>
                            <td class="expander" style="-moz-hyphens: auto; -webkit-hyphens: auto; border-collapse: collapse !important; hyphens: auto; word-break: break-word; padding: 0 !important; text-align: left; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; line-height: 19px; margin: 0; font-size: 14px; visibility: hidden; width: 0px;"></td>
                          </tr></table></td>
                    </tr></table></center>
              </td>
            </tr></table>
          @include('emails.partials.footer', ['message'=>$message])

          @if ($data['custom_branding'] === true)
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
                                <img src="{{ $data['email_logo'] }}" style="height: 32px;" />
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
      </tr></table></body></html>
