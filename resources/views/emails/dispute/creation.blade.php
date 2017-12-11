<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #212121;">
<head style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #212121;">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #212121;">
<meta name="viewport" content="width=device-width" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #212121;">
</head>
<body style="width: 100% !important; min-width: 100%; -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; padding: 0; margin: 0; text-align: left; font-size: 14px; line-height: 19px;">
<p style="color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; padding: 0; margin: 0; text-align: left; font-size: 14px; line-height: 19px; margin-bottom: 10px;">
    @php
        $brand_color = '#6A75ED';
        $brand_text_color = '#FFFFFF';

        $expiryDate = date('jS F Y', $dispute['expires_on']);

        $amount = sprintf('%0.2f', ($dispute['amount'] / 100));
        $amount = floatval($amount);

        $paymentLink = 'https://dashboard.razorpay.com/#/app/payments/pay_' . $dispute['payment_id'];

        $noteResult = 'failing which the case will be lost and the corresponding amount will be debited from the current balance. ';

        if (($dispute['phase'] === 'retrieval') or ($dispute['phase'] === 'fraud'))
        {
            $noteResult = 'failing which the corresponding amount might be debited from the current balance. ';
        }

    @endphp
  
  </p>
    <center style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #212121; width: 100%; min-width: 580px; background-color: #fafafa;">

        <div style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; text-align: center; background-color: {{ $brand_color }}; color: {{ $brand_text_color }}; padding: 30px 0 135px !important;">

            <h2 style="font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; padding: 0; word-break: normal; margin: 0; font-size: 20px; line-height: 24px; text-align: center; color: {{ $brand_text_color }};">
                  Dispute raised for Rs. {{ $amount }}
              </h2>

            <div style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; margin-top: 12px; color: {{ $brand_text_color }};">
                <div style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: {{ $brand_text_color }};">
                    Please respond by {{ $expiryDate }}
                </div>
              </div>
        </div>



        <table class="table" border="0" cellpadding="0" cellspacing="0" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #212121; border-spacing: 0; border-collapse: collapse; padding: 0; vertical-align: top; text-align: left; width: 100%; background-color: #fafafa; margin-top: -100px !important; height: 100%; wdith: 80%; max-width: 600px; margin: 0 auto; font-size: 12px;"><tbody style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #212121;">
<tr style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #212121; padding: 0; vertical-align: top; text-align: left;">
<td class="content" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; text-align: left; font-size: 14px; line-height: 19px; padding: 24px 4%; background-color: #fff; border-left: 1px solid #f2f2f2; border-right: 1px solid #f2f2f2; color: #000000; padding-bottom: 24px;">
                    <strong class="text-uppercase lh-26" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; color: #8A8A8A; font-size: 12px; text-transform: uppercase; line-height: 26px;">Payment ID</strong> <br style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #212121;"><a href="{{ $paymentLink }}" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; text-decoration: none; color: #39ACE5;"> pay_{{ $dispute['payment_id'] }} </a>
                </td>
            </tr>
<tr style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #212121; padding: 0; vertical-align: top; text-align: left;">
<td class="content" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; text-align: left; font-size: 14px; line-height: 19px; padding: 24px 4%; background-color: #fff; border-left: 1px solid #f2f2f2; border-right: 1px solid #f2f2f2; color: #000000; padding-bottom: 24px; padding-top: 0px;">
                    <div style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #212121;">
                        <strong class="text-uppercase lh-26" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; color: #8A8A8A; font-size: 12px; text-transform: uppercase; line-height: 26px;">Dispute ID</strong> <br style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #212121;"> {{ $dispute['id'] }}
                    </div>
                </td>
            </tr>
<tr style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #212121; padding: 0; vertical-align: top; text-align: left;">
<td class="content" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; text-align: left; font-size: 14px; line-height: 19px; padding: 24px 4%; background-color: #fff; border-left: 1px solid #f2f2f2; border-right: 1px solid #f2f2f2; color: #000000; padding-bottom: 24px; padding-top: 0px;">
                    <div style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #212121;">
                        <strong class="text-uppercase lh-26" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; color: #8A8A8A; font-size: 12px; text-transform: uppercase; line-height: 26px;">Gateway case ID</strong> <br style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #212121;"> {{ $dispute['gateway_dispute_id'] }}
                    </div>
                </td>
            </tr>
