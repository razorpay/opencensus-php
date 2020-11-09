<?php

namespace RZP\Models\Payout\Bulk;

use RZP\Models\Batch;
use RZP\Models\Merchant;

class SampleFile extends Base
{
    const SAMPLE_FILE_DATA = [
        Batch\Header::RAZORPAYX_ACCOUNT_NUMBER  => '7878780021057150',
        Batch\Header::PAYOUT_AMOUNT_RUPEES      => '10',
        Batch\Header::PAYOUT_CURRENCY           => 'INR',
        Batch\Header::PAYOUT_MODE               => 'NEFT',
        Batch\Header::PAYOUT_PURPOSE            => 'refund',
        Batch\Header::FUND_ACCOUNT_ID           => '',
        Batch\Header::FUND_ACCOUNT_TYPE         => 'bank_account',
        Batch\Header::FUND_ACCOUNT_NAME         => 'sample',
        Batch\Header::FUND_ACCOUNT_IFSC         => 'SBIN0007105',
        Batch\Header::FUND_ACCOUNT_NUMBER       => '1234567890',
        Batch\Header::FUND_ACCOUNT_VPA          => '',
        Batch\Header::CONTACT_NAME_2            => 'sample',
        Batch\Header::PAYOUT_NARRATION          => 'Sample Narration',
        Batch\Header::PAYOUT_REFERENCE_ID       => '',
        Batch\Header::CONTACT_TYPE              => 'vendor',
        Batch\Header::CONTACT_EMAIL_2           => 'sample@example.com',
        Batch\Header::CONTACT_MOBILE_2          => '9988998899',
        Batch\Header::CONTACT_REFERENCE_ID      => '',
        Batch\Header::NOTES_PLACE               => 'Bangalore',
        Batch\Header::NOTES_CODE                => 'This is a sample note',
    ];

    protected function getInputEntries(Merchant\Entity $merchant)
    {
        return [self::SAMPLE_FILE_DATA];
    }
}
