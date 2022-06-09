<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E;">
<head style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E;">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E;">
    <meta name="viewport" content="width=device-width" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E;">
    <style>
        @media only screen and (max-width: 480px) {
            .qr_code_image_address {
                display:none;
            }
        }
    </style>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E;">
<p style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E;">
    @php
        $themeBgColor   = $merchant['brand_color'];
        $themeFontColor = $merchant['brand_text_color'];
        $themed         = 'background-color: ' . $themeBgColor . '; color: ' . $merchant['brand_text_color'] . ';';

        $is_test_mode = $is_test_mode ?? false;

        $amountPaid = ($invoice['amount_paid']);

        if ((isset($payment)) && $payment['adjusted_amount']>0)
        {
            $amountPaid = $payment['adjusted_amount'];
        }

        $amountDue = $invoice['amount'] - $amountPaid;

        $amountPaidFormatted = number_format($amountPaid / 100, 2);
        $amountDueFormatted  = number_format($amountDue / 100, 2);

        $reportEmailUrl = 'https://razorpay.com/support/payments/report-merchant/?e=' . base64_encode($invoice['id']) . '&m=' . base64_encode($invoice['customer_details']['customer_email']) . '&s=' . base64_encode('customer_email');
        $showReportMailFlag = false;
        if (isset($view_preferences['exempt_customer_flagging']) === true) {
            $showReportMailFlag = !$is_test_mode and !$view_preferences['exempt_customer_flagging'];
        }
    @endphp

