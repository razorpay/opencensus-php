<p class="para-normal font-size-medium" style="font-size: 14px; line-height: 1.5; color: #515978; margin-bottom: 17px;">
    @if (count($refundIdList) == 1)
        The refund for your transaction is initiated. The refund ID for this transaction ID is {{$refundIdList[0]}}. You can check the status of your refund <a href="https://razorpay.com/support/#refund">here</a>.
    @else
        The refund for your transaction is initiated. The refund IDs for this transaction ID are {{join(', ', $refundIdList)}}. You can check the status of your refund <a href="https://razorpay.com/support/#refund">here</a>.
    @endif
</p>

<p class="para-normal font-size-medium" style="font-size: 14px; line-height: 1.5; color: #515978; margin: 20px 0;">
    While refunds are immediate from our end, banks take upto 10 working days to credit the amount to your account.
</p>

<p class="para-normal font-size-medium" style="font-size: 14px; line-height: 1.5; color: #515978; margin: 20px 0;">
    If there is a delay in the refund reflecting in your account, we request you to contact your bank and dispute the transaction.
</p>

<p class="para-normal font-size-medium" style="font-size: 14px; line-height: 1.5; color: #515978; margin: 20px 0;">
    The ticket reference for your request is {{$ticket['id']}}.
</p>
