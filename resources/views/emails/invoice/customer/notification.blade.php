<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN" "http://www.w3.org/TR/REC-html40/loose.dtd">
<html style="color: #212121; font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px;"><body style="color: #212121; font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px;"><div style="color: #212121; font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px;">
  @php
      $status = $invoice['status'];
      $headerLabel = '';
      $ctaLabel = '';
      $ctaHref = '';

      if (isset($payment))
      {
          $ctaLabel = 'DOWNLOAD PDF';
          $ctaHref = $invoice['short_url'];
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
          $headerLabel = strtoupper($invoice['type_label']) . ' EXPIRED';
      }
  @endphp


  @extends('emails.invoice.notification')

  @section('header')
      <table border="0" cellpadding="0" cellspacing="0" height="100%" width="100%" style="color: #212121; font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; width: 100%;"><tbody style="color: #212121; font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px;"><tr style="color: #212121; font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px;"><td class="text-center" style="color: #212121; font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; text-align: center;">
              <div style="color: #212121; font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px;">
                @if ($merchant['image'])
                  <img class="merchant__logo" src="{{ $merchant['image'] }}" style="color: #212121; font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; height: 48px; margin-bottom: 8px; width: 48px;">
                @endif
              </div>
            </td>
          </tr><tr style="color: #212121; font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px;"><td class="text-center" style="color: #212121; font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; text-align: center;">
              <h2 style="color: {{ $merchant['brand_text_color'] }}; font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 24px; font-size: 20px; margin: 0;">
                  {{ $invoice['type_label'] }} from {{$merchant['name']}}
              </h2>
            </td>
          </tr><tr style="color: #212121; font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px;"><td class="text-center" style="color: #212121; font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; text-align: center;">
              <div style="color: {{ $merchant['brand_text_color'] }}; font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px;">
                  @if ($invoice['receipt'])
                      {{ $invoice['type_label'] }} Receipt: {{$invoice['receipt']}}
                  @else
                      {{ $invoice['type_label'] }} Id: {{$invoice['id']}}
                  @endif
              </div>
            </td>
          </tr><tr style="color: #212121; font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px;"><td class="text-center" style="color: #212121; font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; text-align: center;">
              <div style="color: {{ $merchant['brand_text_color'] }}; font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; margin-top: 12px;">
                <div style="color: {{ $merchant['brand_text_color'] }}; font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px;">
                    {{ $headerLabel }}
                </div>
              </div>
            </td>
          </tr></tbody></table>
  @endsection

  @section('footerCTA')
      @if ($ctaLabel)
          <a class="footer--cta" href="{{ $ctaHref }}" target="_blank" style="color: {{ $merchant['brand_text_color'] }}; font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; border-radius: 5px; cursor: pointer; display: inline-block; padding: 10px 15px; text-decoration: none; white-space: nowrap; background-color: {{ $merchant['brand_color'] }}; border: 1px solid {{ $merchant['brand_color'] }};">
            {{ $ctaLabel }}
          </a>
      @endif
  @endsection

</div></body></html>
