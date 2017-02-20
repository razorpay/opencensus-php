<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" style="color: rgba(0,0,0,0.87); font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; white-space: normal;"><head style="color: rgba(0,0,0,0.87); font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; white-space: normal;"><meta http-equiv="Content-Type" content="text/html; charset=utf-8" style="color: rgba(0,0,0,0.87); font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; white-space: normal;"><meta name="viewport" content="width=device-width" style="color: rgba(0,0,0,0.87); font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; white-space: normal;"></head><body style="color: rgba(0,0,0,0.87); font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; white-space: normal;"><p style="color: rgba(0,0,0,0.87); font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; white-space: normal;">
    @php
        $themeBgColor = $merchant['brand_color'];
        $themeFontColor = $merchant['brand_text_color'];
        $themed = 'background-color: ' . $themeBgColor . '; color: ' . $merchant['brand_text_color'] . ';';

        $ctaLabel = '';
        $ctaHref = '';
        $status = $invoice['status'];

        if (isset($payment))
        {
            $ctaLabel = 'DOWNLOAD PDF';
            $ctaHref = $invoice['pdf_url'];
        }
        elseif ($status === 'issued')
        {
            $ctaLabel = 'PROCEED TO PAY';
            $ctaHref = $invoice['short_url'];
        }
    @endphp
  
  </p>
    <table class="table" border="0" cellpadding="0" cellspacing="0" height="100%" width="100%" style="color: rgba(0,0,0,0.87); font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; white-space: normal; table-layout: fixed; width: 100%; background-color: #fafafa; max-width: 600px;"><tbody style="color: rgba(0,0,0,0.87); font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; white-space: normal;"><tr style="color: rgba(0,0,0,0.87); font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; white-space: normal;"><td class="first" style="color: {{ $merchant['brand_text_color'] }}; font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; white-space: normal; padding: 24px 4%; padding-bottom: 0; border-left: 1px solid rgba(0,0,0,0.05); width: 4%; background-color: {{ $merchant['brand_color'] }};"></td>
            <td colspan="2" style="color: {{ $merchant['brand_text_color'] }}; font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; white-space: normal; padding: 24px 0 !important; padding-bottom: 0; width: 92%; background-color: {{ $merchant['brand_color'] }}; border-left: 0; border-right: 0;">
              @yield('header')
            </td>
            <td class="last" style="color: {{ $merchant['brand_text_color'] }}; font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; white-space: normal; padding: 24px 4%; padding-bottom: 0; border-right: 1px solid rgba(0,0,0,0.05); width: 4%; background-color: {{ $merchant['brand_color'] }};"></td>
        </tr>

        @if ($invoice['description'])
        <tr style="color: rgba(0,0,0,0.87); font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; white-space: normal;"><td class="first" style="color: {{ $merchant['brand_text_color'] }}; font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; white-space: normal; padding: 24px 4%; padding-bottom: 0; border-left: 1px solid rgba(0,0,0,0.05); width: 4%; background-color: {{ $merchant['brand_color'] }};"></td>
            <td colspan="2" class="content" style="color: rgba(0,0,0,0.87); font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; white-space: normal; padding: 24px 4%; padding-bottom: 0; background-color: #fff; border-left: 1px solid rgba(0,0,0,0.05); border-right: 1px solid rgba(0,0,0,0.05); width: 92%; border-top: 1px solid rgba(0,0,0,0.05);">
                <div style="color: rgba(0,0,0,0.87); font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; white-space: normal;">
                    <label style="color: rgba(0, 0, 0, 0.54); font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; white-space: normal; font-size: 12px; font-weight: bold; text-transform: uppercase;">{{ $invoice['type_label'] }} SUMMARY</label>
                    <div style="color: rgba(0,0,0,0.87); font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; white-space: normal;">{{$invoice['description']}}</div>
                </div>
            </td>
            <td class="last" style="color: {{ $merchant['brand_text_color'] }}; font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; white-space: normal; padding: 24px 4%; padding-bottom: 0; border-right: 1px solid rgba(0,0,0,0.05); width: 4%; background-color: {{ $merchant['brand_color'] }};"></td>
        </tr>
        @endif

        <tr style="color: rgba(0,0,0,0.87); font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; white-space: normal;">
            @if ($invoice['description'])
                <td class="first" style="color: rgba(0,0,0,0.87); font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; white-space: normal; padding: 24px 4%; padding-bottom: 0; border-left: 1px solid rgba(0,0,0,0.05); width: 4%;"></td>
            @else
                <td class="first" style="color: {{ $merchant['brand_text_color'] }}; font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; white-space: normal; padding: 24px 4%; padding-bottom: 0; border-left: 1px solid rgba(0,0,0,0.05); width: 4%; background-color: {{ $merchant['brand_color'] }};"></td>
            @endif
            <td colspan="2" class="content" style="color: rgba(0,0,0,0.87); font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; white-space: normal; padding: 24px 4%; padding-bottom: 0; background-color: #fff; border-left: 1px solid rgba(0,0,0,0.05); border-right: 1px solid rgba(0,0,0,0.05); width: 92%;">
                <div style="color: rgba(0,0,0,0.87); font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; white-space: normal;">
                    <label style="color: rgba(0, 0, 0, 0.54); font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; white-space: normal; font-size: 12px; font-weight: bold; text-transform: uppercase;">BILLING TO</label>
                    <div style="color: rgba(0,0,0,0.87); font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; white-space: normal;">
                        @if ($invoice['customer_details']['customer_name'])
                            {{$invoice['customer_details']['customer_name']}}
                            @if ($invoice['customer_details']['customer_contact'])
                                , {{$invoice['customer_details']['customer_contact']}}
                            @endif
                        @endif

                        <div style="color: rgba(0,0,0,0.87); font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; white-space: normal;">
                            {{$invoice['customer_details']['customer_email']}}
                        </div>
                    </div>
                </div>
            </td>
            @if ($invoice['description'])
                <td class="last" style="color: rgba(0,0,0,0.87); font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; white-space: normal; padding: 24px 4%; padding-bottom: 0; border-right: 1px solid rgba(0,0,0,0.05); width: 4%;"></td>
            @else
                <td class="last" style="color: {{ $merchant['brand_text_color'] }}; font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; white-space: normal; padding: 24px 4%; padding-bottom: 0; border-right: 1px solid rgba(0,0,0,0.05); width: 4%; background-color: {{ $merchant['brand_color'] }};"></td>
            @endif
        </tr><tr style="color: rgba(0,0,0,0.87); font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; white-space: normal;"><td class="first" style="color: rgba(0,0,0,0.87); font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; white-space: normal; padding: 24px 4%; padding-bottom: 0; border-left: 1px solid rgba(0,0,0,0.05); width: 4%;"></td>
            <td colspan="2" class="content" style="color: rgba(0,0,0,0.87); font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; white-space: normal; padding: 24px 4%; padding-bottom: 24px; background-color: #fff; border-left: 1px solid rgba(0,0,0,0.05); border-right: 1px solid rgba(0,0,0,0.05); width: 92%;">
                    @if (isset($payment))
                        <div style="color: rgba(0,0,0,0.87); font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; white-space: normal;">
                            <label style="color: rgba(0, 0, 0, 0.54); font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; white-space: normal; font-size: 12px; font-weight: bold; text-transform: uppercase;">PAYMENT ID</label>
                            <div style="color: rgba(0,0,0,0.87); font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; white-space: normal;">{{ $payment['public_id'] }}</div>
                        </div>
                        <div style="color: rgba(0,0,0,0.87); font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; white-space: normal; margin-top: 24px;">
                            <label style="color: rgba(0, 0, 0, 0.54); font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; white-space: normal; font-size: 12px; font-weight: bold; text-transform: uppercase;">PAYMENT METHOD</label>
                            <div style="color: rgba(0,0,0,0.87); font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; white-space: normal;">{{ $payment['method'][0] }}, {{ $payment['method'][1] }}</div>
                        </div>
                    @elseif ($invoice['status'] === 'issued')
                        <div style="color: rgba(0,0,0,0.87); font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; white-space: normal;">
                            <label style="color: rgba(0, 0, 0, 0.54); font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; white-space: normal; font-size: 12px; font-weight: bold; text-transform: uppercase;">{{ $invoice['type_label'] }} EXPIRY</label>
                            <div style="color: rgba(0,0,0,0.87); font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; white-space: normal;">{{ $invoice['expire_by_formatted'] }}</div>
                        </div>
                    @elseif ($invoice['status'] === 'expired')
                        <div style="color: rgba(0,0,0,0.87); font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; white-space: normal;">
                            <label style="color: rgba(0, 0, 0, 0.54); font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; white-space: normal; font-size: 12px; font-weight: bold; text-transform: uppercase;">EXPIRED ON</label>
                            <div style="color: rgba(0,0,0,0.87); font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; white-space: normal;">{{ $invoice['expired_at_formatted'] }}</div>
                        </div>
                    @endif
                
            </td>
            <td class="last" style="color: rgba(0,0,0,0.87); font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; white-space: normal; padding: 24px 4%; padding-bottom: 0; border-right: 1px solid rgba(0,0,0,0.05); width: 4%;"></td>
        </tr><tr style="color: rgba(0,0,0,0.87); font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; white-space: normal;"><td class="first" style="color: rgba(0,0,0,0.87); font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; white-space: normal; padding: 24px 4%; padding-bottom: 0; border-left: 1px solid rgba(0,0,0,0.05); width: 4%;"></td>
            <td class="footer content" style="color: rgba(0,0,0,0.87); font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; white-space: normal; border-bottom: 1px solid rgba(0,0,0,0.05); border-top: 1px dashed rgba(0,0,0,0.10); padding-bottom: 0; padding: 24px 4%; background-color: #fff; border-left: 1px solid rgba(0,0,0,0.05); border-right: 0; width: 35%;">
                <label style="color: rgba(0, 0, 0, 0.54); font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; white-space: normal; font-size: 12px; font-weight: bold; text-transform: uppercase;">AMOUNT</label>
                <div style="color: rgba(0,0,0,0.87); font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; white-space: normal; font-weight: bold; font-size: 18px;">
                    {{$invoice['currency']}} {{$invoice['amount_formatted']}}
                </div>
            </td>
            <td class="footer content text-right" style="color: rgba(0,0,0,0.87); font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; white-space: normal; text-align: right; border-bottom: 1px solid rgba(0,0,0,0.05); border-top: 1px dashed rgba(0,0,0,0.10); padding-bottom: 0; padding: 24px 4%; background-color: #fff; border-left: 0; border-right: 1px solid rgba(0,0,0,0.05);">
                @yield("footerCTA")
            </td>
            <td class="last" style="color: rgba(0,0,0,0.87); font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; white-space: normal; padding: 24px 4%; padding-bottom: 0; border-right: 1px solid rgba(0,0,0,0.05); width: 4%;"></td>
        </tr><tr style="color: rgba(0,0,0,0.87); font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; white-space: normal;"><td class="first" style="color: rgba(0,0,0,0.87); font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; white-space: normal; padding: 24px 4%; padding-bottom: 0; border-left: 1px solid rgba(0,0,0,0.05); width: 4%;"></td>
            <td colspan="2" class="text-center" style="color: rgba(0,0,0,0.87); font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; white-space: normal; text-align: center; padding: 24px 4%; padding-bottom: 24px;">
                <div class="footerFerchant" style="color: rgba(0,0,0,0.54); font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; white-space: normal; font-size: 12px; font-weight: bold;">
                    {{$merchant['name']}}
                </div>
                <div class="footerFerchant__address" style="color: rgba(0,0,0,0.54); font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; white-space: normal; font-size: 10px;">
                    {{ $merchant['business_registered_address'] }}
                </div>
            </td>
            <td class="last" style="color: rgba(0,0,0,0.87); font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; white-space: normal; padding: 24px 4%; padding-bottom: 0; border-right: 1px solid rgba(0,0,0,0.05); width: 4%;"></td>
        </tr><tr style="color: rgba(0,0,0,0.87); font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; white-space: normal;"><td class="first" style="color: rgba(0,0,0,0.87); font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; white-space: normal; padding: 24px 4%; padding-bottom: 0; border-left: 1px solid rgba(0,0,0,0.05); width: 4%;"></td>
            <td class="content" style="color: rgba(0,0,0,0.87); font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; white-space: normal; padding: 24px 4%; padding-bottom: 0; background-color: #fff; border-left: 1px solid rgba(0,0,0,0.05); border-right: 0; vertical-align: top; border-bottom: 1px solid rgba(0,0,0,0.05); border-top: 1px solid rgba(0,0,0,0.05);">
                <a href="https://razorpay.com/" target="_blank" style="color: rgba(0,0,0,0.87); font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; white-space: normal;">
                    <img style="color: rgba(0,0,0,0.87); font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; white-space: normal; height: 24px;" src="https://razorpay.com/images/logo-black.png"></a>
            </td>
            <td class="content footerRZP" style="color: rgba(0,0,0,0.54); font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; white-space: normal; font-size: 10px; padding-bottom: 0; padding-left: 10%; text-align: right; padding: 24px 4%; background-color: #fff; border-left: 0; border-right: 1px solid rgba(0,0,0,0.05); border-bottom: 1px solid rgba(0,0,0,0.05); border-top: 1px solid rgba(0,0,0,0.05);">
                <div style="color: rgba(0,0,0,0.87); font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; white-space: normal;">
                    Sign up at <a href="https://razorpay.com/" target="_blank" style="color: rgba(0,0,0,0.87); font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; white-space: normal;">razorpay.com/invoices</a> to create invoices and accept payments for your business.
                </div>
            </td>
            <td class="last" style="color: rgba(0,0,0,0.87); font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; white-space: normal; padding: 24px 4%; padding-bottom: 0; border-right: 1px solid rgba(0,0,0,0.05); width: 4%;"></td>
        </tr><tr style="color: rgba(0,0,0,0.87); font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; white-space: normal;"><td class="first" style="color: rgba(0,0,0,0.87); font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; white-space: normal; padding: 24px 4%; padding-bottom: 0; border-left: 1px solid rgba(0,0,0,0.05); width: 4%;"></td>
            <td colspan="2" style="color: rgba(0,0,0,0.87); font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; white-space: normal; padding: 24px 4%; padding-bottom: 0;"></td>
            <td class="last" style="color: rgba(0,0,0,0.87); font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; white-space: normal; padding: 24px 4%; padding-bottom: 0; border-right: 1px solid rgba(0,0,0,0.05); width: 4%;"></td>
        </tr></tbody></table></body></html>
