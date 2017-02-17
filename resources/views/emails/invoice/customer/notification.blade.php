<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" style="font-family: Verdana, Arial, sans-serif; line-height: 20px;"><head style="font-family: Verdana, Arial, sans-serif; line-height: 20px;"><meta http-equiv="Content-Type" content="text/html; charset=utf-8" style="font-family: Verdana, Arial, sans-serif; line-height: 20px;"><meta name="viewport" content="width=device-width" style="font-family: Verdana, Arial, sans-serif; line-height: 20px;"></head><body style="font-family: Verdana, Arial, sans-serif; line-height: 20px;"><p style="font-family: Verdana, Arial, sans-serif; line-height: 20px;">
    @php
        $themeBgColor = $merchant['brand_color'];
        $themeFontColor = $merchant['brand_text_color'];
        $themed = 'background-color: ' . $themeBgColor . '; color: ' . $merchant['brand_text_color'] . ';';

        $ctaLabel = '';
        $ctaHref = '';
        $headerLabel = '';
        $status = $invoice['status'];

        if (isset($payment))
        {
            $ctaLabel = 'DOWNLOAD PDF';
            $ctaHref = $invoice['pdf_url'];
            $headerLabel = 'PAYMENT SUCCESSFUL';
        }
        elseif ($status === 'issued')
        {
            $ctaLabel = 'PROCEED TO PAY';
            $ctaHref = $invoice['short_url'];
            $headerLabel = $merchant['name'] . ' has sent you an invoice for ' . $invoice['currency'] . ' ' . $invoice['amount_formatted'];
        }
        elseif ($status === 'expired')
        {
            $headerLabel = 'INVOICE EXPIRED';
        }
    @endphp
  
  </p>
    <table class="table" border="0" cellpadding="0" cellspacing="0" height="100%" width="100%" style="font-family: Verdana, Arial, sans-serif; line-height: 20px; background-color: #fafafa; color: rgba(0,0,0,0.87); max-width: 600px; table-layout: fixed;"><tbody style="font-family: Verdana, Arial, sans-serif; line-height: 20px;"><tr style="font-family: Verdana, Arial, sans-serif; line-height: 20px;"><td style="font-family: Verdana, Arial, sans-serif; line-height: 20px; padding: 24px 4%; padding-bottom: 0; border-left: 1px solid rgba(0,0,0,0.05); background-color: {{ $merchant['brand_color'] }}; color: {{ $merchant['brand_text_color'] }};"></td>
            <td style="font-family: Verdana, Arial, sans-serif; line-height: 20px; padding: 24px 0 !important; padding-bottom: 0; border-left: 0; border-right: 0; width: 94%; background-color: {{ $merchant['brand_color'] }}; color: {{ $merchant['brand_text_color'] }};">
              <div class="text-center" style="font-family: Verdana, Arial, sans-serif; line-height: 20px; text-align: center;">
                  @if ($merchant['image'])
                    <img class="merchant__logo" src="{{ $merchant['image'] }}" style="font-family: Verdana, Arial, sans-serif; line-height: 20px; height: 48px; margin-bottom: 8px; width: 48px;">
                  @endif

                   <h2 style="font-family: Verdana, Arial, sans-serif; line-height: 24px; font-size: 20px; margin: 0; color: {{ $merchant['brand_text_color'] }};">
                      Invoice from {{$merchant['name']}}
                  </h2>
                  <div style="font-family: Verdana, Arial, sans-serif; line-height: 20px; color: {{ $merchant['brand_text_color'] }};">
                      @if ($invoice['receipt'])
                          Invoice Receipt: {{$invoice['receipt']}}
                      @else
                          Invoice Id: {{$invoice['id']}}
                      @endif
                  </div>

                   <div style="font-family: Verdana, Arial, sans-serif; line-height: 20px; margin-top: 12px; color: {{ $merchant['brand_text_color'] }};">
                      <div style="font-family: Verdana, Arial, sans-serif; line-height: 20px; color: {{ $merchant['brand_text_color'] }};">
                          {{ $headerLabel }}
                      </div>

                      @unless ($status === 'expired')
                        <div style="font-family: Verdana, Arial, sans-serif; line-height: 20px; color: {{ $merchant['brand_text_color'] }};">
                            The invoice for the same is attached in this mail.
                        </div>
                      @endunless
                   </div>
              </div>
            </td>
            <td style="font-family: Verdana, Arial, sans-serif; line-height: 20px; padding: 24px 4%; padding-bottom: 0; border-right: 1px solid rgba(0,0,0,0.05); background-color: {{ $merchant['brand_color'] }}; color: {{ $merchant['brand_text_color'] }};"></td>
        </tr>

        @if ($invoice['description'])
        <tr style="font-family: Verdana, Arial, sans-serif; line-height: 20px;"><td style="font-family: Verdana, Arial, sans-serif; line-height: 20px; padding: 24px 4%; padding-bottom: 0; border-left: 1px solid rgba(0,0,0,0.05); background-color: {{ $merchant['brand_color'] }}; color: {{ $merchant['brand_text_color'] }};"></td>
            <td class="content" style="font-family: Verdana, Arial, sans-serif; line-height: 20px; padding: 24px 4%; padding-bottom: 0; border-left: 1px solid rgba(0,0,0,0.05); border-right: 1px solid rgba(0,0,0,0.05); background-color: #fff; border-top: 1px solid rgba(0,0,0,0.05);">
                <div style="font-family: Verdana, Arial, sans-serif; line-height: 20px;">
                    <label style="font-family: Verdana, Arial, sans-serif; line-height: 20px; color: rgba(0, 0, 0, 0.54); font-size: 12px; font-weight: bold;">INVOICE SUMMARY</label>
                    <div style="font-family: Verdana, Arial, sans-serif; line-height: 20px;">{{$invoice['description']}}</div>
                </div>
            </td>
            <td style="font-family: Verdana, Arial, sans-serif; line-height: 20px; padding: 24px 4%; padding-bottom: 0; border-right: 1px solid rgba(0,0,0,0.05); background-color: {{ $merchant['brand_color'] }}; color: {{ $merchant['brand_text_color'] }};"></td>
        </tr>
        @endif

        <tr style="font-family: Verdana, Arial, sans-serif; line-height: 20px;">
            @if ($invoice['description'])
                <td style="font-family: Verdana, Arial, sans-serif; line-height: 20px; padding: 24px 4%; padding-bottom: 0; border-left: 1px solid rgba(0,0,0,0.05);"></td>
            @else
              <td style="font-family: Verdana, Arial, sans-serif; line-height: 20px; padding: 24px 4%; padding-bottom: 0; border-left: 1px solid rgba(0,0,0,0.05); border-right: 1px solid rgba(0,0,0,0.05); background-color: {{ $merchant['brand_color'] }}; color: {{ $merchant['brand_text_color'] }};"></td>
            @endif
            <td class="content" style="font-family: Verdana, Arial, sans-serif; line-height: 20px; padding: 24px 4%; padding-bottom: 0; background-color: #fff;">
                <div style="font-family: Verdana, Arial, sans-serif; line-height: 20px;">
                    <label style="font-family: Verdana, Arial, sans-serif; line-height: 20px; color: rgba(0, 0, 0, 0.54); font-size: 12px; font-weight: bold;">BILLING TO</label>
                    <div style="font-family: Verdana, Arial, sans-serif; line-height: 20px;">
                        @if ($invoice['customer_details']['customer_name'])
                            {{$invoice['customer_details']['customer_name']}}
                            @if ($invoice['customer_details']['customer_contact'])
                                , {{$invoice['customer_details']['customer_contact']}}
                            @endif
                        @endif

                        <div style="font-family: Verdana, Arial, sans-serif; line-height: 20px;">
                            {{$invoice['customer_details']['customer_email']}}
                        </div>
                    </div>
                </div>
            </td>
            @if ($invoice['description'])
                <td style="font-family: Verdana, Arial, sans-serif; line-height: 20px; padding: 24px 4%; padding-bottom: 0;"></td>
            @else
              <td style="font-family: Verdana, Arial, sans-serif; line-height: 20px; padding: 24px 4%; padding-bottom: 0; border-right: 1px solid rgba(0,0,0,0.05); background-color: {{ $merchant['brand_color'] }}; color: {{ $merchant['brand_text_color'] }};"></td>
            @endif
        </tr><tr style="font-family: Verdana, Arial, sans-serif; line-height: 20px;"><td style="font-family: Verdana, Arial, sans-serif; line-height: 20px; padding: 24px 4%; padding-bottom: 0; border-left: 1px solid rgba(0,0,0,0.05);"></td>
            <td class="content" style="font-family: Verdana, Arial, sans-serif; line-height: 20px; padding: 24px 4%; padding-bottom: 24px; border-left: 1px solid rgba(0,0,0,0.05); border-right: 1px solid rgba(0,0,0,0.05); background-color: #fff;">
                <div style="font-family: Verdana, Arial, sans-serif; line-height: 20px;">
                    @if (isset($payment))
                        <label style="font-family: Verdana, Arial, sans-serif; line-height: 20px; color: rgba(0, 0, 0, 0.54); font-size: 12px; font-weight: bold;">PAYMENT ID</label>
                        <div style="font-family: Verdana, Arial, sans-serif; line-height: 20px;">{{ $payment['public_id'] }}</div>
                    @elseif ($invoice['status'] === 'issued')
                        <label style="font-family: Verdana, Arial, sans-serif; line-height: 20px; color: rgba(0, 0, 0, 0.54); font-size: 12px; font-weight: bold;">INVOICE EXPIRY</label>
                        <div style="font-family: Verdana, Arial, sans-serif; line-height: 20px;">{{ $invoice['date_formatted'] }}</div>
                    @elseif ($invoice['status'] === 'expired')
                        <label style="font-family: Verdana, Arial, sans-serif; line-height: 20px; color: rgba(0, 0, 0, 0.54); font-size: 12px; font-weight: bold;">EXPIRED ON</label>
                        <div style="font-family: Verdana, Arial, sans-serif; line-height: 20px;">{{ $invoice['date_formatted'] }}</div>
                    @endif
                </div>
            </td>
            <td style="font-family: Verdana, Arial, sans-serif; line-height: 20px; padding: 24px 4%; padding-bottom: 0; border-right: 1px solid rgba(0,0,0,0.05);"></td>
        </tr><tr style="font-family: Verdana, Arial, sans-serif; line-height: 20px;"><td style="font-family: Verdana, Arial, sans-serif; line-height: 20px; padding: 24px 4%; padding-bottom: 0; border-left: 1px solid rgba(0,0,0,0.05);"></td>
            <td class="footer content" style="font-family: Verdana, Arial, sans-serif; line-height: 20px; border-bottom: 1px solid rgba(0,0,0,0.05); border-top: 1px dashed rgba(0,0,0,0.10); padding-bottom: 0; padding: 24px 4%; border-left: 1px solid rgba(0,0,0,0.05); border-right: 1px solid rgba(0,0,0,0.05); background-color: #fff; width: 94%;">
                <table border="0" cellpadding="0" cellspacing="0" width="100%" style="font-family: Verdana, Arial, sans-serif; line-height: 20px;"><tbody style="font-family: Verdana, Arial, sans-serif; line-height: 20px;"><tr style="font-family: Verdana, Arial, sans-serif; line-height: 20px;"><td style="font-family: Verdana, Arial, sans-serif; line-height: 20px;">
                                <label style="font-family: Verdana, Arial, sans-serif; line-height: 20px; color: rgba(0, 0, 0, 0.54); font-size: 12px; font-weight: bold;">AMOUNT</label>
                                <div style="font-family: Verdana, Arial, sans-serif; line-height: 20px; font-weight: bold; font-size: 18px;">
                                    {{$invoice['currency']}} {{$invoice['amount_formatted']}}
                                </div>
                            </td>
                            <td class="text-right" style="font-family: Verdana, Arial, sans-serif; line-height: 20px; text-align: right;">
                              @if ($ctaLabel)
                                <a class="footer--cta" href="{{ $ctaHref }}" target="_blank" style="font-family: Verdana, Arial, sans-serif; line-height: 20px; border-radius: 5px; cursor: pointer; display: inline-block; padding: 7px 20px; text-decoration: none; white-space: nowrap; color: {{ $themeFontColor }}; background-color: {{ $themeBgColor }}; border: 1px solid {{ $themeBgColor }};">
                                  {{ $ctaLabel }}
                                </a>
                              @endif
                            </td>
                        </tr></tbody></table></td>
            <td style="font-family: Verdana, Arial, sans-serif; line-height: 20px; padding: 24px 4%; padding-bottom: 0; border-right: 1px solid rgba(0,0,0,0.05);"></td>
        </tr><tr style="font-family: Verdana, Arial, sans-serif; line-height: 20px;"><td style="font-family: Verdana, Arial, sans-serif; line-height: 20px; padding: 24px 4%; padding-bottom: 0; border-left: 1px solid rgba(0,0,0,0.05);"></td>
            <td class="text-center" style="font-family: Verdana, Arial, sans-serif; line-height: 20px; text-align: center; padding: 24px 4%; padding-bottom: 24px; border-left: 1px solid rgba(0,0,0,0.05); border-right: 1px solid rgba(0,0,0,0.05); background-color: #fafafa; border: 0;">
                <div class="footerFerchant" style="font-family: Verdana, Arial, sans-serif; line-height: 20px; color: rgba(0,0,0,0.54); font-size: 12px; font-weight: bold;">
                    {{$merchant['name']}}
                </div>
                <div class="footerFerchant__address" style="font-family: Verdana, Arial, sans-serif; line-height: 20px; color: rgba(0,0,0,0.54); font-size: 10px;">
                    {{ $merchant['business_registered_address'] }}
                </div>
            </td>
            <td style="font-family: Verdana, Arial, sans-serif; line-height: 20px; padding: 24px 4%; padding-bottom: 0; border-right: 1px solid rgba(0,0,0,0.05);"></td>
        </tr><tr style="font-family: Verdana, Arial, sans-serif; line-height: 20px;"><td style="font-family: Verdana, Arial, sans-serif; line-height: 20px; padding: 24px 4%; padding-bottom: 0; border-left: 1px solid rgba(0,0,0,0.05);"></td>
            <td class="content" style="font-family: Verdana, Arial, sans-serif; line-height: 20px; padding: 24px 4%; padding-bottom: 0; border-left: 1px solid rgba(0,0,0,0.05); border-right: 1px solid rgba(0,0,0,0.05); background-color: #fff; border-bottom: 1px solid rgba(0,0,0,0.05); border-top: 1px solid rgba(0,0,0,0.05);">
                <table border="0" cellpadding="0" cellspacing="0" width="100%" style="font-family: Verdana, Arial, sans-serif; line-height: 20px;"><tbody style="font-family: Verdana, Arial, sans-serif; line-height: 20px;"><tr style="font-family: Verdana, Arial, sans-serif; line-height: 20px;"><td style="font-family: Verdana, Arial, sans-serif; line-height: 20px; vertical-align: top;">
                                <a href="https://razorpay.com/" target="_blank" style="font-family: Verdana, Arial, sans-serif; line-height: 20px;">
                                    <img style="font-family: Verdana, Arial, sans-serif; line-height: 20px; height: 24px;" src="https://razorpay.com/images/logo-black.png"></a>
                            </td>
                            <td class="footerRZP" style="font-family: Verdana, Arial, sans-serif; line-height: 20px; color: rgba(0,0,0,0.54); font-size: 10px; padding-bottom: 24px; padding-left: 10%; text-align: right;">
                                <div style="font-family: Verdana, Arial, sans-serif; line-height: 20px;">
                                    Sign up at <a href="https://razorpay.com/" target="_blank" style="font-family: Verdana, Arial, sans-serif; line-height: 20px;">razorpay.com/invoices</a> to create invoices and accept payments for your business.
                                </div>
                            </td>
                        </tr></tbody></table></td>
            <td style="font-family: Verdana, Arial, sans-serif; line-height: 20px; padding: 24px 4%; padding-bottom: 0; border-right: 1px solid rgba(0,0,0,0.05);"></td>
        </tr><tr style="font-family: Verdana, Arial, sans-serif; line-height: 20px;"><td colspan="3" style="font-family: Verdana, Arial, sans-serif; line-height: 20px; padding: 24px 4%; padding-bottom: 0; border-left: 1px solid rgba(0,0,0,0.05); border-right: 1px solid rgba(0,0,0,0.05);"></td>
        </tr></tbody></table></body></html>
