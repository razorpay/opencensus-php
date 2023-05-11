<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <meta name="viewport" content="width=device-width">

        <meta property="og:title" content="Card Saving">

        <title>Card Saving</title>

    <style type="text/css">
        #outlook a{
            padding:0;
        }
        body{
            width:100% !important;
            background:#f7fcfe;
            color:#444444;
        }
        body{
            -webkit-text-size-adjust:none;
        }
        body{
            margin:0;
            padding:0;
            font-family:-apple-system,".SFNSDisplay","Oxygen","Ubuntu","Roboto","Segoe UI","Helvetica Neue","Lucida Grande",sans-serif;
            font-weight:200;
        }
        img{
            border:none;
            font-size:14px;
            font-weight:bold;
            height:auto;
            line-height:100%;
            outline:none;
            text-decoration:none;
            text-transform:capitalize;
        }
        #backgroundTable{
            height:100% !important;
            margin:0;
            padding:0;
            width:580px !important;
        }
        a,a:visited,a:active,a:link,a:hover{
            color:#1aace5;
            text-decoration:none;
        }
</style></head>
    <body leftmargin="0" marginwidth="0" topmargin="0" marginheight="0" offset="0">
        <center mc:edit="all_content"><center style="margin: 0 auto; background: #ffffff; box-shadow: 0 0 8px 0 rgba(0, 0, 0, 0.1);">
<table border="0" cellpadding="0" cellspacing="0" height="100%" id="backgroundTable">
    <tbody>
        <tr style="height: 25px">
            <td style="font-family:-apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif;">&nbsp;</td>
        </tr>
        @if ($custom_branding === false)
          <tr>
              <td style="font-family:-apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif;">
              <center><a href="https://razorpay.com/" target="_blank"><img height="28px" src="https://cdn.razorpay.com/logo.png" width="126px"></a></center>
              </td>
          </tr>
        @endif
        <tr style="height: 25px">
            <td style="font-family:-apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif;">&nbsp;</td>
        </tr>
    </tbody>
</table>

<table border="0" cellpadding="0" cellspacing="0" height="100%" id="contentTable" width="500px">
    <tbody>
        <tr>
            <td style="text-align: center;font-size: 13px;line-height: 22px;padding: 40px 10px;background-color: #f6f6f6;">

            <center>
              <!-- Card -->
              <table border="0" cellpadding="0" cellspacing="0" style="background: {{$card['color']}}; font-family: Monospace; color: #fff1f5; border-radius: 5px; min-width: 270px;">
                <tbody>
                  <tr>
                    <td style="text-align: left;">
                      <div style="max-height: 25px; margin-left: 20px; margin-top: 20px;">
                        <img
                          style="width: auto; max-height: 25px;"
                          src="https://cdn.razorpay.com/mailers/cardsaving/{{$card['network']}}.png"
                          />
                      </div>
                    </td>
                  </tr>
                  <tr>
                    <td style="font-size: 16px; padding: 20px 20px 10px; font-family: Monospace;">
                      <div>{{$card['number']}}</div>
                    </td>
                  </tr>
                  <tr>
                    <td style="text-align: left;">
                      <div style="margin-left: 20px; margin-bottom: 20px; font-family: Monospace;">
                        Expiry<br>
                        {{$card['expiry']}}
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>

              <!-- Card Shadow -->
              <table border="0" cellpadding="0" cellspacing="0">
                <tr>
                  <td>
                    <img src="http://i.imgur.com/UcckG32.png" style="width: 100%;" />
                  </td>
                </tr>
              </table>
            </center>

            <center>
              <table>
                <tr>
                  <td style="text-align: center;">
                    <!--<img src="http://imgur.com/aUcogPb.png">-->
                    <div style="margin-bottom: 15px;"><b>Card Tokenized Successfully!</b></div>
                    <div>
                        <div style="width: 250px;">
                        You have successfully tokenized and saved your card on Razorpay according to RBI guidelines.
                        </div>
                    </div>
                  </td>
                </tr>
              </table>
            </center>
            </td>
        </tr>
    </tbody>
 </table>

<table border="0" cellpadding="0" cellspacing="0" height="100%" id="contentTable" width="500px">
    <tbody>
        <tr style="font-size:14px; line-height: 24px; text-align: center;">
            <td style="font-family:-apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif;">
            <div style="margin-top: 40px;">
            Your card details are safe. You can view, add and manage cards via Razorpay’s secure Checkout.
            </div>
            </td>
        </tr>
     </tbody>
   </table>
            <table border="0" cellspacing="0" cellpadding="0" width="250px">
                <tbody>
                    <tr>
                        <td bgcolor="#008CC9" style="color:#ffffff;font-size:16px;border-color:#008cc9;background-color:#008cc9;border-radius:2px;border-width:1px;border-style:solid; text-align: center; border-radius: 30px;">
                        <a href="https://razorpay.com/flashcheckout/manage" style="color:#ffffff;display: block;text-decoration:none;padding: 12px 16px;cursor: pointer;" target="_blank">MANAGE YOUR CARDS</a>
                        </td>
                     </tr>
                  </tbody>
             </table>

<table border="0" cellpadding="0" cellspacing="0" height="100%" id="contentTable" width="500px">
    <tbody>
        <tr style="height: 80px;">
            <td style="font-family:-apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif;">&nbsp;</td>
        </tr>
        <tr>
            <td style="font-family:-apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif;border-top: 1px solid #eeeeee; height: 40px">&nbsp;</td>
        </tr>
        <tr style="height: 20px;">
            <td style="font-family:-apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif;">&nbsp;</td>
        </tr>
        @if ($custom_branding === false)
          <tr>
              <td style="font-family:-apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif;">
              <center><a href="https://razorpay.com/" target="_blank"><img height="28px" src="https://cdn.razorpay.com/logo.png" width="126px"></a></center>
              </td>
          </tr>
        @endif
        <tr style="height: 10px;font-size:1px;">
            <td style="font-family:-apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif;">&nbsp;</td>
        </tr>
        <tr style="font-size:12px; line-height: 14px;">
            <td style="font-family:-apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif;">
            <center>Payments Simplified</center>
            </td>
        </tr>
        <tr style="height: 10px;font-size:1px;">
            <td style="font-family:-apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif;">&nbsp;</td>
        </tr>
        <tr style="font-size:12px; line-height: 14px;">
            <td style="font-family:-apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif;">
            <center> <a href="https://github.com/razorpay" style="text-decoration: none;"> <img src="https://cloud.githubusercontent.com/assets/4686195/12816621/6170f53a-cb73-11e5-8d55-e8c8c5ce4ae2.png"> </a> &nbsp; <a href="https://twitter.com/Razorpay" style="text-decoration: none;"> <img src="https://cloud.githubusercontent.com/assets/4686195/12816622/61727c84-cb73-11e5-9683-2e6bcd6b37ee.png"> </a> &nbsp; <a href="https://www.facebook.com/Razorpay/" style="text-decoration: none;"><img src="https://cloud.githubusercontent.com/assets/4686195/12816620/617017dc-cb73-11e5-89a3-84db7af15551.png"> </a> &nbsp; </center>
            </td>
        </tr>
        <tr style="height: 20px;">
            <td style="font-family:-apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif;">&nbsp;</td>
        </tr>
        @if ($custom_branding)
          <tr>
              <td style="font-family:-apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif;">
              <center><img src="{{ $email_logo }}" style="height: 32px;"></center>
              </td>
          </tr>
        @endif
    </tbody>
</table>
</center>
</center>
</body>
</html>
