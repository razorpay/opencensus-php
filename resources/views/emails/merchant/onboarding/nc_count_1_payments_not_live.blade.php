<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta name="viewport" content="width=device-width"/>
    <link href="https://fonts.googleapis.com/css?family=Lato" rel="stylesheet" type="text/css">
    <style>
        .device-padding {
            padding: 40px !important;
        }

        .doc-table {
            display: block !important;
        }

        .doc-list {
            display: none !important;
        }

        @media (max-width: 480px) {
            .device-padding {
                padding: 24px !important;
            }

            .doc-table {
                display: none !important;
            }

            .doc-list {
                display: block !important;
            }
        }
    </style>
</head>

<body style="background-color: #f8f9f9;font-family: 'Lato', Tahoma, Verdana, Segoe, sans-serif;">
<table style="margin: 0 auto;">
    <tr>
        <td style="max-width: 600px;">
            <table style="margin: 0px; border-collapse: collapse;">
                <tr>
                    <td style="background-color: #ffffff; border-radius: 16px;" class="device-padding">
                        <table style="border-collapse: collapse;">
                            <tr>
                                <td style="font-size: 16px; color: #23496d; line-height: 24px;">
                                    <div>
                                        <img style="width: 95px;" src="https://cdn.razorpay.com/logo.png"/>
                                    </div>
                                    <div style="margin-top: 20px; margin-bottom: 20px;">
                      <span style="display: block; height: 4px; width: 24px; background-color:  #5CA2F7;">
                      </span>
                                    </div>
                                    <div>
                                        Hey {{{$merchant['name']}}},
                                    </div>
                                    <div>
                                        Hope you're doing well.
                                    </div>
                                    <br/>
                                    <div>
                                        In order to complete KYC verification for your account, we need a few more
                                        details from you.
                                    </div>
                                    <br/>
                                    <div>
                                        As a next step, we request you to go to your Razorpay dashboard and take the
                                        action required as
                                        per the below given
                                        instructions immediately.
                                    </div>
                                    <br/>
                                    <div style="background-color: #f54a2a14; border-radius: 12px; padding: 16px;">
                                        Please note, you’ll be able to collect payments from customers and receive them
                                        in your bank account only after the required details are updated.
                                    </div>
                                    <table
                                        style="margin-top:24px; margin-bottom: 24px; font-size: 14px; line-height: 20px; border-collapse: collapse;"
                                        class="doc-table">
                                        <tr style="line-height: 24px;">
                                            <th style="padding: 10px; text-align:left; width: 33%; border-bottom: 1px solid #c4cbd752;">
                                                Detail/document
                                            </th>
                                            <th
                                                style="text-align:left; width: 67%; padding: 10px; padding-bottom: 12px; border-bottom: 1.5px solid #c4cbd752;">
                                                Action required
                                            </th>
                                        </tr>
                                        @foreach($clarification_details as $key=>$value)
                                            <tr>
                                                <td style="padding: 10px; padding-bottom: 12px; border-bottom: 1px solid #c4cbd733;">
                                                    {{$key}}
                                                </td>
                                                <td style="padding: 10px; padding-bottom: 12px; border-bottom: 1px solid #c4cbd733;">
                                                    {{$value}}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </table>
                                    <table class="doc-list" style="font-size: 15px;">
                                        @foreach($clarification_details as $key=>$value)
                                            <tr>
                                                <td style="vertical-align: top; padding-right: 10px; padding-top: 15px;">
                                                    &bull;
                                                </td>
                                                <td style="border-bottom: 1px solid #3246641f; padding-bottom: 15px; padding-top: 12px;">
                                                    <b>
                                                        {{$key}}
                                                    </b><br/>
                                                    {{$value}}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </table>
                                    <br/>
                                    <a href="https://easy.razorpay.com/onboarding/needs-clarification" target="_blank"
                                       style="display: block;text-decoration: none;color: #ffffff;border: none;text-align: center;border-radius: 12px;background: #1566F1;width: 160px;height: 56px;line-height: 56px;margin-bottom: 24px;box-shadow: 0px 8px 16px 4px #1566f133;">
                                        Resolve Now
                                    </a>
                                    <div>
                                        Thank You,
                                    </div>
                                    <div style="font-weight: bold;">
                                        Team Razorpay
                                    </div>
                                    <div style="font-size: 14px; line-height: 20px; color:#213554ab; margin-top:24px">
                                        For help on how to resolve the above clarifications, <a href="https://razorpay.com/docs/payments/account-activation-support" target="_blank"
                                                                                                style="color: #1566f1; text-decoration: none;">please
                                            go here.</a>
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
            <div
                style="font-size: 12px; line-height: 18px; color: #21355461; border-top: 1px solid #3246641f; padding: 8px; width: 90%; margin: 0 auto; margin-top: 24px;">
                If you’ve any questions, we’ll be happy to help. <a href="https://razorpay.com/support/" target="_blank"
                                                                    style="color: #1566f1; text-decoration: none;">Reach
                    out to us for support</a>
            </div>
        </td>
    </tr>
</table>
</body>

</html>
