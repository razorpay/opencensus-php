<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" style="color: #212121; font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px;"><head style="color: #212121; font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px;"><meta http-equiv="Content-Type" content="text/html; charset=utf-8" style="color: #212121; font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px;"><meta name="viewport" content="width=device-width" style="color: #212121; font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px;"></head><body style="color: #212121; font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px;"><p style="color: #212121; font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px;">
    @php
        $themeBgColor = $merchant['brand_color'];
        $themeFontColor = $merchant['brand_text_color'];
        $themed = 'background-color: ' . $themeBgColor . '; color: ' . $merchant['brand_text_color'] . ';';
        $is_test_mode = isset($is_test_mode) ?: false;
    @endphp
  
  </p>
    <center style="color: #212121; font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; max-width: 600px;">
        <table class="table" border="0" cellpadding="0" cellspacing="0" height="100%" width="100%" style="color: #212121; font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; width: 100%; background-color: #fafafa;"><tbody style="color: #212121; font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px;"><tr style="color: #212121; font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px;"><td class="first" style="color: {{ $merchant['brand_text_color'] }}; font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; padding: 24px 4%; padding-bottom: 0; border-left: 1px solid #f2f2f2; width: 3%; background-color: {{ $merchant['brand_color'] }};"></td>
                <td colspan="2" style="color: {{ $merchant['brand_text_color'] }}; font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; padding: 24px 0 !important; padding-bottom: 0; width: 92%; background-color: {{ $merchant['brand_color'] }}; border-left: 0; border-right: 0;">
                  @yield('header')
                </td>
                <td class="last" style="color: {{ $merchant['brand_text_color'] }}; font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; padding: 24px 4%; padding-bottom: 0; border-right: 1px solid #f2f2f2; width: 3%; background-color: {{ $merchant['brand_color'] }};"></td>
            </tr>

            @if ($is_test_mode)
            <tr style="color: #212121; font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px;"><td class="first" style="color: {{ $merchant['brand_text_color'] }}; font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; padding: 24px 4%; padding-bottom: 0; border-left: 1px solid #f2f2f2; width: 3%; background-color: {{ $merchant['brand_color'] }};"></td>
                <td colspan="2" class="content" style="color: #8a6d3b; font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; padding: 24px 4%; padding-bottom: 12px; background-color: #fcf8e3; border-left: 1px solid #f2f2f2; border-right: 1px solid #f2f2f2; width: 92%; border-top: 1px solid #f2f2f2; border-color: #faebcc; padding-top: 12px; font-size: 12px;">
                    This {{$invoice['type_label']}} is created in <b style="color: #8a6d3b; font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px;">Test Mode</b>. Only test payments can be made for this invoice.
                </td>
                <td class="last" style="color: {{ $merchant['brand_text_color'] }}; font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; padding: 24px 4%; padding-bottom: 0; border-right: 1px solid #f2f2f2; width: 3%; background-color: {{ $merchant['brand_color'] }};"></td>
            </tr>
            @endif


            @if ($invoice['description'])
            <tr style="color: #212121; font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px;">
                @if ($is_test_mode)
                    <td class="first" style="color: #212121; font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; padding: 24px 4%; padding-bottom: 0; border-left: 1px solid #f2f2f2; width: 3%;"></td>
                @else
                    <td class="first" style="color: {{ $merchant['brand_text_color'] }}; font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; padding: 24px 4%; padding-bottom: 0; border-left: 1px solid #f2f2f2; width: 3%; background-color: {{ $merchant['brand_color'] }};"></td>
                @endif
                <td colspan="2" class="content" style="color: #212121; font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; padding: 24px 4%; padding-bottom: 0; background-color: #fff; border-left: 1px solid #f2f2f2; border-right: 1px solid #f2f2f2; width: 92%; border-top: 1px solid #f2f2f2;">
                    <div style="color: #212121; font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px;">
                        <label style="color: #757575; font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; font-size: 12px; font-weight: bold; text-transform: uppercase;">{{ $invoice['type_label'] }} SUMMARY</label>
                        <div style="color: #212121; font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px;">{{$invoice['description']}}</div>
                    </div>
                </td>

                @if ($is_test_mode)
                    <td class="last" style="color: #212121; font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; padding: 24px 4%; padding-bottom: 0; border-right: 1px solid #f2f2f2; width: 3%;"></td>
                @else
                    <td class="last" style="color: {{ $merchant['brand_text_color'] }}; font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; padding: 24px 4%; padding-bottom: 0; border-right: 1px solid #f2f2f2; width: 3%; background-color: {{ $merchant['brand_color'] }};"></td>
                @endif
            </tr>
            @endif

            <tr style="color: #212121; font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px;">
                @if ($invoice['description'] || $is_test_mode)
                    <td class="first" style="color: #212121; font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; padding: 24px 4%; padding-bottom: 0; border-left: 1px solid #f2f2f2; width: 3%;"></td>
                @else
                    <td class="first" style="color: {{ $merchant['brand_text_color'] }}; font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; padding: 24px 4%; padding-bottom: 0; border-left: 1px solid #f2f2f2; width: 3%; background-color: {{ $merchant['brand_color'] }};"></td>
                @endif
                <td colspan="2" class="content" style="color: #212121; font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; padding: 24px 4%; padding-bottom: 0; background-color: #fff; border-left: 1px solid #f2f2f2; border-right: 1px solid #f2f2f2; width: 92%;">
                    <div style="color: #212121; font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px;">
                        <label style="color: #757575; font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; font-size: 12px; font-weight: bold; text-transform: uppercase;">BILLING TO</label>
                        <div style="color: #212121; font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px;">
                            @if ($invoice['customer_details']['customer_name'])
                                {{$invoice['customer_details']['customer_name']}}
                                @if ($invoice['customer_details']['customer_contact'])
                                    , {{$invoice['customer_details']['customer_contact']}}
                                @endif
                            @endif

                            <div style="color: #212121; font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px;">
                                {{$invoice['customer_details']['customer_email']}}
                            </div>
                        </div>
                    </div>
                </td>
                @if ($invoice['description'] || $is_test_mode)
                    <td class="last" style="color: #212121; font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; padding: 24px 4%; padding-bottom: 0; border-right: 1px solid #f2f2f2; width: 3%;"></td>
                @else
                    <td class="last" style="color: {{ $merchant['brand_text_color'] }}; font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; padding: 24px 4%; padding-bottom: 0; border-right: 1px solid #f2f2f2; width: 3%; background-color: {{ $merchant['brand_color'] }};"></td>
                @endif
            </tr>

            @if (isset($payment))
            <tr style="color: #212121; font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px;"><td class="first" style="color: #212121; font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; padding: 24px 4%; padding-bottom: 0; border-left: 1px solid #f2f2f2; width: 3%;"></td>
                <td colspan="2" class="content" style="color: #212121; font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; padding: 24px 4%; padding-bottom: 0; background-color: #fff; border-left: 1px solid #f2f2f2; border-right: 1px solid #f2f2f2; width: 92%;">
                    <label style="color: #757575; font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; font-size: 12px; font-weight: bold; text-transform: uppercase;">PAYMENT ID</label>
                    <div style="color: #212121; font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px;">{{ $payment['public_id'] }}</div>
                </td>
                <td class="last" style="color: #212121; font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; padding: 24px 4%; padding-bottom: 0; border-right: 1px solid #f2f2f2; width: 3%;"></td>
            </tr><tr style="color: #212121; font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px;"><td class="first" style="color: #212121; font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; padding: 24px 4%; padding-bottom: 0; border-left: 1px solid #f2f2f2; width: 3%;"></td>
                <td colspan="2" class="content" style="color: #212121; font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; padding: 24px 4%; padding-bottom: 24px; background-color: #fff; border-left: 1px solid #f2f2f2; border-right: 1px solid #f2f2f2; width: 92%;">
                    <label style="color: #757575; font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; font-size: 12px; font-weight: bold; text-transform: uppercase;">PAYMENT METHOD</label>
                    <div style="color: #212121; font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px;">{{ $payment['method'][0] }}, {{ $payment['method'][1] }}</div>
                </td>
                <td class="last" style="color: #212121; font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; padding: 24px 4%; padding-bottom: 0; border-right: 1px solid #f2f2f2; width: 3%;"></td>
            </tr>
            @elseif ($invoice['status'] === 'issued')
            <tr style="color: #212121; font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px;"><td class="first" style="color: #212121; font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; padding: 24px 4%; padding-bottom: 0; border-left: 1px solid #f2f2f2; width: 3%;"></td>
                <td colspan="2" class="content" style="color: #212121; font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; padding: 24px 4%; padding-bottom: 24px; background-color: #fff; border-left: 1px solid #f2f2f2; border-right: 1px solid #f2f2f2; width: 92%;">
                    <label style="color: #757575; font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; font-size: 12px; font-weight: bold; text-transform: uppercase;">{{ $invoice['type_label'] }} EXPIRY</label>
                    <div style="color: #212121; font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px;">{{ $invoice['expire_by_formatted'] }}</div>
                </td>
                <td class="last" style="color: #212121; font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; padding: 24px 4%; padding-bottom: 0; border-right: 1px solid #f2f2f2; width: 3%;"></td>
            </tr>
            @elseif ($invoice['status'] === 'expired')
            <tr style="color: #212121; font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px;"><td class="first" style="color: #212121; font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; padding: 24px 4%; padding-bottom: 0; border-left: 1px solid #f2f2f2; width: 3%;"></td>
                <td colspan="2" class="content" style="color: #212121; font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; padding: 24px 4%; padding-bottom: 24px; background-color: #fff; border-left: 1px solid #f2f2f2; border-right: 1px solid #f2f2f2; width: 92%;">
                    <label style="color: #757575; font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; font-size: 12px; font-weight: bold; text-transform: uppercase;">EXPIRED ON</label>
                    <div style="color: #212121; font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px;">{{ $invoice['expired_at_formatted'] }}</div>
                </td>
                <td class="last" style="color: #212121; font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; padding: 24px 4%; padding-bottom: 0; border-right: 1px solid #f2f2f2; width: 3%;"></td>
            </tr>
            @endif

            <tr style="color: #212121; font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px;"><td class="first" style="color: #212121; font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; padding: 24px 4%; padding-bottom: 0; border-left: 1px solid #f2f2f2; width: 3%;"></td>
                <td colspan="2" class="content footer" style="color: #212121; font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; border-bottom: 1px solid #f2f2f2; border-top: 1px dashed #e5e5e5; padding-bottom: 0; padding: 24px 4%; background-color: #fff; border-left: 1px solid #f2f2f2; border-right: 1px solid #f2f2f2; width: 92%; padding-left: 0; padding-right: 0; padding-top: 0;">
                    <table class="table" border="0" cellpadding="0" cellspacing="0" height="100%" width="100%" style="color: #212121; font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; width: 100%; background-color: #fafafa;"><tbody style="color: #212121; font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px;"><tr style="color: #212121; font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px;"><td style="color: #212121; font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; padding: 24px 4%; padding-bottom: 0; background-color: #fff;">
                                    <label style="color: #757575; font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; font-size: 12px; font-weight: bold; text-transform: uppercase;">AMOUNT</label>
                                    <div style="color: #212121; font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; font-weight: bold; font-size: 18px;">
                                        {{$invoice['currency']}} {{$invoice['amount_formatted']}}
                                    </div>
                                </td>
                                <td class="text-right" style="color: #212121; font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; text-align: right; padding: 24px 4%; padding-bottom: 0; background-color: #fff;">
                                    @yield("footerCTA")
                                </td>
                            </tr></tbody></table></td>
                <td class="last" style="color: #212121; font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; padding: 24px 4%; padding-bottom: 0; border-right: 1px solid #f2f2f2; width: 3%;"></td>
            </tr><tr style="color: #212121; font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px;"><td class="first" style="color: #212121; font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; padding: 24px 4%; padding-bottom: 0; border-left: 1px solid #f2f2f2; width: 3%;"></td>
                <td colspan="2" class="text-center" style="color: #212121; font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; text-align: center; padding: 24px 4%; padding-bottom: 24px; width: 92%;">
                    <div class="footerFerchant" style="color: #757575; font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; font-size: 12px; font-weight: bold;">
                        {{$merchant['name']}}
                    </div>
                    <div class="footerFerchant__address" style="color: #757575; font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; font-size: 10px;">
                        {{ $merchant['business_registered_address'] }}
                    </div>
                </td>
                <td class="last" style="color: #212121; font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; padding: 24px 4%; padding-bottom: 0; border-right: 1px solid #f2f2f2; width: 3%;"></td>
            </tr><tr style="color: #212121; font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px;"><td class="first" style="color: #212121; font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; padding: 24px 4%; padding-bottom: 0; border-left: 1px solid #f2f2f2; width: 3%;"></td>
                <td colspan="2" class="content" style="color: #212121; font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; padding: 24px 4%; padding-bottom: 0; background-color: #fff; border-left: 1px solid #f2f2f2; border-right: 1px solid #f2f2f2; width: 92%; vertical-align: top; border-bottom: 1px solid #f2f2f2; border-top: 1px solid #f2f2f2;">
                    <table border="0" cellpadding="0" cellspacing="0" height="100%" width="100%" style="color: #212121; font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; width: 100%;"><tbody style="color: #212121; font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px;"><tr style="color: #212121; font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px;"><td class="content" style="color: #212121; font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; vertical-align: top;">
                                    <a href="https://razorpay.com/" target="_blank" style="color: #212121; font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; height: 24px;">
                                        <img style="color: #212121; font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; height: 24px;" src="https://razorpay.com/images/logo-black.png"></a>
                                </td>
                                <td class="content" style="color: #212121; font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px;">
                                    <div class="footerRZP" style="color: #757575; font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; font-size: 10px; padding-bottom: 24px; padding-left: 10%; text-align: right;">
                                        Sign up at <a href="https://razorpay.com/" target="_blank" style="color: #212121; font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px;">razorpay.com/invoices</a> to create invoices and accept payments for your business.
                                    </div>
                                </td>
                            </tr></tbody></table></td>
                <td class="last" style="color: #212121; font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; padding: 24px 4%; padding-bottom: 0; border-right: 1px solid #f2f2f2; width: 3%;"></td>
            </tr><tr style="color: #212121; font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px;"><td class="first" style="color: #212121; font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; padding: 24px 4%; padding-bottom: 0; border-left: 1px solid #f2f2f2; width: 3%;"></td>
                <td colspan="2" style="color: #212121; font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; padding: 24px 4%; padding-bottom: 0; width: 92%;"></td>
                <td class="last" style="color: #212121; font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; padding: 24px 4%; padding-bottom: 0; border-right: 1px solid #f2f2f2; width: 3%;"></td>
            </tr></tbody></table></center>
  
</body></html>
