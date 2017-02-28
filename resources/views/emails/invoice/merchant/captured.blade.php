<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN" "http://www.w3.org/TR/REC-html40/loose.dtd">
<html style="color: #212121; font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px;"><body style="color: #212121; font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px;"><p style="color: #212121; font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px;">
  @extends('emails.invoice.notification')

  @section('header')
    </p><div class="text-center" style="color: #212121; font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; text-align: center;">
         <h2 style="color: {{ $merchant['brand_text_color'] }}; font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 24px; font-size: 20px; margin: 0;">
            {{ $invoice['type_label'] }}: {{ $invoice['receipt'] or $invoice['id'] }} paid successfully
        </h2>
        <div style="color: {{ $merchant['brand_text_color'] }}; font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; margin-top: 8px;">
            {{ $invoice['receipt'] or $invoice['id'] }} issued on {{ $invoice['issued_at_formatted'] }} has been paid
        </div>
    </div>
  @endsection

  @section('footerCTA')
      <a class="footer--cta" href="{{ $invoice['dashboard_url'] }}" target="_blank" style="color: {{ $merchant['brand_text_color'] }}; font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; border-radius: 5px; cursor: pointer; display: inline-block; padding: 10px 15px; text-decoration: none; white-space: nowrap; background-color: {{ $merchant['brand_color'] }}; border: 1px solid {{ $merchant['brand_color'] }};">
        VIEW ON DASHBOARD
      </a>
  @endsection
</body></html>
