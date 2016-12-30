<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  </head>
  <body leftmargin="0" marginwidth="0" topmargin="0" marginheight="0" offset="0" style="line-height: 1.5;width: 100% !important;margin: 0;padding: 0; font-size: 15px;">
    <center>
        <table border="0" cellpadding="0" cellspacing="0" height="100%" width="100%" style="max-width: 600px">
            <tbody>
              <tr>
                <td colspan="3">
                  <h2>Hi {{$firstName}},</h2>
                </td>
              </tr>
              <tr>
                <td colspan="3">
                  <p>We got a request to reset your password for your administrator account in {{$orgName}}.</p>
                </td>
              </tr>
              <tr>
                <td style="width: 25%"></td>
                <td style="width: 33%">
                  <a href="{{$resetUrl}}" target="_blank" style="
                    text-decoration: none;
                    padding: 7px 20px;
                    display: inline-block;
                    border: 2px solid #3a3f51;
                    border-radius: 5px;
                    color: #3a3f51;
                    white-space: nowrap;
                    margin: 15px 0;
                    cursor: pointer;
                  ">
                    Reset Password
                  </a>
                </td>
                <td style="width: 25%"></td>
              </tr>
              <tr>
                <td colspan="3">
                  <p>
                    If you ignore this email, your password won't be changed. If you didn't initiate a password reset, <a href="mailto:support@razorpay.com">let us know</a>.
                  </p>
                </td>
              </tr>
            </tbody>
          </table>
      </center>
  </body>
</html>
