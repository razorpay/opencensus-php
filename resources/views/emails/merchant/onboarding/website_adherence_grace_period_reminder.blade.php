<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width" />
    <style>
        @media (max-width: 480px) {
            .mail-content {
                margin-left: 12px !important;
                margin-right: 12px !important;
            }
        }
        @media (min-width: 1024px) {
            .mail-content {
                margin-left: 24px !important;
                margin-right: 24px !important;
            }
        }
    </style>
</head>
<body>
<table style="margin:0 auto">
    <tr>
        <td style="background-color: #f8f9f9; font-family: Trebuchet MS;max-width:500px">
            <table style="width: 100%;">
                <tr>
                    <td style="background-color: #082654; border-radius: 2px 2px 0px 0px; padding-top: 20px; height: 120px; text-align: center; vertical-align: top;">
                        <!-- Razorpay logo -->
                        <img style="height: 30px;" src="https://cdn.razorpay.com/logo_invert.png" alt="razorpay-logo" />
                    </td>
                </tr>
            </table>
            <table class="mail-content" style="margin: -75px 18px 24px; border-collapse: collapse;">
                <tr>
                    <td style="background-color: #ffffff; padding: 24px;">
                        <table style="border-collapse: collapse;">
                            <tr>
                                <td style="margin-bottom: 8px;">
                                    <h4 style="color: #0d2366; font-weight: 700; font-size: 18px; text-align: center; margin-bottom: 8px; margin-top: 0; line-height: 27px;">
                                        Update content on your website/app to avoid payment disruption
                                    </h4>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <span style="display: block; height: 4px; width: 24px; background-color: #2dd589; margin: 0 auto;"> </span>
                                </td>
                            </tr>
                            <tr>
                                <td style="font-family: 'Trebuchet MS'; font-weight: 400; font-size: 14px; line-height: 20px; color: #23496d; background-color: #ffffff;">
                                    <p>
                                        Hi {{{$merchant['name']}}},
                                        <br />
                                        <br /> This is a friendly reminder to update the content created for the below pages using Razorpay on your website/app on priority before {[date}}:
                                    </p>
                                    <table>
                                        <tr>
                                            <td style="padding-left: 7.5px;">
                                                @isset($merchant['sections']['terms'])
                                                    <p style="margin-top: 0; margin-bottom: 2px;text-indent: -17px; padding-left: 17px;">&bull;&nbsp;&nbsp;
                                                        Terms and Conditions
                                                    </p>
                                                    @isset($merchant['sections']['contact_us'])
                                                        <p style="margin-top: 0; margin-bottom: 2px;text-indent: -17px; padding-left: 17px;">&bull;&nbsp;&nbsp;
                                                            Contact Us
                                                        </p>
                                                    @endisset
                                                    @isset($merchant['sections']['refund'])
                                                        <p style="margin-top: 0; margin-bottom: 2px;text-indent: -17px; padding-left: 17px;">&bull;&nbsp;&nbsp;
                                                            Cancellation and Refund Policy
                                                        </p>
                                                    @endisset
                                                    @isset($merchant['sections']['privacy'])
                                                        <p style="margin-top: 0; margin-bottom: 2px;text-indent: -17px; padding-left: 17px;">&bull;&nbsp;&nbsp;
                                                            Privacy Policy
                                                        </p>
                                                    @endisset
                                                    @isset($merchant['sections']['shipping'])
                                                        <p style="margin-top: 0; margin-bottom: 2px;text-indent: -17px; padding-left: 17px;">&bull;&nbsp;&nbsp;
                                                            Shipping and Delivery Policy
                                                        </p>
                                                    @endisset
                                            </td>
                                        </tr>
                                    </table>
                                    <p style="margin-bottom: 32px;">
                                        Payments and settlements for your business will be put on-hold if the content is not updated before the given date as per RBI guidelines.
                                        <br />
                                        <br /> In case you have already updated the content, please let us know your feedback here: <a href="{{link}}">{{link}}
                                            <a />
                                            <br />
                                            <br /> Thank you,
                                            <br /> Team Razorpay
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <a href="https://dashboard.razorpay.com/app/website-app-details" target="_blank" style="
                              display: block;
                              text-decoration: none;
                              height: 40px;
                              background: #2b83ea;
                              border-radius: 2px;
                              color: #ffffff;
                              border: none;
                              margin: 0 auto;
                              text-align: center;
                              width: 155px;
                              line-height: 40px;
                              ">
                                        Update Details
                                    </a>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
            <p style="font-family: 'Trebuchet MS'; font-style: normal; font-weight: 400; font-size: 12px; line-height: 18px; text-align: center; color: #7b8199;">
                If you have any issues with services from
                <br /> Razorpay, Please raise your request <a href="https://dashboard.razorpay.com/#/app/dashboard#request" target="_blank" style="color: #528ff0; text-decoration: none;">here</a>.
            </p>
        </td>
    </tr>
</table>
</body>
</html>
