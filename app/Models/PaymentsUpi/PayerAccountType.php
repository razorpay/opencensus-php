<?php

namespace RZP\Models\PaymentsUpi;

class PayerAccountType
{
    const PAYER_ACCOUNT_TYPE_BANK_ACCOUNT = 'bank_account';

    const PAYER_ACCOUNT_TYPE_CREDIT = 'credit_card';

    const PAYER_ACCOUNT_TYPE_PPIWALLET = 'ppiwallet';

    const PRICING_PLAN_RECEIVER_TYPE_CREDIT = 'credit';

    const SUPPORTED_PAYER_ACCOUNT_TYPES = [
        self::PAYER_ACCOUNT_TYPE_BANK_ACCOUNT,
        self::PAYER_ACCOUNT_TYPE_CREDIT,
        self::PAYER_ACCOUNT_TYPE_PPIWALLET
    ];
}
