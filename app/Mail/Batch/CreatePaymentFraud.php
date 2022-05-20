<?php


namespace RZP\Mail\Batch;


use Carbon\Carbon;
use RZP\Constants\MailTags;
use RZP\Constants\Timezone;
use RZP\Mail\Base\Constants;
use RZP\Models\Batch\Entity;

class CreatePaymentFraud extends Base
{
    protected static $mailTag     = MailTags::PAYMENT_FRAUD_BATCH_FILE_CREATED;

    protected static $sender      = Constants::SUPPORT;

    protected static $subjectLine = 'Payment Frauds Created from Fraud Report';

    protected static $body        = 'Please find attached the payment frauds created.';

    const RECIPIENTS = [
        'payments-onlinepayments-txn-risk@razorpay.com',
        'crossborder-risk@razorpay.com',
    ];

    protected function addRecipients()
    {
        $this->to(self::RECIPIENTS);

        return $this;
    }
}
