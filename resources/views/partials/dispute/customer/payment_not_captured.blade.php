<p class="para-normal font-size-medium" style="font-size: 14px; line-height: 1.5; color: #515978; margin-bottom: 17px;">
    This is in reference to the support ticket {{$ticket['id']}} raised by you. We observed that the payment {{$payment['id']}} for the amount of {{$payment['amount']}} made on {{$payment['created_at_str']}} is successful and yet to be accepted by the merchant: {{$merchant['name']}}.
</p>

<p class="para-normal font-size-medium" style="font-size: 14px; line-height: 1.5; color: #515978; margin: 20px 0;">
    If the merchant doesn’t accept the payment by {{$payment['refund_init_date_str']}}, then the amount gets auto-refunded to you within 7-10 working days with a confirmation email.
</p>

<p class="para-normal font-size-medium" style="font-size: 14px; line-height: 1.5; color: #515978; margin: 20px 0;">
    Ideally, you should receive the refund by {{$payment['refund_done_date_str']}}.
</p>

<p class="para-normal font-size-medium" style="font-size: 14px; line-height: 1.5; color: #515978; margin: 20px 0;">
    If in case you do not receive any refund by the above-mentioned date, that would imply that the merchant has captured/received the payment. We suggest you reach out to the merchant for any refund or service related queries as they are the service providers and refunds are initiated from their end.
</p>
