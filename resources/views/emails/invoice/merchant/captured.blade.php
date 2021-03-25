<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN" "http://www.w3.org/TR/REC-html40/loose.dtd">
<html style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #212121;"><body style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #212121;">
<p style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #212121;">
  @extends('emails.invoice.notification')

  @section('header')
    </p>
<div class="text-center" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #212121; text-align: center;">
         <h2 style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; margin: 0; font-size: 20px; line-height: 24px; color: {{ $merchant['brand_text_color'] }};">
            {{ $invoice['type_label'] }}: {{ $invoice['receipt'] ?? $invoice['id'] }} paid successfully
        </h2>
        <div style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: {{ $merchant['brand_text_color'] }}; margin-top: 8px;">
            {{ $invoice['receipt'] ?? $invoice['id'] }} issued on {{ $invoice['issued_at_formatted'] }} has been paid
        </div>
    </div>
  @endsection

  @section('footerCTA')
      <a class="footer--cta" href="{{ $invoice['dashboard_url'] }}" target="_blank" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; text-decoration: none; padding: 10px 15px; display: inline-block; border-radius: 5px; white-space: nowrap; cursor: pointer; color: {{ $merchant['brand_text_color'] }}; background-color: {{ $merchant['brand_color'] }}; border: 1px solid {{ $merchant['brand_color'] }};">
        VIEW ON DASHBOARD
      </a>
  @endsection
</body></html>