<?php

namespace RZP\Notifications\Settlement;

class Events
{
    const PROCESSED     = 'PROCESSED';

    const FAILED        = 'FAILED';

    const SMS_TEMPLATES = [
        self::PROCESSED     => 'sms.settlements.processed_new',
        self::FAILED        => 'sms.settlements.failed_new',
    ];

    const WHATSAPP_TEMPLATES = [
        self::PROCESSED => 'A settlement of {amount} for your Razorpay account {merchant_id} for {date} has been deposited into your bank account ending with {bank_account_id}, Settlement Id is {settlement_id} and UTR is {utr}. Please login into your Razorpay dashboard for additional details at {url}.',
        self::FAILED    => 'We regret to inform you that the settlement for Razorpay merchant account {merchant_id} for {date} failed to be processed to your account: {bank_account_id} because of this error {failure_reason}, Please login into your Razorpay account and update your bank account details at {url}.',
    ];
    
}
