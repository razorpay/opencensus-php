<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN" "http://www.w3.org/TR/REC-html40/loose.dtd">
<html style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #212121;"><body style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #212121;"><div style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #212121;">

  @extends('emails.invoice.notification')

  @php
      $status    = $payment_link['status'];

      $amountPaid = ($payment_link['amount_paid']);

      if (isset($payment))
      {
          $amountPaid += $payment['adjusted_amount'];
      }

      $headerLabel = '';
      $ctaLabel = 'PROCEED TO PAY';
      $ctaHref = $payment_link['short_url'];

      if (isset($payment))
      {
          $headerLabel = 'You have made a payment of ' . $payment['amount'];
      }
      elseif ($status === 'issued')
      {
          $headerLabel = $merchant['name'] . ' has sent you an ' . strtolower($payment_link['type_label']) . ' for ' . $payment_link['currency'] . ' ' . $payment_link['amount_formatted'];
      }
      elseif ($status === 'inactive')
      {
          $headerLabel = 'PAYMENT PAGE INACTIVE';
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
                  {{ $payment_link['type_label'] }} from {{$merchant['name']}}
              </h2>
            </td>
          </tr>
<tr style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #212121;">
<td class="text-center" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #212121; text-align: center;">
              <div style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: {{ $merchant['brand_text_color'] }};">
                  @if ($payment_link['receipt'])
                      {{ $payment_link['type_label'] }} Receipt: {{$payment_link['receipt']}}
                  @else
                      {{ $payment_link['type_label'] }} Id: {{$payment_link['id']}}
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