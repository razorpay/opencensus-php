<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E;">
<head style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E;">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E;">
    <meta name="viewport" content="width=device-width" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E;">
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E;">
<p style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E;">
    @php
        $themeBgColor   = $merchant['brand_color'];
        $themeFontColor = $merchant['brand_text_color'];
        $themed         = 'background-color: ' . $themeBgColor . '; color: ' . $merchant['brand_text_color'] . ';';

        $is_test_mode = $is_test_mode ?? false;

        $amountPayable = $payment_link['amount'];
        $amountPayableFormatted = number_format($amountPayable / 100, 2);
    @endphp

</p>
<center style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E; max-width: 600px;">
    <table class="table" border="0" cellpadding="0" cellspacing="0" height="100%" width="100%" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E; width: 100%; background-color: #fafafa;"><tbody style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E;">
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
                    This Payment request is created in <b style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #8a6d3b;">Test Mode</b>. Only test payments can be made for it.
                </td>
                <td class="last" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; padding: 24px 4%; padding-bottom: 0; border-right: 1px solid #f2f2f2; width: 3%; background-color: {{ $merchant['brand_color'] }}; color: {{ $merchant['brand_text_color'] }};"></td>
            </tr>
        @endif


        <tr style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E;">
            @if ($is_test_mode)
                <td class="first" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E; padding: 24px 4%; padding-bottom: 0; border-left: 1px solid #f2f2f2; width: 3%;"></td>
            @else
                <td class="first" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; padding: 24px 4%; padding-bottom: 0; border-left: 1px solid #f2f2f2; width: 3%; background-color: {{ $merchant['brand_color'] }}; color: {{ $merchant['brand_text_color'] }};"></td>
            @endif
            <td colspan="2" class="content" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E; padding: 24px 4%; padding-bottom: 0; background-color: #fff; border-left: 1px solid #f2f2f2; border-right: 1px solid #f2f2f2; width: 92%; border-top: 1px solid #f2f2f2;">
                <div style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E;">
                    {{ $merchant['name'] }} has requested a payment for {{ $payment_link['title'] }}. You can view more details and complete your payment by visiting this page <a href="{{ $payment_link['short_url'] }}">{{ $payment_link['short_url'] }}</a> or clicking the button below.
                </div>
            </td>

            @if ($is_test_mode)
                <td class="last" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E; padding: 24px 4%; padding-bottom: 0; border-right: 1px solid #f2f2f2; width: 3%;"></td>
            @else
                <td class="last" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; padding: 24px 4%; padding-bottom: 0; border-right: 1px solid #f2f2f2; width: 3%; background-color: {{ $merchant['brand_color'] }}; color: {{ $merchant['brand_text_color'] }};"></td>
            @endif
        </tr>

        @if ($payment_link['expire_by_formatted'])
            <tr style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E;">
                <td class="first" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E; padding: 24px 4%; padding-bottom: 0; border-left: 1px solid #f2f2f2; width: 3%;"></td>
                <td colspan="2" class="content" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E; padding: 24px 4%; background-color: #fff; border-left: 1px solid #f2f2f2; border-right: 1px solid #f2f2f2; width: 92%; padding-bottom: 24px;">
                    <label style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; font-size: 12px; color: #9B9B9B; font-weight: bold; text-transform: uppercase;">EXPIRES ON</label>
                    <div style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E;">{{ $payment_link['expire_by_formatted'] }}</div>
                </td>
                <td class="last" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E; padding: 24px 4%; padding-bottom: 0; border-right: 1px solid #f2f2f2; width: 3%;"></td>
            </tr>
        @endif
        <tr style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E;">
            <td class="first" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E; padding: 24px 4%; padding-bottom: 0; border-left: 1px solid #f2f2f2; width: 3%;"></td>
            <td colspan="2" class="content footer" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E; border-bottom: 1px solid #f2f2f2; border-top: 1px dashed #e5e5e5; padding: 24px 4%; padding-bottom: 0; background-color: #fff; border-left: 1px solid #f2f2f2; border-right: 1px solid #f2f2f2; width: 92%; padding-left: 0; padding-right: 0; padding-top: 0;">
                <table class="table" border="0" cellpadding="0" cellspacing="0" height="100%" width="100%" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E; width: 100%; background-color: #fafafa;"><tbody style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E;">
                    <tr style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E;">
                        @if (empty($payment_link['amount']) === false)
                            <td style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E; padding: 24px 4%; background-color: #fff;">
                                <label style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; font-size: 12px; color: #9B9B9B; font-weight: bold; text-transform: uppercase;">AMOUNT PAYABLE</label>
                                <div style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E; font-weight: bold; font-size: 18px;">
                                    {{$payment_link['currency']}} {{$amountPayableFormatted}}
                                </div>
                            </td>
                        @endif
                        <td rowspan="2" class="text-right" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E; text-align: right; padding: 24px 4%; background-color: #fff; vertical-align: bottom;">
                            @yield("footerCTA")
                        </td>
                    </tr>
                    </tbody></table>
            </td>
            <td class="last" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E; padding: 24px 4%; padding-bottom: 0; border-right: 1px solid #f2f2f2; width: 3%;"></td>
        </tr>
        <tr style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E;">
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
        <tr style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E;">
            <td class="first" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E; padding: 24px 4%; padding-bottom: 0; border-left: 1px solid #f2f2f2; width: 3%;"></td>
            <td colspan="2" class="content" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E; padding: 24px 4%; padding-bottom: 0; background-color: #fff; border-left: 1px solid #f2f2f2; width: 92%; border-right: 1px solid #f2f2f2; vertical-align: top; border-bottom: 1px solid #f2f2f2; border-top: 1px solid #f2f2f2;">
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
                                Create customised payment pages to collect payments from your customers. <a href="https://razorpay.com/payment-pages/?utm_source=email&utm_medium=footer&utm_campaign=paymentpage" target="_blank" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E;"> Know more</a>.
                            </div>
                        </td>
                    </tr></tbody></table>
            </td>
            <td class="last" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E; padding: 24px 4%; padding-bottom: 0; border-right: 1px solid #f2f2f2; width: 3%;"></td>
        </tr>
        <tr style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E;">
            <td class="first" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E; padding: 24px 4%; padding-bottom: 0; border-left: 1px solid #f2f2f2; width: 3%;"></td>
            <td colspan="2" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E; padding: 24px 4%; padding-bottom: 0; width: 92%;"></td>
            <td class="last" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E; padding: 24px 4%; padding-bottom: 0; border-right: 1px solid #f2f2f2; width: 3%;"></td>
        </tr>
        </tbody></table>
</center>

</body>
</html>
