<?php

namespace RZP\Mail\Batch;

use Carbon\Carbon;

use RZP\Constants\MailTags;
use RZP\Constants\Timezone;
use RZP\Mail\Base\Constants;

class MerchantOnboarding extends Base
{
    protected static $mailTag     = MailTags::BATCH_MERCHANT_ONBOARDING_FILE;

    protected static $sender      = Constants::MERCHANT_ONBOARDING;

    protected static $subjectLine = 'Razorpay | Merchant onboarding file for %s dated %s';

    protected static $body        = 'Please find attached the terminals created for %s';

    protected function addSubject()
    {
        $today = Carbon::now(Timezone::IST)->format('d-m-Y');

        $this->subject(sprintf(static::$subjectLine, $this->batchSettings['gateway'], $today));

        return $this;
    }

    protected function addMailData()
    {
        $data = ['body' => sprintf(static::$body, $this->batchSettings['gateway'])];

        $this->with($data);

        return $this;
    }

    protected function addRecipients()
    {
        // Add more if required
        $emails = ['albin.george@razorpay.com', 'vivek@razorpay.com'];

        $this->to($emails);

        return $this;
    }
}
