<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN" "http://www.w3.org/TR/REC-html40/loose.dtd">
<html><body style="-ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%; margin: 0; min-width: 100%; padding: 0; width: 100% !important; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; line-height: 19px; text-align: left; font-size: 14px;"><table class="row footer" style="border-collapse: collapse; border-spacing: 0; padding: 0px; text-align: left; vertical-align: top; position: relative; width: 100%;"><tr style="padding: 0; text-align: left; vertical-align: top;"><td class="wrapper last" style="-moz-hyphens: auto; -webkit-hyphens: auto; border-collapse: collapse !important; hyphens: auto; word-break: break-word; padding: 10px 20px 0px 0px; text-align: left; vertical-align: top; color: #aaa; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; line-height: 18px; margin: 0; font-size: 12px; position: relative; padding-right: 0px;">

      <table class="three columns offset-by-six" style="border-collapse: collapse; border-spacing: 0; padding: 0; text-align: left; vertical-align: top; margin: 0 auto; width: 130px;"><tr style="padding: 0; text-align: left; vertical-align: top;"><td class="center" style="-moz-hyphens: auto; -webkit-hyphens: auto; border-collapse: collapse !important; hyphens: auto; word-break: break-word; padding: 0px 0px 10px; text-align: center; vertical-align: top; color: #aaa; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; line-height: 18px; margin: 0; font-size: 12px;">
            <center style="min-width: 130px; width: 100%;">
              <a href="https://facebook.com/razorpay" class="logo" style="color: #aaa !important; text-decoration: none; display: inline-block; float: left !important; height: 22px; padding: 0px 5px 0px 5px; width: 22px;">
                <img height="22" width="22" src="https://cdn.razorpay.com/facebook.png" alt="Facebook Icon" title="Razorpay on Facebook" style="-ms-interpolation-mode: bicubic; clear: both; display: block; float: none; max-width: 100%; outline: none; text-decoration: none; width: auto; border: none;"></a>
              <a href="https://twitter.com/razorpay" class="logo" style="color: #aaa !important; text-decoration: none; display: inline-block; float: left !important; height: 22px; padding: 0px 5px 0px 5px; width: 22px;">
                <img height="22" width="22" src="https://cdn.razorpay.com/twitter.png" alt="Twitter Icon" title="Razorpay on Twitter" style="-ms-interpolation-mode: bicubic; clear: both; display: block; float: none; max-width: 100%; outline: none; text-decoration: none; width: auto; border: none;"></a>
              <a href="https://github.com/razorpay" class="logo" style="color: #aaa !important; text-decoration: none; display: inline-block; float: left !important; height: 22px; padding: 0px 5px 0px 5px; width: 22px;">
                <img height="22" width="22" src="https://cdn.razorpay.com/github.png" alt="GitHub Icon" title="Razorpay on GitHub" style="-ms-interpolation-mode: bicubic; clear: both; display: block; float: none; max-width: 100%; outline: none; text-decoration: none; width: auto; border: none;"></a>
            </center>
          </td>
          <td class="expander" style="-moz-hyphens: auto; -webkit-hyphens: auto; border-collapse: collapse !important; hyphens: auto; word-break: break-word; padding: 0 !important; text-align: left; vertical-align: top; color: #aaa; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; line-height: 18px; margin: 0; font-size: 12px; visibility: hidden; width: 0px;"></td>
        </tr></table></td>
</tr></table>
@if(!isset($showContact) || $showContact !== false)
    <table class="row footer" style="border-collapse: collapse; border-spacing: 0; padding: 0px; text-align: left; vertical-align: top; position: relative; width: 100%;"><tr style="padding: 0; text-align: left; vertical-align: top;"><td class="wrapper last" style="-moz-hyphens: auto; -webkit-hyphens: auto; border-collapse: collapse !important; hyphens: auto; word-break: break-word; padding: 10px 20px 0px 0px; text-align: left; vertical-align: top; color: #aaa; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; line-height: 18px; margin: 0; font-size: 12px; position: relative; padding-right: 0px;">
        <table class="seven columns" style="border-collapse: collapse; border-spacing: 0; padding: 0; text-align: left; vertical-align: top; margin: 0 auto; width: 330px;"><tr style="padding: 0; text-align: left; vertical-align: top;"><td class="center" style="-moz-hyphens: auto; -webkit-hyphens: auto; border-collapse: collapse !important; hyphens: auto; word-break: break-word; padding: 0px 0px 10px; text-align: center; vertical-align: top; color: #aaa; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; line-height: 18px; margin: 0; font-size: 12px;">
            <center style="min-width: 330px; width: 100%;">
                @if(isset($type) === true)
                    @include('emails.partials.support', ['type' => $type])
                @else
                    @include('emails.partials.support')
                @endif
              </center>
            </td>
            <td class="expander" style="-moz-hyphens: auto; -webkit-hyphens: auto; border-collapse: collapse !important; hyphens: auto; word-break: break-word; padding: 0 !important; text-align: left; vertical-align: top; color: #aaa; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; line-height: 18px; margin: 0; font-size: 12px; visibility: hidden; width: 0px;"></td>
          </tr></table></td>
    </tr></table>
@endif
</body></html>