<tr style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #212121; padding: 0; vertical-align: top; text-align: left;">
<td class="content" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; text-align: left; font-size: 14px; line-height: 19px; padding: 24px 4%; background-color: #fff; border-left: 1px solid #f2f2f2; border-right: 1px solid #f2f2f2; color: #000000; padding-bottom: 0px; padding-top: 0px;">
                    <div style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #212121;">
                        <strong class="text-uppercase lh-26" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; color: #8A8A8A; font-size: 12px; text-transform: uppercase; line-height: 26px;">Dispute Type and Reason</strong> <br style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #212121;"><span style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #212121; text-transform: capitalize;"> <b style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #212121;"> {{ $dispute['phase'] }} </b> : {{ $dispute['reason_description'] }} </span>
                    </div>
                </td>
            </tr>
<tr style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #212121; padding: 0; vertical-align: top; text-align: left;">
<td class="content" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; text-align: left; font-size: 14px; line-height: 19px; padding: 24px 4%; background-color: #fff; border-left: 1px solid #f2f2f2; border-right: 1px solid #f2f2f2; color: #000000; padding-bottom: 24px !important;">
                    <div style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #212121;">
                        <strong class="text-uppercase lh-26" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; color: #8A8A8A; font-size: 12px; text-transform: uppercase; line-height: 26px;">Deadline for resolution</strong> <br style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #212121;"><b style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #212121;"> {{ $expiryDate }} </b> ( in {{ $remainingDays }} days )
                    </div>
                </td>
            </tr>
<tr style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #212121; padding: 0; vertical-align: top; text-align: left;">
<td class="content" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; text-align: left; font-size: 14px; line-height: 19px; padding: 24px 4%; padding-bottom: 0; background-color: #fff; border-left: 1px solid #f2f2f2; border-right: 1px solid #f2f2f2; color: #000000; border-top: dashed 1px rgba(0,0,0,0.1); border-bottom: solid 1px rgba(0,0,0,0.05); padding-top: 0px;">
                    <div style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #000000;">
                        <br style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #212121;">
                        Hey,
                        <br style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #212121;"><br style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #212121;">
                        We have received a dispute for Rs. {{ $amount }} on pay_{{ $dispute['payment_id'] }} against {{ $merchant['name'] }}. Please share all proofs like invoices, proof of delivery of product/service and any relevant screenshots pertaining to the transaction in a consolidated ZIP archive named as the Payment ID.
                        <br style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #212121;"><br style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #212121;">
                        Note: Kindly <b style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #212121;"> reply to this email </b> with the requested documents in the required format by the deadline, {{ $noteResult }}
                        <br style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #212121;"><br style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #212121;">
</div>
                </td>
            </tr>
<tr style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #212121; padding: 0; vertical-align: top; text-align: left; height: 30px;"></tr>
<tr style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #212121; padding: 0; vertical-align: top; text-align: left;">
<td class="content" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; text-align: left; font-size: 14px; line-height: 19px; padding: 24px 4%; padding-bottom: 0; background-color: #fff; border-left: 1px solid #f2f2f2; color: #000000; border-right: 1px solid #f2f2f2; vertical-align: top; border-bottom: 1px solid #f2f2f2; border-top: 1px solid #f2f2f2;">

                    <table border="0" cellpadding="0" cellspacing="0" height="100%" width="100%" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #212121; border-spacing: 0; border-collapse: collapse; padding: 0; vertical-align: top; text-align: left; width: 100%;"><tbody style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #212121;"><tr style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #212121; padding: 0; vertical-align: top; text-align: left;">
<td class="content" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; padding: 0; margin: 0; text-align: left; font-size: 14px; line-height: 19px; vertical-align: top;">
                                    <a href="https://razorpay.com/" target="_blank" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; text-decoration: none; color: #39ACE5; height: 24px;">
                                        <img style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #212121; outline: none; text-decoration: none; -ms-interpolation-mode: bicubic; width: auto; max-width: 100%; float: left; clear: both; display: block; border: none; height: 24px;" src="https://razorpay.com/images/logo-black.png"></a>
                                </td>
                                <td class="content" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; padding: 0; margin: 0; text-align: left; font-size: 14px; line-height: 19px;">
                                    <div class="footerRZP" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; text-align: right; padding-left: 10%; padding-bottom: 24px; font-size: 10px; color: #757575;">
                                        For any queries, please reply to this email.
                                    </div>
                                </td>
                            </tr></tbody></table>
</td>
            </tr>
<tr style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #212121; padding: 0; vertical-align: top; text-align: left;">
<td style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; text-align: left; font-size: 14px; line-height: 19px; padding: 24px 4%; padding-bottom: 0;"></td>
            </tr>
</tbody></table>
</center>
  
</body>
</html>