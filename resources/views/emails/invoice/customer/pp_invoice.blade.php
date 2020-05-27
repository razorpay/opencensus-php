<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN" "http://www.w3.org/TR/REC-html40/loose.dtd">
<html style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #212121;"><body style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #212121;"><div style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #212121;">

    @extends('emails.invoice.pp_notification')

    @php
        $status    = $invoice['status'];
        $isInvoice = ($invoice['type'] === 'invoice');

        $amountPaid = ($invoice['amount_paid']);

        if (isset($payment))
        {
            $amountPaid += $payment['adjusted_amount'];
        }

        $headerLabel = '';
        $ctaLabel = '';
        $ctaHref = '';

        if (isset($payment))
        {
            if ($amountPaid >= $invoice['amount'])
            {
              $ctaLabel = $isInvoice ? 'DOWNLOAD PDF' : '';
            }
            else
            {
              $ctaLabel = 'PROCEED TO PAY';
            }

            $ctaHref = $invoice['short_url'];

            $headerLabel = 'You have made a payment of ' . $payment['amount'];
        }
        elseif ($status === 'issued' or $status === 'partially_paid')
        {
            $ctaLabel = 'PROCEED TO PAY';
            $ctaHref = $invoice['short_url'];

            if (isset($invoice['entity_type']) === true and $invoice['entity_type'] === 'subscription_registration')
            {
              $invoice['type_label'] = 'Authorization Link';
              $method = $invoice['subscription_registration']['method'];

              $ctaLabel = ($method === 'card' ? 'PAY AND ' : ''). 'AUTHORIZE';

              $displayMethod =$method === 'card' ? 'card' : 'bank account';

              $headerLabel .= $merchant['name'] . ' has sent you ' . $displayMethod . ' authorization link';

            }
            else
            {
              $headerLabel = $merchant['name'] . ' has sent you ' . ($invoice['type_label']=== 'Invoice' ? 'an ' : 'a ') . strtolower($invoice['type_label']) . ' for ' . $invoice['currency'] . ' ' . $invoice['amount_formatted'];
            }

        }
        elseif ($status === 'expired')
        {
            $headerLabel = strtoupper($invoice['type_label']) . ' EXPIRED';
        }
    @endphp

    @section('header')
        <table border="0" cellpadding="0" cellspacing="0" height="100%" width="100%" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #212121; width: 100%;"><tbody style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #212121;">
            <tr style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #212121;">
                <td class="text-center" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #212121; text-align: center;">
                    <div style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #212121;">
                        @if ($merchant['image'])
                            <img class="merchant__logo" src="{{ $merchant['image'] }}" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #212121; width: 48px; height: 48px; margin-bottom: 8px;">
                        @endif
                    </div>
                </td>
            </tr>
            <tr style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #212121;">
                <td class="text-center" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #212121; text-align: center;">
                    <h2 style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; margin: 0; font-size: 20px; line-height: 24px; color: {{ $merchant['brand_text_color'] }};">
                        Payment Receipt from {{$merchant['name']}}
                    </h2>
                </td>
            </tr>
            <tr style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #212121;">
                <td class="text-center" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #212121; text-align: center;">
                    <div style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: {{ $merchant['brand_text_color'] }};">
                        @if ($invoice['receipt'])
                            @if ($custom_labels['receipt_number'] ?? false)
                                {{ $custom_labels['receipt_number'] }}: {{$invoice['receipt']}}
                            @else
                                Transaction Reference: {{$invoice['receipt']}}
                            @endif
                        @else
                            Transaction Id: {{$invoice['id']}}
                        @endif
                    </div>
                </td>
            </tr>
            <tr style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #212121;">
                <td class="text-center" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #212121; text-align: center;">
                    <div style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; margin-top: 12px; color: {{ $merchant['brand_text_color'] }};">
                        <div style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: {{ $merchant['brand_text_color'] }};">
                            {{ $headerLabel }}
                        </div>
                    </div>
                </td>
            </tr>
            </tbody></table>
    @endsection

    @section('footerCTA')
        @if ($ctaLabel)
            <a class="footer--cta" href="{{ $ctaHref }}" target="_blank" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; text-decoration: none; padding: 9px 15px; display: inline-block; border-radius: 4px; white-space: nowrap; cursor: pointer; color: {{ $merchant['brand_text_color'] }}; background-color: {{ $merchant['brand_color'] }}; border: 1px solid {{ $merchant['brand_color'] }};">
                {{ $ctaLabel }}
            </a>
        @endif
    @endsection

</div></body></html>