</p>
<center style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E; max-width: 600px;">
    <table class="table" border="0" cellpadding="0" cellspacing="0" height="100%" width="100%" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E; width: 100%; background-color: #fafafa; text-align: center"><tbody style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E;">
        <tr style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E;">
            <td class="first" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; padding: 24px 4%; padding-bottom: 0; border-left: 1px solid #f2f2f2; width: 3%; background-color: {{ $merchant['brand_color'] }}; color: {{ $merchant['brand_text_color'] }};"></td>
            <td colspan="2" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; padding-bottom: 0; width: 92%; background-color: {{ $merchant['brand_color'] }}; color: {{ $merchant['brand_text_color'] }}; padding: 24px 0 !important; border-left: 0; border-right: 0;">
                @yield('header')
            </td>
            <td class="last" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; padding: 24px 4%; padding-bottom: 0; border-right: 1px solid #f2f2f2; width: 3%; background-color: {{ $merchant['brand_color'] }}; color: {{ $merchant['brand_text_color'] }};"></td>
        </tr>

        @if ($is_test_mode)
            <tr style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E;">
                <td class="first" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; padding: 24px 4%; padding-bottom: 0; border-left: 1px solid #f2f2f2; width: 3%; background-color: {{ $merchant['brand_color'] }}; color: {{ $merchant['brand_text_color'] }};"></td>
                <td colspan="2" class="content" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; padding: 24px 4%; border-left: 1px solid #f2f2f2; border-right: 1px solid #f2f2f2; width: 92%; border-top: 1px solid #f2f2f2; background-color: #fcf8e3; border-color: #faebcc; color: #8a6d3b; padding-top: 12px; padding-bottom: 12px; font-size: 12px;">
                    This {{$invoice['type_label']}} is created in <b style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #8a6d3b;">Test Mode</b>. Only test payments can be made for this {{$invoice['type_label']}}.
                </td>
                <td class="last" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; padding: 24px 4%; padding-bottom: 0; border-right: 1px solid #f2f2f2; width: 3%; background-color: {{ $merchant['brand_color'] }}; color: {{ $merchant['brand_text_color'] }};"></td>
            </tr>
        @endif

        @if ($invoice['description'])
            <tr style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E; padding:100px 4%;">
                @if ($is_test_mode)
                    <td class="first" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E; padding-bottom: 0; border-left: 1px solid #f2f2f2; width: 3%;"></td>
                @else
                    <td class="first" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; padding-bottom: 0; border-left: 1px solid #f2f2f2; width: 3%; background-color: {{ $merchant['brand_color'] }}; color: {{ $merchant['brand_text_color'] }};"></td>
                @endif
                <td colspan="2" class="content" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E; padding-bottom: 0; background-color: #fff; border-left: 1px solid #f2f2f2; border-right: 1px solid #f2f2f2; width: 92%; border-top: 1px solid #f2f2f2; padding-top: 24px">
                    <div style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E;">
                        <label style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; font-size: 12px; color: #9B9B9B; font-weight: bold; text-transform: uppercase;">PAYMENT FOR</label>
                        <div style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E; white-space: pre-wrap;word-wrap: break-word; font-size: 14px; padding-left: 20px; padding-right:20px">{{$invoice['description']}}</div>
                    </div>
                </td>

                @if ($is_test_mode)
                    <td class="last" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E;  padding-bottom: 0; border-right: 1px solid #f2f2f2; width: 3%;"></td>
                @else
                    <td class="last" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; padding-bottom: 0; border-right: 1px solid #f2f2f2; width: 3%; background-color: {{ $merchant['brand_color'] }}; color: {{ $merchant['brand_text_color'] }};"></td>
                @endif
            </tr>
        @endif

        <tr style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E; text-align: center;">
            <td class="first" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E; padding-bottom: 0; border-left: 1px solid #f2f2f2; width: 3%;"></td>
            <td colspan="2" class="content footer" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E; padding-bottom: 0; background-color: #fff; border-left: 1px solid #f2f2f2; border-right: 1px solid #f2f2f2; width: 92%; padding-left: 0; padding-right: 0; padding-top: 24px;">
                <label style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; font-size: 12px; color: #9B9B9B; font-weight: bold; text-transform: uppercase;">
                    AMOUNT PAYABLE
                </label>
                <div style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E; font-weight: bold; font-size: 18px;">
                    {{$invoice['currency']}}
                    {{$amountDueFormatted}}
                </div>
            <td class="last" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E; padding-bottom: 0; border-right: 1px solid #f2f2f2; width: 3%;"></td>
        </tr>
        @if ($ctaLabel)
            <tr style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E; text-align: center;">
                <td class="first" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E; border-left: 1px solid #f2f2f2; width: 3%;"></td>
                <td colspan="2" class="content footer" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E; background-color: #fff; border-left: 1px solid #f2f2f2; border-right: 1px solid #f2f2f2; width: 92%; padding-left: 0; padding-right: 0; padding-top : 24px">
                        <a class="footer--cta" href="{{ $ctaHref }}" target="_blank" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; text-decoration: none; padding: 9px 0px; display: inline-block; border-radius: 4px; white-space: nowrap; cursor: pointer; color: {{ $merchant['brand_text_color'] }}; background-color: {{ $merchant['brand_color'] }}; border: 1px solid {{ $merchant['brand_color'] }}; width: 236px; font-weight: bold">
                            Proceed to pay
                        </a>
                    <div class='qr_code_image_address' style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E; font-size: 8px;">
                        With cards, netbanking, UPI and others
                    </div>
                    <div class='qr_code_image_address' style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E; font-size: 12px; font-weight: bold">
                        OR
                    </div>
                <td class="last" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E; border-right: 1px solid #f2f2f2; width: 3%;"></td>
            </tr>

        @endif
        <tr class="qr_code_image_address" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E; text-align: center;">
            <td class="first" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E; padding-bottom: 0; border-left: 1px solid #f2f2f2; width: 3%;"></td>
            <td colspan="2" class="content" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E; padding-bottom: 24px; background-color: #fff; border-left: 1px solid #f2f2f2; border-right: 1px solid #f2f2f2; width: 92%; padding-top: 5px">
                <img src={{$qr_code_image_address}} alt="QRCodeImage" style="width:236px; height:105px; border-radius: 4px" />
            </td>
        </tr>
        <tr style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E; text-align: center">
            <td class="first" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E; padding: 24px 4%; padding-bottom: 0; border-left: 1px solid #f2f2f2; width: 3%;"></td>
            <td colspan="2" class="text-center" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E; text-align: center; padding: 24px 4%; width: 92%; padding-bottom: 24px;">
                <div class="footerFerchant" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; font-size: 12px; font-weight: bold; color: #9B9B9B;">
                    {{$merchant['name']}}
                </div>
                <div class="footerFerchant__address" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; font-size: 10px; color: #9B9B9B;">
                    {{ $merchant['business_registered_address'] }}
                </div>
            </td>
            <td class="last" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E; padding: 24px 4%; padding-bottom: 0; border-right: 1px solid #f2f2f2; width: 3%;"></td>
        </tr>
        <tr style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E; text-align: center">
            <td class="first" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E; padding: 24px 4%; padding-bottom: 0; border-left: 1px solid #f2f2f2; width: 3%;"></td>
            <td colspan="2" class="content" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E; padding: 24px 4%; padding-bottom: 0; background-color: #fff; border-left: 1px solid #f2f2f2; width: 92%; border-right: 1px solid #f2f2f2; vertical-align: top; border-bottom: 1px solid #f2f2f2;">
                <table border="0" cellpadding="0" cellspacing="0" height="100%" width="100%" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E; width: 100%;"><tbody style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E;"><tr style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E;">
                        <td class="content" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E; vertical-align: top;">
                            <a href="#" target="_blank" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E; height: 24px;">
                                @if(isset($org) === true and empty($org['branding']) === false and empty($org['branding']['branding_logo']) === false)
                                    <img style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E; height: 24px;" src="{{$org['branding']['branding_logo']}}"></a>
                            @else
                                <img style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E; height: 24px;" src="https://razorpay.com/images/logo-black.png"></a>
                            @endif
                        </td>
                        <td class="content" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E;">
                            <div class="footerRZP" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; text-align: right; padding-left: 10%; padding-bottom: 24px; font-size: 10px; color: #9B9B9B;">
                                @if(isset($org) === true and empty($org['branding']) === false and empty($org['branding']['branding_logo']) === false)
                                    <img src="https://cdn.razorpay.com/static/assets/hostedpages/powered-by-rzp.svg" alt="logo" />
                                @else
                                    Sign up with <a href="https://razorpay.com/payment-links" target="_blank" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E;">Razorpay</a> to accept payments via links for your business.
                                    @if($showReportMailFlag === true)
                                        Please report this email if you find it to be suspicious<a href={{$reportEmailUrl}} target="_blank" rel="noopener" style="color: #528FF0; text-decoration: none;"><img src="https://cdn.razorpay.com/static/assets/email/flag.png" width="13px" style="vertical-align: middle; margin: 0 3px;" alt="report flag" />Report Email</a>
                                    @endif
                                @endif
                            </div>
                        </td>
                    </tr></tbody></table>
            </td>
            <td class="last" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E; padding: 24px 4%; padding-bottom: 0; border-right: 1px solid #f2f2f2; width: 3%;"></td>
        </tr>
        <tr style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E; text-align: center">
            <td class="first" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E; padding: 24px 4%; padding-bottom: 0; border-left: 1px solid #f2f2f2; width: 3%;"></td>
            <td colspan="2" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E; padding: 24px 4%; padding-bottom: 0; width: 92%;"></td>
            <td class="last" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E; padding: 24px 4%; padding-bottom: 0; border-right: 1px solid #f2f2f2; width: 3%;"></td>
        </tr>
        </tbody></table>
</center>

</body>
</html>
