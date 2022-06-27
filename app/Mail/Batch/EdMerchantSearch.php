<?php


namespace RZP\Mail\Batch;
use RZP\Constants\MailTags;
use RZP\Mail\Base\Constants;

class EdMerchantSearch extends Base
{
    protected static $mailTag     = MailTags::ED_MERCHANT_SEARCH_RESULT;

    protected static $sender      = Constants::SUPPORT;

    protected static $subjectLine = 'Merchant search results for ed request query submitted';

    protected static $body        = 'Please find attached the results of the merchant search query submitted';

    const RECIPIENTS = [
        'payments-onlinepayments-txn-risk@razorpay.com',
        'fraud.alerts@razorpay.com',
    ];

    protected function addRecipients()
    {
        $this->to(self::RECIPIENTS);

        return $this;
    }
}
