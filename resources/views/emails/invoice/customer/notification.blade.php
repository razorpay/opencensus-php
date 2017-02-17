<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN" "http://www.w3.org/TR/REC-html40/loose.dtd">
<html style="color: rgba(0,0,0,0.87); font-family: Arial, sans-serif; line-height: 20px;"><body style="color: rgba(0,0,0,0.87); font-family: Arial, sans-serif; line-height: 20px;"><div style="color: rgba(0,0,0,0.87); font-family: Arial, sans-serif; line-height: 20px;">
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
          $headerLabel = 'INVOICE EXPIRED';
      }
  @endphp


  @extends('emails.invoice.notification')

  @section('header')
    <div class="text-center" style="color: rgba(0,0,0,0.87); font-family: Arial, sans-serif; line-height: 20px; text-align: center;">
        @if ($merchant['image'])
          <img class="merchant__logo" src="{{ $merchant['image'] }}" style="color: rgba(0,0,0,0.87); font-family: Arial, sans-serif; line-height: 20px; height: 48px; margin-bottom: 8px; width: 48px;">
        @endif

         <h2 style="color: {{ $merchant['brand_text_color'] }}; font-family: Arial, sans-serif; line-height: 24px; font-size: 20px; margin: 0;">
            Invoice from {{$merchant['name']}}
        </h2>
        <div style="color: {{ $merchant['brand_text_color'] }}; font-family: Arial, sans-serif; line-height: 20px;">
            @if ($invoice['receipt'])
                Invoice Receipt: {{$invoice['receipt']}}
            @else
                Invoice Id: {{$invoice['id']}}
            @endif
        </div>

         <div style="color: {{ $merchant['brand_text_color'] }}; font-family: Arial, sans-serif; line-height: 20px; margin-top: 12px;">
            <div style="color: {{ $merchant['brand_text_color'] }}; font-family: Arial, sans-serif; line-height: 20px;">
                {{ $headerLabel }}
            </div>

            @unless ($status === 'expired')
              <div style="color: {{ $merchant['brand_text_color'] }}; font-family: Arial, sans-serif; line-height: 20px;">
                  <!-- The invoice for the same is attached in this mail. -->
              </div>
            @endunless
         </div>
    </div>
  @endsection

  @section('footerCTA')
    @if ($ctaLabel)
        <a class="footer--cta" href="{{ $ctaHref }}" target="_blank" style="color: {{ $merchant['brand_text_color'] }}; font-family: Arial, sans-serif; line-height: 20px; border-radius: 5px; cursor: pointer; display: inline-block; padding: 7px 20px; text-decoration: none; white-space: nowrap; background-color: {{ $merchant['brand_color'] }}; border: 1px solid {{ $merchant['brand_color'] }};">
          {{ $ctaLabel }}
        </a>
    @endif
  @endsection
</div></body></html>
