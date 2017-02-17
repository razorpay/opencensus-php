<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" style="color: rgba(0,0,0,0.87); font-family: Arial, sans-serif; line-height: 20px;"><head style="color: rgba(0,0,0,0.87); font-family: Arial, sans-serif; line-height: 20px;"><meta http-equiv="Content-Type" content="text/html; charset=utf-8" style="color: rgba(0,0,0,0.87); font-family: Arial, sans-serif; line-height: 20px;"><meta name="viewport" content="width=device-width" style="color: rgba(0,0,0,0.87); font-family: Arial, sans-serif; line-height: 20px;"></head><body style="color: rgba(0,0,0,0.87); font-family: Arial, sans-serif; line-height: 20px;"><p style="color: rgba(0,0,0,0.87); font-family: Arial, sans-serif; line-height: 20px;">
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
        elseif ($status === 'expired')
        {
        }
    @endphp
  
  </p>
    <table class="table" border="0" cellpadding="0" cellspacing="0" height="100%" width="100%" style="color: rgba(0,0,0,0.87); font-family: Arial, sans-serif; line-height: 20px; background-color: #fafafa; max-width: 600px; table-layout: fixed;"><tbody style="color: rgba(0,0,0,0.87); font-family: Arial, sans-serif; line-height: 20px;"><tr style="color: rgba(0,0,0,0.87); font-family: Arial, sans-serif; line-height: 20px;"><td style="color: {{ $merchant['brand_text_color'] }}; font-family: Arial, sans-serif; line-height: 20px; padding: 24px 4%; padding-bottom: 0; border-left: 1px solid rgba(0,0,0,0.05); background-color: {{ $merchant['brand_color'] }};"></td>
            <td style="color: {{ $merchant['brand_text_color'] }}; font-family: Arial, sans-serif; line-height: 20px; padding: 24px 0 !important; padding-bottom: 0; border-left: 0; border-right: 0; width: 94%; background-color: {{ $merchant['brand_color'] }};">
              @yield('header')
            </td>
            <td style="color: {{ $merchant['brand_text_color'] }}; font-family: Arial, sans-serif; line-height: 20px; padding: 24px 4%; padding-bottom: 0; border-right: 1px solid rgba(0,0,0,0.05); background-color: {{ $merchant['brand_color'] }};"></td>
        </tr>

        @if ($invoice['description'])
        <tr style="color: rgba(0,0,0,0.87); font-family: Arial, sans-serif; line-height: 20px;"><td style="color: {{ $merchant['brand_text_color'] }}; font-family: Arial, sans-serif; line-height: 20px; padding: 24px 4%; padding-bottom: 0; border-left: 1px solid rgba(0,0,0,0.05); background-color: {{ $merchant['brand_color'] }};"></td>
            <td class="content" style="color: rgba(0,0,0,0.87); font-family: Arial, sans-serif; line-height: 20px; padding: 24px 4%; padding-bottom: 0; border-left: 1px solid rgba(0,0,0,0.05); border-right: 1px solid rgba(0,0,0,0.05); background-color: #fff; border-top: 1px solid rgba(0,0,0,0.05);">
                <div style="color: rgba(0,0,0,0.87); font-family: Arial, sans-serif; line-height: 20px;">
                    <label style="color: rgba(0, 0, 0, 0.54); font-family: Arial, sans-serif; line-height: 20px; font-size: 12px; font-weight: bold;">INVOICE SUMMARY</label>
                    <div style="color: rgba(0,0,0,0.87); font-family: Arial, sans-serif; line-height: 20px;">{{$invoice['description']}}</div>
                </div>
            </td>
            <td style="color: {{ $merchant['brand_text_color'] }}; font-family: Arial, sans-serif; line-height: 20px; padding: 24px 4%; padding-bottom: 0; border-right: 1px solid rgba(0,0,0,0.05); background-color: {{ $merchant['brand_color'] }};"></td>
        </tr>
        @endif

        <tr style="color: rgba(0,0,0,0.87); font-family: Arial, sans-serif; line-height: 20px;">
            @if ($invoice['description'])
                <td style="color: rgba(0,0,0,0.87); font-family: Arial, sans-serif; line-height: 20px; padding: 24px 4%; padding-bottom: 0; border-left: 1px solid rgba(0,0,0,0.05);"></td>
            @else
              <td style="color: {{ $merchant['brand_text_color'] }}; font-family: Arial, sans-serif; line-height: 20px; padding: 24px 4%; padding-bottom: 0; border-left: 1px solid rgba(0,0,0,0.05); border-right: 1px solid rgba(0,0,0,0.05); background-color: {{ $merchant['brand_color'] }};"></td>
            @endif
            <td class="content" style="color: rgba(0,0,0,0.87); font-family: Arial, sans-serif; line-height: 20px; padding: 24px 4%; padding-bottom: 0; background-color: #fff;">
                <div style="color: rgba(0,0,0,0.87); font-family: Arial, sans-serif; line-height: 20px;">
                    <label style="color: rgba(0, 0, 0, 0.54); font-family: Arial, sans-serif; line-height: 20px; font-size: 12px; font-weight: bold;">BILLING TO</label>
                    <div style="color: rgba(0,0,0,0.87); font-family: Arial, sans-serif; line-height: 20px;">
                        @if ($invoice['customer_details']['customer_name'])
                            {{$invoice['customer_details']['customer_name']}}
                            @if ($invoice['customer_details']['customer_contact'])
                                , {{$invoice['customer_details']['customer_contact']}}
                            @endif
                        @endif

                        <div style="color: rgba(0,0,0,0.87); font-family: Arial, sans-serif; line-height: 20px;">
                            {{$invoice['customer_details']['customer_email']}}
                        </div>
                    </div>
                </div>
            </td>
            @if ($invoice['description'])
                <td style="color: rgba(0,0,0,0.87); font-family: Arial, sans-serif; line-height: 20px; padding: 24px 4%; padding-bottom: 0;"></td>
            @else
              <td style="color: {{ $merchant['brand_text_color'] }}; font-family: Arial, sans-serif; line-height: 20px; padding: 24px 4%; padding-bottom: 0; border-right: 1px solid rgba(0,0,0,0.05); background-color: {{ $merchant['brand_color'] }};"></td>
            @endif
        </tr><tr style="color: rgba(0,0,0,0.87); font-family: Arial, sans-serif; line-height: 20px;"><td style="color: rgba(0,0,0,0.87); font-family: Arial, sans-serif; line-height: 20px; padding: 24px 4%; padding-bottom: 0; border-left: 1px solid rgba(0,0,0,0.05);"></td>
            <td class="content" style="color: rgba(0,0,0,0.87); font-family: Arial, sans-serif; line-height: 20px; padding: 24px 4%; padding-bottom: 24px; border-left: 1px solid rgba(0,0,0,0.05); border-right: 1px solid rgba(0,0,0,0.05); background-color: #fff;">
                    @if (isset($payment))
                        <div style="color: rgba(0,0,0,0.87); font-family: Arial, sans-serif; line-height: 20px;">
                            <label style="color: rgba(0, 0, 0, 0.54); font-family: Arial, sans-serif; line-height: 20px; font-size: 12px; font-weight: bold;">PAYMENT ID</label>
                            <div style="color: rgba(0,0,0,0.87); font-family: Arial, sans-serif; line-height: 20px;">{{ $payment['public_id'] }}</div>
                        </div>
                        <div style="color: rgba(0,0,0,0.87); font-family: Arial, sans-serif; line-height: 20px; margin-top: 24px;">
                            <label style="color: rgba(0, 0, 0, 0.54); font-family: Arial, sans-serif; line-height: 20px; font-size: 12px; font-weight: bold;">PAYMENT METHOD</label>
                            <div style="color: rgba(0,0,0,0.87); font-family: Arial, sans-serif; line-height: 20px;">{{ $payment['method'][0] }}, {{ $payment['method'][1] }}</div>
                        </div>
                    @elseif ($invoice['status'] === 'issued')
                        <div style="color: rgba(0,0,0,0.87); font-family: Arial, sans-serif; line-height: 20px;">
                            <label style="color: rgba(0, 0, 0, 0.54); font-family: Arial, sans-serif; line-height: 20px; font-size: 12px; font-weight: bold;">INVOICE EXPIRY</label>
                            <div style="color: rgba(0,0,0,0.87); font-family: Arial, sans-serif; line-height: 20px;">{{ $invoice['expire_by_formatted'] }}</div>
                        </div>
                    @elseif ($invoice['status'] === 'expired')
                        <div style="color: rgba(0,0,0,0.87); font-family: Arial, sans-serif; line-height: 20px;">
                            <label style="color: rgba(0, 0, 0, 0.54); font-family: Arial, sans-serif; line-height: 20px; font-size: 12px; font-weight: bold;">EXPIRED ON</label>
                            <div style="color: rgba(0,0,0,0.87); font-family: Arial, sans-serif; line-height: 20px;">{{ $invoice['expired_at_formatted'] }}</div>
                        </div>
                    @endif
                
            </td>
            <td style="color: rgba(0,0,0,0.87); font-family: Arial, sans-serif; line-height: 20px; padding: 24px 4%; padding-bottom: 0; border-right: 1px solid rgba(0,0,0,0.05);"></td>
        </tr><tr style="color: rgba(0,0,0,0.87); font-family: Arial, sans-serif; line-height: 20px;"><td style="color: rgba(0,0,0,0.87); font-family: Arial, sans-serif; line-height: 20px; padding: 24px 4%; padding-bottom: 0; border-left: 1px solid rgba(0,0,0,0.05);"></td>
            <td class="footer content" style="color: rgba(0,0,0,0.87); font-family: Arial, sans-serif; line-height: 20px; border-bottom: 1px solid rgba(0,0,0,0.05); border-top: 1px dashed rgba(0,0,0,0.10); padding-bottom: 0; padding: 24px 4%; border-left: 1px solid rgba(0,0,0,0.05); border-right: 1px solid rgba(0,0,0,0.05); background-color: #fff; width: 94%;">
                <table border="0" cellpadding="0" cellspacing="0" width="100%" style="color: rgba(0,0,0,0.87); font-family: Arial, sans-serif; line-height: 20px;"><tbody style="color: rgba(0,0,0,0.87); font-family: Arial, sans-serif; line-height: 20px;"><tr style="color: rgba(0,0,0,0.87); font-family: Arial, sans-serif; line-height: 20px;"><td style="color: rgba(0,0,0,0.87); font-family: Arial, sans-serif; line-height: 20px;">
                                <label style="color: rgba(0, 0, 0, 0.54); font-family: Arial, sans-serif; line-height: 20px; font-size: 12px; font-weight: bold;">AMOUNT</label>
                                <div style="color: rgba(0,0,0,0.87); font-family: Arial, sans-serif; line-height: 20px; font-weight: bold; font-size: 18px;">
                                    {{$invoice['currency']}} {{$invoice['amount_formatted']}}
                                </div>
                            </td>
                            <td class="text-right" style="color: rgba(0,0,0,0.87); font-family: Arial, sans-serif; line-height: 20px; text-align: right;">
                                @yield("footerCTA")
                            </td>
                        </tr></tbody></table></td>
            <td style="color: rgba(0,0,0,0.87); font-family: Arial, sans-serif; line-height: 20px; padding: 24px 4%; padding-bottom: 0; border-right: 1px solid rgba(0,0,0,0.05);"></td>
        </tr><tr style="color: rgba(0,0,0,0.87); font-family: Arial, sans-serif; line-height: 20px;"><td style="color: rgba(0,0,0,0.87); font-family: Arial, sans-serif; line-height: 20px; padding: 24px 4%; padding-bottom: 0; border-left: 1px solid rgba(0,0,0,0.05);"></td>
            <td class="text-center" style="color: rgba(0,0,0,0.87); font-family: Arial, sans-serif; line-height: 20px; text-align: center; padding: 24px 4%; padding-bottom: 24px; border-left: 1px solid rgba(0,0,0,0.05); border-right: 1px solid rgba(0,0,0,0.05); background-color: #fafafa; border: 0;">
                <div class="footerFerchant" style="color: rgba(0,0,0,0.54); font-family: Arial, sans-serif; line-height: 20px; font-size: 12px; font-weight: bold;">
                    {{$merchant['name']}}
                </div>
                <div class="footerFerchant__address" style="color: rgba(0,0,0,0.54); font-family: Arial, sans-serif; line-height: 20px; font-size: 10px;">
                    {{ $merchant['business_registered_address'] }}
                </div>
            </td>
            <td style="color: rgba(0,0,0,0.87); font-family: Arial, sans-serif; line-height: 20px; padding: 24px 4%; padding-bottom: 0; border-right: 1px solid rgba(0,0,0,0.05);"></td>
        </tr><tr style="color: rgba(0,0,0,0.87); font-family: Arial, sans-serif; line-height: 20px;"><td style="color: rgba(0,0,0,0.87); font-family: Arial, sans-serif; line-height: 20px; padding: 24px 4%; padding-bottom: 0; border-left: 1px solid rgba(0,0,0,0.05);"></td>
            <td class="content" style="color: rgba(0,0,0,0.87); font-family: Arial, sans-serif; line-height: 20px; padding: 24px 4%; padding-bottom: 0; border-left: 1px solid rgba(0,0,0,0.05); border-right: 1px solid rgba(0,0,0,0.05); background-color: #fff; border-bottom: 1px solid rgba(0,0,0,0.05); border-top: 1px solid rgba(0,0,0,0.05);">
                <table border="0" cellpadding="0" cellspacing="0" width="100%" style="color: rgba(0,0,0,0.87); font-family: Arial, sans-serif; line-height: 20px;"><tbody style="color: rgba(0,0,0,0.87); font-family: Arial, sans-serif; line-height: 20px;"><tr style="color: rgba(0,0,0,0.87); font-family: Arial, sans-serif; line-height: 20px;"><td style="color: rgba(0,0,0,0.87); font-family: Arial, sans-serif; line-height: 20px; vertical-align: top;">
                                <a href="https://razorpay.com/" target="_blank" style="color: rgba(0,0,0,0.87); font-family: Arial, sans-serif; line-height: 20px;">
                                    <img style="color: rgba(0,0,0,0.87); font-family: Arial, sans-serif; line-height: 20px; height: 24px;" src="https://razorpay.com/images/logo-black.png"></a>
                            </td>
                            <td class="footerRZP" style="color: rgba(0,0,0,0.54); font-family: Arial, sans-serif; line-height: 20px; font-size: 10px; padding-bottom: 24px; padding-left: 10%; text-align: right;">
                                <div style="color: rgba(0,0,0,0.87); font-family: Arial, sans-serif; line-height: 20px;">
                                    Sign up at <a href="https://razorpay.com/" target="_blank" style="color: rgba(0,0,0,0.87); font-family: Arial, sans-serif; line-height: 20px;">razorpay.com/invoices</a> to create invoices and accept payments for your business.
                                </div>
                            </td>
                        </tr></tbody></table></td>
            <td style="color: rgba(0,0,0,0.87); font-family: Arial, sans-serif; line-height: 20px; padding: 24px 4%; padding-bottom: 0; border-right: 1px solid rgba(0,0,0,0.05);"></td>
        </tr><tr style="color: rgba(0,0,0,0.87); font-family: Arial, sans-serif; line-height: 20px;"><td colspan="3" style="color: rgba(0,0,0,0.87); font-family: Arial, sans-serif; line-height: 20px; padding: 24px 4%; padding-bottom: 0; border-left: 1px solid rgba(0,0,0,0.05); border-right: 1px solid rgba(0,0,0,0.05);"></td>
        </tr></tbody></table></body></html>
