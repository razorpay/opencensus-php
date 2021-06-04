<?php

namespace RZP\Models\Payout\Bulk;

use RZP\Models\Batch;
use RZP\Constants\Mode;
use RZP\Models\Merchant;
use RZP\Models\FundAccount;

class SampleFile extends Base
{
    const PAYOUT_TO_BANK_ACCOUNT_DATA = [
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
        Batch\Header::FUND_ACCOUNT_PHONE_NUMBER => '',
        Batch\Header::CONTACT_NAME_2            => 'sample',
        Batch\Header::PAYOUT_NARRATION          => 'Sample Narration',
        Batch\Header::PAYOUT_REFERENCE_ID       => '',
        Batch\Header::FUND_ACCOUNT_EMAIL        => '',
        Batch\Header::CONTACT_TYPE              => 'vendor',
        Batch\Header::CONTACT_EMAIL_2           => 'sample@example.com',
        Batch\Header::CONTACT_MOBILE_2          => '9988998899',
        Batch\Header::CONTACT_REFERENCE_ID      => '',
        Batch\Header::NOTES_PLACE               => 'Bangalore',
        Batch\Header::NOTES_CODE                => 'This is a sample note',
    ];

    const PAYOUT_TO_AMAZONPAY_DATA = [
        Batch\Header::RAZORPAYX_ACCOUNT_NUMBER  => '7878780021057150',
        Batch\Header::PAYOUT_AMOUNT_RUPEES      => '10',
        Batch\Header::PAYOUT_CURRENCY           => 'INR',
        Batch\Header::PAYOUT_MODE               => 'amazonpay',
        Batch\Header::PAYOUT_PURPOSE            => 'refund',
        Batch\Header::FUND_ACCOUNT_ID           => '',
        Batch\Header::FUND_ACCOUNT_TYPE         => 'wallet',
        Batch\Header::FUND_ACCOUNT_NAME         => 'sample',
        Batch\Header::FUND_ACCOUNT_IFSC         => '',
        Batch\Header::FUND_ACCOUNT_NUMBER       => '',
        Batch\Header::FUND_ACCOUNT_VPA          => '',
        Batch\Header::FUND_ACCOUNT_PHONE_NUMBER => '+918124632237',
        Batch\Header::CONTACT_NAME_2            => 'sample',
        Batch\Header::PAYOUT_NARRATION          => 'Sample Narration',
        Batch\Header::PAYOUT_REFERENCE_ID       => '',
        Batch\Header::FUND_ACCOUNT_EMAIL        => 'sample@example.com',
        Batch\Header::CONTACT_TYPE              => 'vendor',
        Batch\Header::CONTACT_EMAIL_2           => 'sample@example.com',
        Batch\Header::CONTACT_MOBILE_2          => '9988998899',
        Batch\Header::CONTACT_REFERENCE_ID      => '',
        Batch\Header::NOTES_PLACE               => 'Bangalore',
        Batch\Header::NOTES_CODE                => 'This is a sample note',
    ];

    const SAMPLE_FILE_DATA = [
        self::PAYOUT_TO_BANK_ACCOUNT_DATA,
    ];

    const SAMPLE_FILE_DATA_AMAZON_PAY = [
        self::PAYOUT_TO_BANK_ACCOUNT_DATA,
        self::PAYOUT_TO_AMAZONPAY_DATA,
    ];

    protected function getInputEntries(Merchant\Entity $merchant)
    {
        $merchantEnabledForAmazonpay = (new FundAccount\Core)->isMerchantEnabledForAmazonPay($merchant);

        if($merchantEnabledForAmazonpay === true)
        {
            return self::SAMPLE_FILE_DATA_AMAZON_PAY;
        }

        return self::SAMPLE_FILE_DATA;
    }
}
