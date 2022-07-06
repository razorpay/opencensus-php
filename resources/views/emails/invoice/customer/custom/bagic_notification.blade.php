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
        $amountPaid = ($invoice['amount_paid']);
        if ((isset($payment)) && $payment['adjusted_amount']>0)
        {
            $amountPaid = $payment['adjusted_amount'];
        }
        $amountDue = $invoice['amount'] - $amountPaid;
        $amountPaidFormatted = number_format($amountPaid / 100, 2);
        $amountDueFormatted  = number_format($amountDue / 100, 2);

        $policy_expire = '';
        $bagic_des = '';
        if(isset($invoice['notes'])){
            $notes = $invoice['notes'];
            $policy_expire = $notes['term_end_date'] ?? $policy_expire;
        }
        $bagic_description = $invoice['description'] ?? $bagic_des;
        $reportEmailUrl = 'https://razorpay.com/support/payments/report-merchant/?e=' . base64_encode($invoice['id']) . '&m=' . base64_encode($invoice['customer_details']['customer_email']) . '&s=' . base64_encode('customer_email');
        $showReportMailFlag = false;
        if (isset($view_preferences['exempt_customer_flagging']) === true) {
            $showReportMailFlag = !$is_test_mode and !$view_preferences['exempt_customer_flagging'];
        }
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
                    This {{$invoice['type_label']}} is created in <b style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #8a6d3b;">Test Mode</b>. Only test payments can be made for this {{$invoice['type_label']}}.
                </td>
                <td class="last" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; padding: 24px 4%; padding-bottom: 0; border-right: 1px solid #f2f2f2; width: 3%; background-color: {{ $merchant['brand_color'] }}; color: {{ $merchant['brand_text_color'] }};"></td>
            </tr>
        @endif


        @if ($invoice['description'])
            <tr style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E;">
                @if ($is_test_mode)
                    <td class="first" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E; padding: 24px 4%; padding-bottom: 0; border-left: 1px solid #f2f2f2; width: 3%;"></td>
                @else
                    <td class="first" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; padding: 24px 4%; padding-bottom: 0; border-left: 1px solid #f2f2f2; width: 3%; background-color: {{ $merchant['brand_color'] }}; color: {{ $merchant['brand_text_color'] }};"></td>
                @endif
                <td colspan="2" class="content" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E; padding: 24px 4%; padding-bottom: 0; background-color: #fff; border-left: 1px solid #f2f2f2; border-right: 1px solid #f2f2f2; width: 92%; border-top: 1px solid #f2f2f2;">
                    <div style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E;">
                        <label style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; font-size: 12px; color: #9B9B9B; font-weight: bold; text-transform: uppercase;">{{ $invoice['type_label'] === 'Invoice' ? 'INVOICE SUMMARY' : 'PAYMENT FOR'}}</label>
                        <div style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E; white-space: pre-wrap;word-wrap: break-word;">{{$bagic_description}}</div>
                        @if (isset($custom_labels['rb_bank_additional_description']) === true and $custom_labels['rb_bank_additional_description'] === true)
                            <br/><br/>
                            Assuring you of our best service, at all times.
                            <br/><br/>
                            Kind Regards,
                            <br/>
                            {{$merchant['name']}}
                        @endif
                    </div>
                </td>

                @if ($is_test_mode)
                    <td class="last" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E; padding: 24px 4%; padding-bottom: 0; border-right: 1px solid #f2f2f2; width: 3%;"></td>
                @else
                    <td class="last" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; padding: 24px 4%; padding-bottom: 0; border-right: 1px solid #f2f2f2; width: 3%; background-color: {{ $merchant['brand_color'] }}; color: {{ $merchant['brand_text_color'] }};"></td>
                @endif
            </tr>
        @endif

        @if (isset($invoice['entity_type']) === true and $invoice['entity_type'] === 'subscription_registration' and $invoice['subscription_registration']['method'] === 'emandate')
            @if ($invoice['subscription_registration']['expire_at'] or $invoice['subscription_registration']['max_amount'])
                <tr style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E;">
                    @if ($is_test_mode)
                        <td class="first" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E; padding: 24px 4%; padding-bottom: 0; border-left: 1px solid #f2f2f2; width: 3%;"></td>
                    @else
                        <td class="first" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; padding: 24px 4%; padding-bottom: 0; border-left: 1px solid #f2f2f2; width: 3%; background-color: {{ $merchant['brand_color'] }}; color: {{ $merchant['brand_text_color'] }};"></td>
                    @endif

                    <td colspan="2" class="content" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E; padding: 24px 4%; padding-bottom: 0; background-color: #fff; border-left: 1px solid #f2f2f2; border-right: 1px solid #f2f2f2; width: 92%; border-top: 1px solid #f2f2f2;">
                        <div style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E;">
                            <label style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; font-size: 12px; color: #9B9B9B; font-weight: bold; text-transform: uppercase;">MANDATE DETAILS</label>
                            <div style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E;">
                                @if ($invoice['subscription_registration']['expire_at'])
                                    <div>Mandate End Date: {{epoch_format($invoice['subscription_registration']['expire_at'])}}</div>
                                @endif

                                @if ($invoice['subscription_registration']['max_amount'])
                                    <div>Mandate Max Amount: {{$invoice['currency']}} {{number_format($invoice['subscription_registration']['max_amount'] / 100, 2)}}</div>
                                @endif
                            </div>
                        </div>
                    </td>

                    @if ($is_test_mode)
                        <td class="last" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E; padding: 24px 4%; padding-bottom: 0; border-right: 1px solid #f2f2f2; width: 3%;"></td>
                    @else
                        <td class="last" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; padding: 24px 4%; padding-bottom: 0; border-right: 1px solid #f2f2f2; width: 3%; background-color: {{ $merchant['brand_color'] }}; color: {{ $merchant['brand_text_color'] }};"></td>
                    @endif

                </tr>
            @endif

            @if(isset($invoice['subscription_registration']['bank_account']) and $invoice['subscription_registration']['bank_account']['bank_name'])
                <tr style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E;">
                    @if ($is_test_mode)
                        <td class="first" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E; padding: 24px 4%; padding-bottom: 0; border-left: 1px solid #f2f2f2; width: 3%;"></td>
                    @else
                        <td class="first" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; padding: 24px 4%; padding-bottom: 0; border-left: 1px solid #f2f2f2; width: 3%; background-color: {{ $merchant['brand_color'] }}; color: {{ $merchant['brand_text_color'] }};"></td>
                    @endif

                    <td colspan="2" class="content" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E; padding: 24px 4%; padding-bottom: 0; background-color: #fff; border-left: 1px solid #f2f2f2; border-right: 1px solid #f2f2f2; width: 92%; border-top: 1px solid #f2f2f2;">
                        <div style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E;">
                            <label style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; font-size: 12px; color: #9B9B9B; font-weight: bold; text-transform: uppercase;">BANK DETAILS</label>
                            <div style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E;">
                                <div>Bank: {{$invoice['subscription_registration']['bank_account']['bank_name']}}</div>
                                <div>Name on Account: {{$invoice['subscription_registration']['bank_account']['name']}}</div>
                                <div>IFSC: {{$invoice['subscription_registration']['bank_account']['ifsc']}}</div>
                                <div>Account Number: {{$invoice['subscription_registration']['bank_account']['account_number']}}</div>
                            </div>
                        </div>
                    </td>

                    @if ($is_test_mode)
                        <td class="last" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E; padding: 24px 4%; padding-bottom: 0; border-right: 1px solid #f2f2f2; width: 3%;"></td>
                    @else
                        <td class="last" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; padding: 24px 4%; padding-bottom: 0; border-right: 1px solid #f2f2f2; width: 3%; background-color: {{ $merchant['brand_color'] }}; color: {{ $merchant['brand_text_color'] }};"></td>
                    @endif

                </tr>
            @endif
        @endif

        <tr style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E;">
            @if ($invoice['description'] || $is_test_mode)
                <td class="first" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E; padding: 24px 4%; padding-bottom: 0; border-left: 1px solid #f2f2f2; width: 3%;"></td>
            @else
                <td class="first" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; padding: 24px 4%; padding-bottom: 0; border-left: 1px solid #f2f2f2; width: 3%; background-color: {{ $merchant['brand_color'] }}; color: {{ $merchant['brand_text_color'] }};"></td>
            @endif
            @if (isset($view_preferences) === true and isset($view_preferences['hide_issued_to']) === true and ($view_preferences['hide_issued_to'] === 1 or $view_preferences['hide_issued_to'] === true))
                <td colspan="2" class="content" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E; background-color: #fff; border-left: 1px solid #f2f2f2; border-right: 1px solid #f2f2f2; width: 92%;"></td>
            @elseif (empty($policy_expire) === true)
                <td colspan="2" class="content" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E; background-color: #fff; border-left: 1px solid #f2f2f2; border-right: 1px solid #f2f2f2; width: 92%;"></td>
            @else
                <td colspan="2" class="content" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E; padding: 24px 4%; background-color: #fff; border-left: 1px solid #f2f2f2; border-right: 1px solid #f2f2f2; width: 92%;">
                    <div style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E;">
                        <label style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; font-size: 12px; color: #9B9B9B; font-weight: bold; text-transform: uppercase;">POLICY EXPIRES ON</label>
                        <div style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E;">
                            <div style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E;">
                                {{$policy_expire}}
                            </div>
                        </div>
                    </div>
                </td>
            @endif
            @if ($invoice['description'] || $is_test_mode)
                <td class="last" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E; padding: 0 4%; border-right: 1px solid #f2f2f2; width: 3%;"></td>
            @else
                <td class="last" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; padding: 0 4%; border-right: 1px solid #f2f2f2; width: 3%; background-color: {{ $merchant['brand_color'] }}; color: {{ $merchant['brand_text_color'] }};"></td>
            @endif
        </tr>

        @if (isset($payment))
            <tr style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E;">
                <td class="first" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E; padding: 24px 4%; padding-bottom: 0; border-left: 1px solid #f2f2f2; width: 3%;"></td>
                <td colspan="2" class="content" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E; padding: 0 4%; background-color: #fff; border-left: 1px solid #f2f2f2; border-right: 1px solid #f2f2f2; width: 92%;">
                    <label style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; font-size: 12px; color: #9B9B9B; font-weight: bold; text-transform: uppercase;">PAYMENT ID</label>
                    <div style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E;">{{ $payment['public_id'] }}</div>
                </td>
                <td class="last" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E; padding: 24px 4%; padding-bottom: 0; border-right: 1px solid #f2f2f2; width: 3%;"></td>
            </tr>
            <tr style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E;">
                <td class="first" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E; padding: 24px 4%; padding-bottom: 0; border-left: 1px solid #f2f2f2; width: 3%;"></td>
                <td colspan="2" class="content" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E; padding: 24px 4%; background-color: #fff; border-left: 1px solid #f2f2f2; border-right: 1px solid #f2f2f2; width: 92%; padding-bottom: 24px;">
                    <label style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; font-size: 12px; color: #9B9B9B; font-weight: bold; text-transform: uppercase;">PAYMENT METHOD</label>
                    <div style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E;">{{ $payment['method'][0] }}, {{ $payment['method'][1] }}</div>
                </td>
                <td class="last" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E; padding: 24px 4%; padding-bottom: 0; border-right: 1px solid #f2f2f2; width: 3%;"></td>
            </tr>
        @elseif ($invoice['status'] === 'issued' and $invoice['expire_by_formatted'])
            <tr style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E;">
                <td class="first" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E; padding: 24px 4%; padding-bottom: 0; border-left: 1px solid #f2f2f2; width: 3%;"></td>
                <td colspan="2" class="content" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E; padding: 0 4%; background-color: #fff; border-left: 1px solid #f2f2f2; border-right: 1px solid #f2f2f2; width: 92%; padding-bottom: 24px;">
                    <label style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; font-size: 12px; color: #9B9B9B; font-weight: bold; text-transform: uppercase;">
                        {{ isset($custom_labels['expires_on']) === true ? $custom_labels['expires_on'] : 'LINK EXPIRES ON' }}
                    </label>
                    <div style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E;">{{ $invoice['expire_by_formatted'] }}</div>
                </td>
                <td class="last" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E; padding: 24px 4%; padding-bottom: 0; border-right: 1px solid #f2f2f2; width: 3%;"></td>
            </tr>
        @elseif ($invoice['status'] === 'expired')
            <tr style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E;">
                <td class="first" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E; padding: 24px 4%; padding-bottom: 0; border-left: 1px solid #f2f2f2; width: 3%;"></td>
                <td colspan="2" class="content" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E; padding: 0 4%; background-color: #fff; border-left: 1px solid #f2f2f2; border-right: 1px solid #f2f2f2; width: 92%; padding-bottom: 24px;">
                    <label style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; font-size: 12px; color: #9B9B9B; font-weight: bold; text-transform: uppercase;">
                        {{ isset($custom_labels['expired_on']) === true ? $custom_labels['expired_on'] : 'LINK EXPIRED ON' }}
                    </label>
                    <div style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E;">{{ $invoice['expired_at_formatted'] }}</div>
                </td>
                <td class="last" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E; padding: 24px 4%; padding-bottom: 0; border-right: 1px solid #f2f2f2; width: 3%;"></td>
            </tr>
        @endif

        <tr style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E;">
            <td class="first" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E; padding: 24px 4%; padding-bottom: 0; border-left: 1px solid #f2f2f2; width: 3%;"></td>
            <td colspan="2" class="content footer" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E; border-bottom: 1px solid #f2f2f2; border-top: 1px dashed #e5e5e5; padding: 24px 4%; padding-bottom: 0; background-color: #fff; border-left: 1px solid #f2f2f2; border-right: 1px solid #f2f2f2; width: 92%; padding-left: 0; padding-right: 0; padding-top: 0;">
                <table class="table" border="0" cellpadding="0" cellspacing="0" height="100%" width="100%" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E; width: 100%; background-color: #fafafa;"><tbody style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E;">
                    <tr style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E;">
                        <td style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E; padding: 24px 4%; background-color: #fff;">
                            <label style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; font-size: 12px; color: #9B9B9B; font-weight: bold; text-transform: uppercase;">
                                @if(isset($payment) or $invoice['partial_payment'] === true)
                                    AMOUNT PAID
                                @else
                                    AMOUNT PAYABLE
                                @endif
                            </label>
                            <div style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E; font-weight: bold; font-size: 18px;">
                                {{$invoice['currency']}}
                                @if(isset($payment) or $invoice['partial_payment'] === true)
                                    {{$amountPaidFormatted}}
                                @else
                                    {{$amountDueFormatted}}
                                @endif
                            </div>
                        </td>
                        <td rowspan="2" class="text-right" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E; text-align: right; padding: 24px 4%; background-color: #fff; vertical-align: bottom;">
                            @yield("footerCTA")
                        </td>
                    </tr>
                    @if ($invoice['partial_payment'])
                        <tr style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E;">
                            <td style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E; padding: 0 4% 24px; background-color: #fff;">
                                <label style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; font-size: 12px; color: #58666E; font-weight: bold; text-transform: uppercase;">AMOUNT DUE</label>
                                <div style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E; font-weight: bold; font-size: 18px;">
                                    {{$invoice['currency']}} {{$amountDueFormatted}}
                                </div>
                                <div style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E; margin-top: 12px;">You can pay the amount in parts</div>
                            </td>
                        </tr>
                    @endif
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
                                @if(isset($org) === true and empty($org['branding']) === false and empty($org['branding']['branding_logo']) === false)
                                    <img src="https://cdn.razorpay.com/static/assets/hostedpages/powered-by-rzp.svg" alt="logo" />
                                @elseif($invoice['type_label'] === 'Invoice')
                                    @include('emails.partials.support')
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
        <tr style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E;">
            <td class="first" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E; padding: 24px 4%; padding-bottom: 0; border-left: 1px solid #f2f2f2; width: 3%;"></td>
            <td colspan="2" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E; padding: 24px 4%; padding-bottom: 0; width: 92%;"></td>
            <td class="last" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #58666E; padding: 24px 4%; padding-bottom: 0; border-right: 1px solid #f2f2f2; width: 3%;"></td>
        </tr>
        </tbody></table>
</center>

</body>
</html>
